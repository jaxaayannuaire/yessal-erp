import 'dart:async';
import 'dart:convert';

import 'package:drift/native.dart';
import 'package:flutter/services.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:yessal_caisse/core/api/api_client.dart';
import 'package:yessal_caisse/core/database/app_database.dart';
import 'package:yessal_caisse/core/storage/token_storage.dart';
import 'package:yessal_caisse/core/sync/manual_sync_controller.dart';
import 'package:yessal_caisse/core/sync/outbox_repository.dart';
import 'package:yessal_caisse/core/sync/sync_outbox_service.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  const channel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');
  setUp(() {
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(channel, (call) async => null);
  });

  test('manual sync reports success and refreshes queued count', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'one');
    final client = Client.responses([_applied()]);
    final controller = _controller(repository, client);
    await controller.load(1);
    expect(controller.queued, 1);

    await controller.sync(1);

    expect(controller.state, ManualSyncState.success);
    expect(controller.queued, 0);
    expect(client.requests, hasLength(1));
  });

  test(
    'double manual trigger shares the single active synchronization',
    () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      await _event(repository, 'one');
      final response = Completer<http.Response>();
      final client = Client.actions([() => response.future]);
      final controller = _controller(repository, client);

      final first = controller.sync(1);
      final second = controller.sync(1);
      await Future<void>.delayed(Duration.zero);
      expect(controller.state, ManualSyncState.syncing);
      expect(client.requests, hasLength(1));
      response.complete(_applied());
      await Future.wait([first, second]);

      expect(client.requests, hasLength(1));
      expect(controller.state, ManualSyncState.success);
    },
  );

  test('timeout leaves queued work and exposes an error', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'timeout');
    final controller = _controller(
      repository,
      Client.actions([() => throw TimeoutException('timeout')]),
    );

    await controller.sync(1);

    expect(controller.state, ManualSyncState.error);
    expect(controller.queued, 1);
  });

  test('shows conflict rejected and failed counts after manual sync', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'conflict');
    await _event(repository, 'rejected');
    await _event(repository, 'failed');
    final controller = _controller(
      repository,
      Client.responses([
        _response('conflicts', {'message': 'Stock'}),
        _response('rejected', {'message': 'Session'}),
        _response('failed', {'message': 'Erreur'}),
      ]),
    );

    await controller.sync(1);

    expect(controller.conflict, 1);
    expect(controller.rejected, 1);
    expect(controller.failed, 1);
    expect(controller.queued, 0);
  });

  test('does not send data when tenant scope changes', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'tenant');
    final client = Client.responses([]);
    final controller = _controller(repository, client, organizationId: 2);

    await controller.sync(1);

    expect(controller.state, ManualSyncState.error);
    expect(client.requests, isEmpty);
  });
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
      occurredAt: DateTime.utc(2026, 6, 1),
    );

ManualSyncController _controller(
  OutboxRepository repository,
  Client client, {
  int organizationId = 1,
}) => ManualSyncController(
  SyncOutboxService(
    ApiClient(
      client: client,
      tokenStorage: TokenStorage(storage: const FlutterSecureStorage()),
    )..organizationId = organizationId,
    repository,
  ),
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
