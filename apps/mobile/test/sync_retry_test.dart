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
import 'package:yessal_caisse/core/sync/outbox_retry_policy.dart';
import 'package:yessal_caisse/core/sync/sync_outbox_service.dart';
import 'package:yessal_caisse/core/storage/token_storage.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  const channel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');

  setUp(() {
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(channel, (call) async => null);
  });

  for (final code in [500, 408, 429, 503]) {
    test('retries failed HTTP $code with the same outbox event', () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      final initial = await _failedHttpEvent(repository, code: code);
      var refreshes = 0;
      final client = _RecordingClient.responses([_appliedResponse()]);
      final service = _service(
        repository,
        client,
        refresh: ({required organizationId, required shopId}) async {
          refreshes++;
          expect(organizationId, 1);
          expect(shopId, initial.shopId);
        },
      );

      final result = await service.retryFailed(
        organizationId: 1,
        outboxId: initial.id,
      );

      expect(result.status, OutboxStatus.applied);
      expect(result.attemptCount, initial.attemptCount + 1);
      expect(client.requests, hasLength(1));
      expect(refreshes, 1);
      expect(await _outboxCount(database), 1);
      _expectIdentity(result, initial);
    });
  }

  test(
    'treats duplicate applied as applied and refreshes exactly once',
    () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      final initial = await _failedHttpEvent(repository, code: 500);
      var refreshes = 0;
      final client = _RecordingClient.responses([
        _appliedResponse(duplicate: true),
      ]);

      final result = await _service(
        repository,
        client,
        refresh: ({required organizationId, required shopId}) async {
          refreshes++;
        },
      ).retryFailed(organizationId: 1, outboxId: initial.id);

      expect(result.status, OutboxStatus.applied);
      expect(result.serverResultJson, contains('duplicate'));
      expect(client.requests, hasLength(1));
      expect(refreshes, 1);
      expect(await _outboxCount(database), 1);
      _expectIdentity(result, initial);
    },
  );

  test('returns a retry timeout to queued without refreshing', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final initial = await _failedHttpEvent(repository, code: 500);
    var refreshes = 0;
    final client = _RecordingClient.actions([
      () => throw TimeoutException('timeout'),
    ]);

    final result = await _service(
      repository,
      client,
      refresh: ({required organizationId, required shopId}) async {
        refreshes++;
      },
    ).retryFailed(organizationId: 1, outboxId: initial.id);

    expect(result.status, OutboxStatus.queued);
    expect(result.failureKind, OutboxFailureKind.network);
    expect(result.httpStatusCode, isNull);
    expect(client.requests, hasLength(1));
    expect(refreshes, 0);
    expect(await _outboxCount(database), 1);
    _expectIdentity(result, initial);
  });

  for (final scenario in <String, http.Response>{
    'conflict': _jsonResponse({
      'conflicts': [
        {'message': 'Stock insuffisant'},
      ],
    }),
    'rejected': _jsonResponse({
      'rejected': [
        {'message': 'Session fermée'},
      ],
    }),
    'server failed': _jsonResponse({
      'failed': [
        {'message': 'Traitement serveur échoué'},
      ],
    }),
  }.entries) {
    test('keeps a retry result as ${scenario.key} without refresh', () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      final initial = await _failedHttpEvent(repository, code: 500);
      var refreshes = 0;
      final client = _RecordingClient.responses([scenario.value]);

      final result = await _service(
        repository,
        client,
        refresh: ({required organizationId, required shopId}) async {
          refreshes++;
        },
      ).retryFailed(organizationId: 1, outboxId: initial.id);

      final expected = switch (scenario.key) {
        'conflict' => OutboxStatus.conflict,
        'rejected' => OutboxStatus.rejected,
        _ => OutboxStatus.failed,
      };
      final kind = switch (scenario.key) {
        'conflict' => OutboxFailureKind.businessConflict,
        'rejected' => OutboxFailureKind.businessRejected,
        _ => OutboxFailureKind.serverProcessing,
      };
      expect(result.status, expected);
      expect(result.failureKind, kind);
      expect(result.httpStatusCode, isNull);
      expect(const OutboxRetryPolicy().evaluate(result).allowed, isFalse);
      expect(client.requests, hasLength(1));
      expect(refreshes, 0);
      expect(await _outboxCount(database), 1);
      _expectIdentity(result, initial);
    });
  }

  test(
    'refuses non retryable status, tenant mismatch and cross-tenant id',
    () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      final http422 = await _failedHttpEvent(repository, code: 422);
      final conflict = await _terminalEvent(repository, OutboxStatus.conflict);
      final rejected = await _terminalEvent(repository, OutboxStatus.rejected);
      final serverFailed = await _failedServerEvent(repository);
      final otherTenant = await _failedHttpEvent(
        repository,
        organizationId: 2,
        code: 500,
      );
      final client = _RecordingClient.responses([]);
      final service = _service(repository, client);

      for (final event in [http422, conflict, rejected, serverFailed]) {
        final before = await repository.findById(
          organizationId: 1,
          id: event.id,
        );
        await expectLater(
          service.retryFailed(organizationId: 1, outboxId: event.id),
          throwsA(isA<SyncOutboxException>()),
        );
        final after = await repository.findById(
          organizationId: 1,
          id: event.id,
        );
        expect(after!.status, before!.status);
        expect(after.attemptCount, before.attemptCount);
        _expectIdentity(after, before);
      }

      await expectLater(
        service.retryFailed(organizationId: 1, outboxId: otherTenant.id),
        throwsA(isA<SyncOutboxException>()),
      );
      final wrongScopeService = _service(repository, client, organizationId: 2);
      final beforeMismatch = await repository.findById(
        organizationId: 1,
        id: http422.id,
      );
      await expectLater(
        wrongScopeService.retryFailed(organizationId: 1, outboxId: http422.id),
        throwsA(isA<SyncOutboxException>()),
      );
      final afterMismatch = await repository.findById(
        organizationId: 1,
        id: http422.id,
      );
      expect(afterMismatch!.attemptCount, beforeMismatch!.attemptCount);
      _expectIdentity(afterMismatch, beforeMismatch);
      expect(client.requests, isEmpty);
    },
  );

  test(
    'deduplicates concurrent retries into one post and one refresh',
    () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      final initial = await _failedHttpEvent(repository, code: 500);
      final response = Completer<http.Response>();
      final client = _RecordingClient.actions([() => response.future]);
      var refreshes = 0;
      final service = _service(
        repository,
        client,
        refresh: ({required organizationId, required shopId}) async {
          refreshes++;
        },
      );

      final first = service.retryFailed(
        organizationId: 1,
        outboxId: initial.id,
      );
      final second = service.retryFailed(
        organizationId: 1,
        outboxId: initial.id,
      );
      await Future<void>.delayed(Duration.zero);
      expect(client.requests, hasLength(1));
      response.complete(_appliedResponse());
      final results = await Future.wait([first, second]);

      expect(results.map((event) => event.id).toSet(), {initial.id});
      expect(results.map((event) => event.status).toSet(), {
        OutboxStatus.applied,
      });
      expect(refreshes, 1);
      expect(await _outboxCount(database), 1);
      final reloaded = await repository.findById(
        organizationId: 1,
        id: initial.id,
      );
      expect(reloaded!.attemptCount, initial.attemptCount + 1);
      _expectIdentity(reloaded, initial);
    },
  );
}

