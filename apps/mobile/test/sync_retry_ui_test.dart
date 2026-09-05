import 'dart:async';
import 'dart:convert';

import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:yessal_caisse/core/api/api_client.dart';
import 'package:yessal_caisse/core/database/app_database.dart';
import 'package:yessal_caisse/core/storage/token_storage.dart';
import 'package:yessal_caisse/core/sync/outbox_repository.dart';
import 'package:yessal_caisse/core/sync/outbox_retry_policy.dart';
import 'package:yessal_caisse/core/sync/sync_issues_screen.dart';
import 'package:yessal_caisse/core/sync/sync_outbox_service.dart';
import 'package:yessal_caisse/core/sync/sync_screen.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  const channel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');

  setUp(() {
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(channel, (call) async => null);
  });

  for (final code in [408, 429, 500, 503]) {
    testWidgets('shows retry action for HTTP $code', (tester) async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      final event = await _failedHttp(repository, code: code);

      await _pumpDetail(tester, repository, event);

      expect(find.text('Réessayer cette opération'), findsOneWidget);
      expect(
        find.text('Cet échec technique peut être réessayé.'),
        findsOneWidget,
      );
    });
  }

  for (final scenario in <String, _Failure>{
    'HTTP 422': const _Failure(OutboxFailureKind.http, 422),
    'conflict': const _Failure(OutboxFailureKind.businessConflict),
    'rejected': const _Failure(OutboxFailureKind.businessRejected),
    'server processing': const _Failure(OutboxFailureKind.serverProcessing),
    'local payload': const _Failure(OutboxFailureKind.localPayloadInvalid),
    'protocol': const _Failure(OutboxFailureKind.protocolInvalid),
    'legacy': const _Failure(null),
  }.entries) {
    testWidgets('does not show retry action for ${scenario.key}', (
      tester,
    ) async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      final event = await _failed(repository, failure: scenario.value);

      await _pumpDetail(tester, repository, event);

      expect(find.text('Réessayer cette opération'), findsNothing);
      expect(
        find.text(const OutboxRetryPolicy().evaluate(event).reason),
        findsOneWidget,
      );
    });
  }

  testWidgets('cancels confirmation without HTTP or outbox mutation', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final event = await _failedHttp(repository, code: 500);
    final client = _Client.responses([]);
    var refreshes = 0;
    await _pumpDetail(
      tester,
      repository,
      event,
      service: _service(
        repository,
        client,
        refresh: ({required organizationId, required shopId}) async {
          refreshes++;
        },
      ),
    );

    await tester.tap(find.text('Réessayer cette opération'));
    await tester.pumpAndSettle();
    expect(find.text('Réessayer cette opération ?'), findsOneWidget);
    expect(
      find.textContaining('même identifiant de synchronisation'),
      findsOneWidget,
    );
    await tester.tap(find.text('Annuler'));
    await tester.pumpAndSettle();

    final reloaded = await repository.findById(organizationId: 1, id: event.id);
    expect(reloaded!.status, OutboxStatus.failed);
    expect(reloaded.attemptCount, event.attemptCount);
    expect(client.requests, isEmpty);
    expect(refreshes, 0);
  });

  testWidgets('shows retrying once then applied success without payload', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final event = await _failedHttp(repository, code: 500);
    final response = Completer<http.Response>();
    final client = _Client.actions([() => response.future]);
    var refreshes = 0;
    await _pumpDetail(
      tester,
      repository,
      event,
      service: _service(
        repository,
        client,
        refresh: ({required organizationId, required shopId}) async {
          refreshes++;
        },
      ),
    );

    await _confirmRetry(tester);
    expect(find.text('Nouvelle tentative en cours…'), findsOneWidget);
    expect(
      tester
          .widget<FilledButton>(
            find.widgetWithText(FilledButton, 'Nouvelle tentative en cours…'),
          )
          .onPressed,
      isNull,
    );
    await tester.tap(
      find.widgetWithText(FilledButton, 'Nouvelle tentative en cours…'),
      warnIfMissed: false,
    );
    await tester.pump();
    expect(client.requests, hasLength(1));
    response.complete(_applied());
    await tester.pumpAndSettle();

    expect(find.text('Synchronisation réussie.'), findsOneWidget);
    expect(find.textContaining('private-payload'), findsNothing);
    expect(refreshes, 1);
    final reloaded = await repository.findById(organizationId: 1, id: event.id);
    expect(reloaded!.status, OutboxStatus.applied);
  });

  testWidgets('shows timeout and removes queued work from issues', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final event = await _failedHttp(repository, code: 500);
    final client = _Client.actions([() => throw TimeoutException('timeout')]);
    await _pumpDetail(
      tester,
      repository,
      event,
      service: _service(repository, client),
    );

    await _confirmRetry(tester);
    await tester.pumpAndSettle();

    expect(find.text('La connexion au serveur a expiré.'), findsOneWidget);
    final reloaded = await repository.findById(organizationId: 1, id: event.id);
    expect(reloaded!.status, OutboxStatus.queued);
    expect(await repository.listIssues(1), isEmpty);
  });

  for (final scenario in <String, http.Response>{
    'conflict': _response('conflicts', {'message': 'Stock insuffisant'}),
    'rejected': _response('rejected', {'message': 'Session fermée'}),
    'server processing': _response('failed', {'message': 'Échec serveur'}),
  }.entries) {
    testWidgets('reloads detail after retry ${scenario.key}', (tester) async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      final event = await _failedHttp(repository, code: 500);
      final client = _Client.responses([scenario.value]);
      await _pumpDetail(
        tester,
        repository,
        event,
        service: _service(repository, client),
      );

      await _confirmRetry(tester);
      await tester.pumpAndSettle();

      final expected = switch (scenario.key) {
        'conflict' => 'Conflit',
        'rejected' => 'Rejeté',
        _ => 'Erreur',
      };
      expect(find.text(expected), findsOneWidget);
      expect(find.text('Réessayer cette opération'), findsNothing);
      expect((await repository.listIssues(1)).map((item) => item.id), [
        event.id,
      ]);
    });
  }

  testWidgets('reloads Issues and SyncScreen counts locally after applied', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _failedHttp(repository, code: 500);
    final client = _Client.responses([_applied()]);
    await tester.pumpWidget(
      MaterialApp(
        home: SyncScreen(
          organizationId: 1,
          outbox: repository,
          service: _service(repository, client),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.textContaining('Erreurs      1'), findsOneWidget);
    await tester.tap(find.textContaining('Voir les 1 éléments à traiter'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Erreur'));
    await tester.pumpAndSettle();
    await _confirmRetry(tester);
    await tester.pumpAndSettle();
    await tester.pageBack();
    await tester.pumpAndSettle();
    expect(find.text('Aucun problème de synchronisation.'), findsOneWidget);
    await tester.pageBack();
    await tester.pumpAndSettle();
    expect(find.textContaining('Erreurs      0'), findsOneWidget);
    expect(find.textContaining('En attente      0'), findsOneWidget);
    expect(client.requests, hasLength(1));
  });

  testWidgets('keeps tenant data isolated and hides a cross-tenant detail', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final eventA = await _failedHttp(repository, code: 500);
    final eventB = await _failedHttp(repository, organizationId: 2, code: 500);
    final client = _Client.responses([]);
    expect((await repository.listIssues(1)).map((event) => event.id), [
      eventA.id,
    ]);
    await tester.pumpWidget(
      MaterialApp(
        home: SyncIssuesScreen(
          organizationId: 1,
          outbox: repository,
          service: _service(repository, client),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.byType(ListTile), findsOneWidget);

    await tester.pumpWidget(
      MaterialApp(
        home: SyncIssueDetailScreen(
          organizationId: 1,
          eventId: eventB.id,
          outbox: repository,
          service: _service(repository, client),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(
      find.text('Événement de synchronisation introuvable.'),
      findsOneWidget,
    );
    expect(find.text('Réessayer cette opération'), findsNothing);
    expect(client.requests, isEmpty);
  });
}

class _Failure {
  const _Failure(this.kind, [this.code]);
  final OutboxFailureKind? kind;
  final int? code;
}

Future<void> _pumpDetail(
  WidgetTester tester,
  OutboxRepository repository,
  OutboxEvent event, {
  SyncOutboxService? service,
}) async {
  final resolvedService =
      service ?? _service(repository, _Client.responses([]));
  await tester.pumpWidget(
    MaterialApp(
      home: SyncIssueDetailScreen(
        organizationId: event.organizationId,
        eventId: event.id,
        outbox: repository,
        service: resolvedService,
      ),
    ),
  );
  await tester.pumpAndSettle();
  await tester.scrollUntilVisible(find.text('Reprise'), 200);
}

Future<void> _confirmRetry(WidgetTester tester) async {
  await tester.scrollUntilVisible(find.text('Réessayer cette opération'), 200);
  await tester.tap(find.text('Réessayer cette opération'));
  await tester.pumpAndSettle();
  await tester.tap(find.text('Réessayer'));
  await tester.pump();
}

Future<OutboxEvent> _failedHttp(
  OutboxRepository repository, {
  int organizationId = 1,
  required int code,
}) => _failed(
  repository,
  organizationId: organizationId,
  failure: _Failure(OutboxFailureKind.http, code),
);

Future<OutboxEvent> _failed(
  OutboxRepository repository, {
  int organizationId = 1,
  required _Failure failure,
}) async {
  final event = await repository.enqueue(
    organizationId: organizationId,
    shopId: 10,
    deviceId: 20,
    eventUuid: 'event-$organizationId-${DateTime.now().microsecondsSinceEpoch}',
    entityType: 'sale',
    entityId: 'local-sale',
    action: 'create',
    payloadJson: '{"private-payload":"never-render"}',
    occurredAt: DateTime.utc(2026, 9, 5, 10),
  );
  await repository.markSending(organizationId: organizationId, id: event.id);
  if (failure.kind == OutboxFailureKind.businessConflict) {
    return (await repository.markConflict(
      organizationId: organizationId,
      id: event.id,
      error: 'Conflit métier.',
    ))!;
  }
  if (failure.kind == OutboxFailureKind.businessRejected) {
    return (await repository.markRejected(
      organizationId: organizationId,
      id: event.id,
      error: 'Rejet métier.',
    ))!;
  }
  return (await repository.markFailed(
    organizationId: organizationId,
    id: event.id,
    error: failure.code == null ? 'Erreur historique.' : 'HTTP ${failure.code}',
    failureKind: failure.kind,
    httpStatusCode: failure.code,
  ))!;
}

SyncOutboxService _service(
  OutboxRepository repository,
  _Client client, {
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

http.Response _applied() =>
    _response('accepted', {'status': 'applied', 'duplicate': true});

http.Response _response(String category, Map<String, dynamic> item) =>
    http.Response(
      jsonEncode({
        category: [item],
      }),
      200,
    );

class _Client extends http.BaseClient {
  _Client.responses(List<http.Response> responses)
    : _actions = responses
          .map(
            (response) =>
                () async => response,
          )
          .toList();
  _Client.actions(List<FutureOr<http.Response> Function()> actions)
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
