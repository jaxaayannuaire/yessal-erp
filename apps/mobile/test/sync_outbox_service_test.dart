import 'dart:async';
import 'dart:convert';

import 'package:drift/native.dart';
import 'package:flutter/services.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:yessal_caisse/core/api/api_client.dart';
import 'package:yessal_caisse/core/database/app_database.dart';
import 'package:yessal_caisse/core/sync/outbox_repository.dart';
import 'package:yessal_caisse/core/sync/sync_outbox_service.dart';
import 'package:yessal_caisse/core/storage/token_storage.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  const channel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');

  setUp(() {
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(channel, (call) async => null);
  });

  test('sends one queued event and persists an applied result', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final httpClient = RecordingClient.responses([
      _jsonResponse({
        'accepted': [
          {
            'status': 'applied',
            'duplicate': false,
            'result': {'sale_id': 123, 'status': 'finalized'},
          },
        ],
      }),
    ]);
    final event = await _event(database);
    final result = await _service(
      database,
      httpClient,
    ).syncOne(organizationId: 1, outboxId: event.id);
    final body =
        jsonDecode(httpClient.requests.single.body) as Map<String, dynamic>;
    final requestEvent =
        (body['events'] as List).single as Map<String, dynamic>;

    expect(httpClient.requests.single.url.path, '/api/v1/caisse/sync/push');
    expect(httpClient.requests.single.headers['X-Organization-Id'], '1');
    expect(body['device_id'], 30);
    expect(requestEvent['event_uuid'], event.eventUuid);
    expect(requestEvent['shop_id'], 10);
    expect(requestEvent['entity_type'], 'sale');
    expect(requestEvent['entity_id'], 'local-1');
    expect(requestEvent['action'], 'create');
    expect(requestEvent['payload'], _payload);
    expect(requestEvent['occurred_at'], '2026-03-01T10:30:00.000Z');
    expect(result.status, OutboxStatus.applied);
    expect(result.attemptCount, 1);
    expect(result.serverResultJson, contains('sale_id'));
    expect(result.lastError, isNull);
  });

  test(
    'retries a timeout with the same event and accepts duplicate applied',
    () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final httpClient = RecordingClient.actions([
        () => throw TimeoutException('timeout'),
        () => _jsonResponse({
          'accepted': [
            {
              'status': 'applied',
              'duplicate': true,
              'result': {'sale_id': 123},
            },
          ],
        }),
      ]);
      final event = await _event(database);
      final service = _service(database, httpClient);

      final queued = await service.syncOne(
        organizationId: 1,
        outboxId: event.id,
      );
      expect(queued.status, OutboxStatus.queued);
      expect(queued.attemptCount, 1);
      expect(queued.eventUuid, event.eventUuid);
      expect(queued.payloadJson, event.payloadJson);
      final applied = await service.syncOne(
        organizationId: 1,
        outboxId: event.id,
      );

      expect(applied.status, OutboxStatus.applied);
      expect(applied.attemptCount, 2);
      expect(applied.eventUuid, event.eventUuid);
      expect(await _outboxCount(database), 1);
    },
  );

  test('persists conflict, rejection and explicit server failure', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final httpClient = RecordingClient.responses([
      _jsonResponse({
        'conflicts': [
          {'message': 'Stock insuffisant'},
        ],
      }),
      _jsonResponse({
        'rejected': [
          {'message': 'Session fermée'},
        ],
      }),
      _jsonResponse({
        'failed': [
          {'message': 'Erreur serveur'},
        ],
      }),
    ]);
    final service = _service(database, httpClient);
    final conflict = await _event(database, eventUuid: 'conflict');
    final rejected = await _event(database, eventUuid: 'rejected');
    final failed = await _event(database, eventUuid: 'failed');

    expect(
      (await service.syncOne(organizationId: 1, outboxId: conflict.id)).status,
      OutboxStatus.conflict,
    );
    expect(
      (await service.syncOne(organizationId: 1, outboxId: rejected.id)).status,
      OutboxStatus.rejected,
    );
    final result = await service.syncOne(
      organizationId: 1,
      outboxId: failed.id,
    );
    expect(result.status, OutboxStatus.failed);
    expect(result.lastError, 'Erreur serveur');
    expect(result.serverResultJson, contains('Erreur serveur'));
  });

  test('marks HTTP responses outside the sync contract as failed', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final httpClient = RecordingClient.responses([
      _jsonResponse({'message': 'Non authentifié'}, statusCode: 401),
      _jsonResponse({'message': 'Interdit'}, statusCode: 403),
      _jsonResponse({'message': 'Validation'}, statusCode: 422),
      _jsonResponse({'message': 'Indisponible'}, statusCode: 500),
    ]);
    final service = _service(database, httpClient);
    for (final code in [401, 403, 422, 500]) {
      final event = await _event(database, eventUuid: 'http-$code');
      final result = await service.syncOne(
        organizationId: 1,
        outboxId: event.id,
      );
      expect(result.status, OutboxStatus.failed);
      expect(result.lastError, contains('HTTP $code'));
    }
  });

  test(
    'marks malformed and ambiguous successful responses as failed',
    () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final httpClient = RecordingClient.responses([
        _jsonResponse({}),
        _jsonResponse({
          'accepted': [
            {'status': 'applied'},
          ],
          'conflicts': [
            {'message': 'Conflit'},
          ],
        }),
      ]);
      final service = _service(database, httpClient);
      for (final uuid in ['empty', 'multiple']) {
        final event = await _event(database, eventUuid: uuid);
        final result = await service.syncOne(
          organizationId: 1,
          outboxId: event.id,
        );
        expect(result.status, OutboxStatus.failed);
        expect(
          result.lastError,
          'Réponse de synchronisation serveur invalide.',
        );
      }
    },
  );

  test(
    'does not call HTTP for non-queued, cross-tenant or wrongly scoped work',
    () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final httpClient = RecordingClient.responses([]);
      final repository = OutboxRepository(database);
      final event = await _event(database);
      await repository.markSending(organizationId: 1, id: event.id);
      final service = _service(database, httpClient);
      await expectLater(
        service.syncOne(organizationId: 1, outboxId: event.id),
        throwsA(isA<SyncOutboxException>()),
      );
      await expectLater(
        service.syncOne(organizationId: 2, outboxId: event.id),
        throwsA(isA<SyncOutboxException>()),
      );
      final queued = await _event(database, eventUuid: 'wrong-scope');
      final wrongScope = SyncOutboxService(
        _api(httpClient, organizationId: 2),
        repository,
      );
      await expectLater(
        wrongScope.syncOne(organizationId: 1, outboxId: queued.id),
        throwsA(isA<SyncOutboxException>()),
      );
      expect(httpClient.requests, isEmpty);
    },
  );

  test('marks invalid local JSON as failed without HTTP', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final httpClient = RecordingClient.responses([]);
    final event = await _event(database, payloadJson: '{invalide');

    final result = await _service(
      database,
      httpClient,
    ).syncOne(organizationId: 1, outboxId: event.id);

    expect(result.status, OutboxStatus.failed);
    expect(
      result.lastError,
      'Le payload de synchronisation local est invalide.',
    );
    expect(result.attemptCount, 1);
    expect(result.payloadJson, '{invalide');
    expect(httpClient.requests, isEmpty);
  });
}