var _eventSequence = 0;

Future<OutboxEvent> _failedHttpEvent(
  OutboxRepository repository, {
  int organizationId = 1,
  required int code,
}) async {
  final event = await _enqueue(repository, organizationId: organizationId);
  await repository.markSending(organizationId: organizationId, id: event.id);
  return (await repository.markFailed(
    organizationId: organizationId,
    id: event.id,
    error: 'HTTP $code',
    failureKind: OutboxFailureKind.http,
    httpStatusCode: code,
  ))!;
}

Future<OutboxEvent> _terminalEvent(
  OutboxRepository repository,
  OutboxStatus status,
) async {
  final event = await _enqueue(repository);
  await repository.markSending(organizationId: 1, id: event.id);
  return switch (status) {
    OutboxStatus.conflict => (await repository.markConflict(
      organizationId: 1,
      id: event.id,
      error: 'Conflit',
    ))!,
    OutboxStatus.rejected => (await repository.markRejected(
      organizationId: 1,
      id: event.id,
      error: 'Rejet',
    ))!,
    _ => throw ArgumentError.value(status),
  };
}

Future<OutboxEvent> _failedServerEvent(OutboxRepository repository) async {
  final event = await _enqueue(repository);
  await repository.markSending(organizationId: 1, id: event.id);
  return (await repository.markFailed(
    organizationId: 1,
    id: event.id,
    error: 'Échec serveur',
    failureKind: OutboxFailureKind.serverProcessing,
  ))!;
}

Future<OutboxEvent> _enqueue(
  OutboxRepository repository, {
  int organizationId = 1,
}) => repository.enqueue(
  organizationId: organizationId,
  shopId: 10,
  deviceId: 20,
  eventUuid: 'event-$organizationId-${_eventSequence++}',
  entityType: 'sale',
  entityId: 'local-sale',
  action: 'create',
  payloadJson: '{"terminal_id":20,"cash_session_id":30,"local_uuid":"local-sale","currency":"XOF","lines":[],"payment":{"method":"cash","amount":0},"finalize":true}',
  occurredAt: DateTime.utc(2026, 9, 5, 10),
);

SyncOutboxService _service(
  OutboxRepository repository,
  _RecordingClient client, {
  int organizationId = 1,
  OutboxRefresh? refresh,
}) => SyncOutboxService(
  ApiClient(
    client: client,
    tokenStorage: TokenStorage(storage: const FlutterSecureStorage()),
  )..organizationId = organizationId,
  repository,
  refresh: refresh,
);

http.Response _appliedResponse({bool duplicate = false}) => _jsonResponse({
  'accepted': [
    {
      'status': 'applied',
      'duplicate': duplicate,
      'result': {'sale_id': 123, 'status': 'finalized'},
    },
  ],
});

http.Response _jsonResponse(Map<String, dynamic> body) =>
    http.Response(jsonEncode(body), 200);

Future<int> _outboxCount(AppDatabase database) async =>
    (await database.select(database.syncOutbox).get()).length;

void _expectIdentity(OutboxEvent actual, OutboxEvent expected) {
  expect(actual.id, expected.id);
  expect(actual.organizationId, expected.organizationId);
  expect(actual.shopId, expected.shopId);
  expect(actual.deviceId, expected.deviceId);
  expect(actual.eventUuid, expected.eventUuid);
  expect(actual.entityType, expected.entityType);
  expect(actual.entityId, expected.entityId);
  expect(actual.action, expected.action);
  expect(actual.payloadJson, expected.payloadJson);
  expect(actual.occurredAt, expected.occurredAt);
}

class _RecordingClient extends http.BaseClient {
  _RecordingClient.responses(List<http.Response> responses)
    : _actions = responses
          .map(
            (response) =>
                () async => response,
          )
          .toList();

  _RecordingClient.actions(List<FutureOr<http.Response> Function()> actions)
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
