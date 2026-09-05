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
import 'package:yessal_caisse/core/sync/issue_retry_controller.dart';
import 'package:yessal_caisse/core/sync/outbox_repository.dart';
import 'package:yessal_caisse/core/sync/sync_outbox_service.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  const channel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');

  setUp(() {
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(channel, (call) async => null);
  });

  test('reports retrying then success only for applied', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final event = await _failed(repository);
    final response = Completer<http.Response>();
    final client = _Client([() => response.future]);
    final controller = IssueRetryController(_service(repository, client));

    final first = controller.retry(organizationId: 1, outboxId: event.id);
    final second = controller.retry(organizationId: 1, outboxId: event.id);
    expect(controller.state, IssueRetryState.retrying);
    expect(await second, isNull);
    await Future<void>.delayed(Duration.zero);
    expect(client.requests, hasLength(1));
    response.complete(_response('accepted', {'status': 'applied'}));
    final result = await first;

    expect(result!.status, OutboxStatus.applied);
    expect(controller.state, IssueRetryState.success);
    expect(controller.error, isNull);
  });

  test('reports a non-applied retry result as an error state', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final event = await _failed(repository);
    final controller = IssueRetryController(
      _service(repository, _Client([() => throw TimeoutException('timeout')])),
    );

    final result = await controller.retry(
      organizationId: 1,
      outboxId: event.id,
    );

    expect(result!.status, OutboxStatus.queued);
    expect(controller.state, IssueRetryState.error);
    expect(controller.error, 'La connexion au serveur a expiré.');
  });
}

Future<OutboxEvent> _failed(OutboxRepository repository) async {
  final event = await repository.enqueue(
    organizationId: 1,
    shopId: 10,
    deviceId: 20,
    eventUuid: 'retry-controller',
    entityType: 'sale',
    entityId: 'sale-1',
    action: 'create',
    payloadJson: '{}',
    occurredAt: DateTime.utc(2026, 9, 5),
  );
  await repository.markSending(organizationId: 1, id: event.id);
  return (await repository.markFailed(
    organizationId: 1,
    id: event.id,
    error: 'HTTP 500',
    failureKind: OutboxFailureKind.http,
    httpStatusCode: 500,
  ))!;
}

SyncOutboxService _service(OutboxRepository repository, _Client client) =>
    SyncOutboxService(
      ApiClient(
        client: client,
        tokenStorage: TokenStorage(storage: const FlutterSecureStorage()),
      )..organizationId = 1,
      repository,
    );

http.Response _response(String category, Map<String, dynamic> item) =>
    http.Response(
      jsonEncode({
        category: [item],
      }),
      200,
    );

class _Client extends http.BaseClient {
  _Client(this._actions);
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