const _payload = {
  'terminal_id': 20,
  'cash_session_id': 300,
  'local_uuid': 'local-1',
  'receipt_number': 'MOB-001',
  'currency': 'XOF',
  'lines': [
    {'product_id': 100, 'quantity': 2, 'unit_price': 1000},
  ],
  'payment': {'method': 'cash', 'amount': 2000},
  'finalize': true,
};

Future<OutboxEvent> _event(
  AppDatabase database, {
  String eventUuid = 'event-1',
  String payloadJson = '{"terminal_id":20,"cash_session_id":300,"local_uuid":"local-1","receipt_number":"MOB-001","currency":"XOF","lines":[{"product_id":100,"quantity":2,"unit_price":1000}],"payment":{"method":"cash","amount":2000},"finalize":true}',
}) => OutboxRepository(database).enqueue(
  organizationId: 1,
  shopId: 10,
  deviceId: 30,
  eventUuid: eventUuid,
  entityType: 'sale',
  entityId: 'local-1',
  action: 'create',
  payloadJson: payloadJson,
  occurredAt: DateTime.utc(2026, 3, 1, 10, 30),
);

SyncOutboxService _service(AppDatabase database, RecordingClient httpClient) =>
    SyncOutboxService(
      _api(httpClient, organizationId: 1),
      OutboxRepository(database),
    );

ApiClient _api(RecordingClient httpClient, {required int organizationId}) =>
    ApiClient(
      client: httpClient,
      tokenStorage: TokenStorage(storage: const FlutterSecureStorage()),
    )..organizationId = organizationId;

http.Response _jsonResponse(
  Map<String, dynamic> body, {
  int statusCode = 200,
}) => http.Response(jsonEncode(body), statusCode);

Future<int> _outboxCount(AppDatabase database) async =>
    (await database.select(database.syncOutbox).get()).length;

class RecordingClient extends http.BaseClient {
  RecordingClient.responses(List<http.Response> responses)
    : _actions = responses
          .map<FutureOr<http.Response> Function()>(
            (response) =>
                () => response,
          )
          .toList();
  RecordingClient.actions(List<FutureOr<http.Response> Function()> actions)
    : _actions = actions;

  final List<FutureOr<http.Response> Function()> _actions;
  final requests = <http.Request>[];

  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) async {
    requests.add(request as http.Request);
    final response = await _actions.removeAt(0)();
    return http.StreamedResponse(
      Stream.value(response.bodyBytes),
      response.statusCode,
      headers: response.headers,
    );
  }
}
