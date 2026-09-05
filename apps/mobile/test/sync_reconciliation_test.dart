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

  test('refreshes once after an applied event', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'applied');
    final refreshes = <(int, int)>[];

    await _service(
      repository,
      Client.responses([_applied()]),
      refreshes,
    ).syncPending(1);

    expect(refreshes, [(1, 10)]);
  });

  test('refreshes once after several applied events', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'one');
    await _event(repository, 'two');
    final refreshes = <(int, int)>[];

    await _service(
      repository,
      Client.responses([_applied(), _applied()]),
      refreshes,
    ).syncPending(1);

    expect(refreshes, [(1, 10)]);
  });

  test('refreshes after duplicate applied', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'duplicate');
    final refreshes = <(int, int)>[];

    await _service(
      repository,
      Client.responses([
        _response('accepted', {'status': 'applied', 'duplicate': true}),
      ]),
      refreshes,
    ).syncPending(1);

    expect(refreshes, [(1, 10)]);
  });

  test('does not refresh for only terminal non-applied results', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'conflict');
    await _event(repository, 'rejected');
    await _event(repository, 'failed');
    final refreshes = <(int, int)>[];

    await _service(
      repository,
      Client.responses([
        _response('conflicts', {'message': 'Stock'}),
        _response('rejected', {'message': 'Session'}),
        _response('failed', {'message': 'Erreur'}),
      ]),
      refreshes,
    ).syncPending(1);

    expect(refreshes, isEmpty);
  });

  test('does not refresh after a timeout-only run', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'timeout');
    final refreshes = <(int, int)>[];

    await _service(
      repository,
      Client.actions([() => throw TimeoutException('timeout')]),
      refreshes,
    ).syncPending(1);

    expect(refreshes, isEmpty);
  });

  test(
    'does not call HTTP or refresh with an incorrect tenant scope',
    () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      await _event(repository, 'tenant');
      final client = Client.responses([]);
      final refreshes = <(int, int)>[];

      await expectLater(
        _service(
          repository,
          client,
          refreshes,
          organizationId: 2,
        ).syncPending(1),
        throwsA(isA<SyncOutboxException>()),
      );

      expect(client.requests, isEmpty);
      expect(refreshes, isEmpty);
    },
  );

  test(
    'shares replay and refresh between simultaneous syncPending calls',
    () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      await _event(repository, 'once');
      final response = Completer<http.Response>();
      final client = Client.actions([() => response.future]);
      final refreshes = <(int, int)>[];
      final service = _service(repository, client, refreshes);

      final first = service.syncPending(1);
      final second = service.syncPending(1);
      expect(identical(first, second), isTrue);
      await Future<void>.delayed(Duration.zero);
      expect(client.requests, hasLength(1));
      response.complete(_applied());
      await first;

      expect(client.requests, hasLength(1));
      expect(refreshes, [(1, 10)]);
    },
  );
}

Future<OutboxEvent> _event(OutboxRepository repository, String uuid) =>
    repository.enqueue(
      organizationId: 1,
      shopId: 10,
      deviceId: 30,
      eventUuid: uuid,
      entityType: 'sale',
      entityId: 'local-$uuid',
      action: 'create',
      payloadJson: '{}',
      occurredAt: DateTime.utc(2026, 5, 1),
    );

SyncOutboxService _service(
  OutboxRepository repository,
  Client client,
  List<(int, int)> refreshes, {
  int organizationId = 1,
}) => SyncOutboxService(
  ApiClient(
    client: client,
    tokenStorage: TokenStorage(storage: const FlutterSecureStorage()),
  )..organizationId = organizationId,
  repository,
  refresh: ({required organizationId, required shopId}) async {
    refreshes.add((organizationId, shopId));
  },
);

http.Response _applied() => _response('accepted', {'status': 'applied'});
http.Response _response(String category, Map<String, dynamic> item) =>
    http.Response(
      jsonEncode({
        category: [item],
      }),
      200,
    );

class Client extends http.BaseClient {
  Client.responses(List<http.Response> responses)
    : _actions = responses
          .map<FutureOr<http.Response> Function()>(
            (response) =>
                () => response,
          )
          .toList();
  Client.actions(List<FutureOr<http.Response> Function()> actions)
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
