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

  test('replays queued events FIFO with one POST per event', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    var tick = 0;
    final repository = OutboxRepository(
      database,
      clock: () => DateTime.utc(2026, 4, 1, 0, 0, tick++),
    );
    final first = await _event(repository, 'first');
    final second = await _event(repository, 'second');
    final client = RecordingClient.responses([_applied(), _applied()]);
    final results = await _service(repository, client).syncPending(1);

    expect(results.map((event) => event.eventUuid), ['first', 'second']);
    expect(client.requests, hasLength(2));
    expect(_eventUuid(client.requests[0]), 'first');
    expect(_eventUuid(client.requests[1]), 'second');
    expect(
      (await repository.findById(organizationId: 1, id: first.id))!.status,
      OutboxStatus.applied,
    );
    expect(
      (await repository.findById(organizationId: 1, id: second.id))!.status,
      OutboxStatus.applied,
    );
  });

  test(
    'continues after conflict, rejected and failed server responses',
    () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      await _event(repository, 'conflict');
      await _event(repository, 'rejected');
      await _event(repository, 'failed');
      await _event(repository, 'applied');
      final client = RecordingClient.responses([
        _response('conflicts', {'message': 'Stock insuffisant'}),
        _response('rejected', {'message': 'Session fermée'}),
        _response('failed', {'message': 'Erreur explicite'}),
        _applied(),
      ]);

      final results = await _service(repository, client).syncPending(1);

      expect(results.map((event) => event.status), [
        OutboxStatus.conflict,
        OutboxStatus.rejected,
        OutboxStatus.failed,
        OutboxStatus.applied,
      ]);
      expect(client.requests, hasLength(4));
    },
  );

  test('stops after a retryable network failure', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final first = await _event(repository, 'first');
    final second = await _event(repository, 'second');
    final client = RecordingClient.actions([
      () => throw TimeoutException('timeout'),
      () => _applied(),
    ]);

    final results = await _service(repository, client).syncPending(1);

    expect(results, hasLength(1));
    expect(results.single.status, OutboxStatus.queued);
    expect(client.requests, hasLength(1));
    expect(
      (await repository.findById(organizationId: 1, id: first.id))!.status,
      OutboxStatus.queued,
    );
    expect(
      (await repository.findById(organizationId: 1, id: second.id))!.status,
      OutboxStatus.queued,
    );
    expect(
      (await repository.findById(
        organizationId: 1,
        id: second.id,
      ))!.attemptCount,
      0,
    );
  });

  test(
    'does not replay terminal events and keeps organizations isolated',
    () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      final terminal = await _event(repository, 'terminal');
      await repository.markSending(organizationId: 1, id: terminal.id);
      await repository.markApplied(
        organizationId: 1,
        id: terminal.id,
        serverResultJson: '{}',
      );
      await _event(repository, 'tenant-two', organizationId: 2);
      final client = RecordingClient.responses([]);
      final service = _service(repository, client);

      expect(await service.syncPending(1), isEmpty);
      await expectLater(
        service.syncPending(2),
        throwsA(isA<SyncOutboxException>()),
      );
      expect(client.requests, isEmpty);
    },
  );

  test('shares one active run across simultaneous calls', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'only-once');
    final response = Completer<http.Response>();
    final client = RecordingClient.actions([() => response.future]);
    final service = _service(repository, client);

    final first = service.syncPending(1);
    final second = service.syncPending(1);
    expect(identical(first, second), isTrue);
    await Future<void>.delayed(Duration.zero);
    expect(client.requests, hasLength(1));
    response.complete(_applied());

    expect((await first).single.status, OutboxStatus.applied);
    expect(client.requests, hasLength(1));
  });
}

Future<OutboxEvent> _event(
  OutboxRepository repository,
  String eventUuid, {
  int organizationId = 1,
}) => repository.enqueue(
  organizationId: organizationId,
  shopId: 10,
  deviceId: 30,
  eventUuid: eventUuid,
  entityType: 'sale',
  entityId: 'local-$eventUuid',
  action: 'create',
  payloadJson: '{"currency":"XOF"}',
  occurredAt: DateTime.utc(2026, 4, 1),
);

SyncOutboxService _service(
  OutboxRepository repository,
  RecordingClient client,
) => SyncOutboxService(
  ApiClient(
    client: client,
    tokenStorage: TokenStorage(storage: const FlutterSecureStorage()),
  )..organizationId = 1,
  repository,
);

http.Response _applied() => _response('accepted', {'status': 'applied'});

http.Response _response(String category, Map<String, dynamic> item) =>
    http.Response(
      jsonEncode({
        category: [item],
      }),
      200,
    );

String _eventUuid(http.Request request) {
  final body = jsonDecode(request.body) as Map<String, dynamic>;
  return ((body['events'] as List).single as Map<String, dynamic>)['event_uuid']
      as String;
}

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
