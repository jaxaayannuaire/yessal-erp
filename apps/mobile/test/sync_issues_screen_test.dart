import 'package:drift/drift.dart';
import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:yessal_caisse/core/api/api_client.dart';
import 'package:yessal_caisse/core/database/app_database.dart';
import 'package:yessal_caisse/core/storage/token_storage.dart';
import 'package:yessal_caisse/core/sync/outbox_repository.dart';
import 'package:yessal_caisse/core/sync/sync_issues_screen.dart';
import 'package:yessal_caisse/core/sync/sync_outbox_service.dart';
import 'package:yessal_caisse/core/sync/sync_screen.dart';

void main() {
  testWidgets(
    'lists translated issue statuses, filters, and excludes non issues',
    (tester) async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      final conflict = await _issue(
        repository,
        uuid: 'conflict',
        status: OutboxStatus.conflict,
        error: 'Stock insuffisant.',
      );
      await _issue(
        repository,
        uuid: 'rejected',
        status: OutboxStatus.rejected,
        error: 'Produit introuvable.',
      );
      final failed = await _issue(
        repository,
        uuid: 'failed',
        status: OutboxStatus.failed,
        error: 'Erreur serveur.',
      );
      await _enqueue(repository, uuid: 'queued');
      final sending = await _enqueue(repository, uuid: 'sending');
      await repository.markSending(organizationId: 1, id: sending.id);
      final applied = await _enqueue(repository, uuid: 'applied');
      await repository.markSending(organizationId: 1, id: applied.id);
      await repository.markApplied(
        organizationId: 1,
        id: applied.id,
        serverResultJson: '{}',
      );

      await tester.pumpWidget(
        MaterialApp(
          home: SyncIssuesScreen(organizationId: 1, outbox: repository),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Conflit'), findsOneWidget);
      expect(find.text('Rejeté'), findsOneWidget);
      expect(find.text('Erreur'), findsOneWidget);
      expect(find.textContaining('Erreur serveur.'), findsOneWidget);
      expect(find.textContaining('queued'), findsNothing);
      expect(find.textContaining('sending'), findsNothing);
      expect(find.textContaining('applied'), findsNothing);
      await tester.tap(find.text('Conflits'));
      await tester.pumpAndSettle();
      expect(find.text('Conflit'), findsOneWidget);
      expect(find.text('Rejeté'), findsNothing);
      expect(find.textContaining('Stock insuffisant.'), findsOneWidget);
      await tester.tap(find.text('Rejetés'));
      await tester.pumpAndSettle();
      expect(find.text('Rejeté'), findsOneWidget);
      expect(find.textContaining('Produit introuvable.'), findsOneWidget);
      await tester.tap(find.text('Erreurs'));
      await tester.pumpAndSettle();
      expect(find.text('Erreur'), findsOneWidget);
      expect(find.textContaining('Erreur serveur.'), findsOneWidget);
      expect(conflict.status, OutboxStatus.conflict);
      expect(failed.status, OutboxStatus.failed);
    },
  );

  testWidgets('keeps issue lists tenant-scoped and shows an empty state', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _issue(
      repository,
      uuid: 'tenant-a',
      status: OutboxStatus.conflict,
      error: 'A uniquement.',
    );
    await _issue(
      repository,
      organizationId: 2,
      uuid: 'tenant-b',
      status: OutboxStatus.failed,
      error: 'B uniquement.',
    );
    await tester.pumpWidget(
      MaterialApp(
        home: SyncIssuesScreen(
          key: const ValueKey('tenant-a'),
          organizationId: 1,
          outbox: repository,
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.textContaining('A uniquement.'), findsOneWidget);
    expect(find.textContaining('B uniquement.'), findsNothing);

    await tester.pumpWidget(
      MaterialApp(
        home: SyncIssuesScreen(
          key: const ValueKey('tenant-empty'),
          organizationId: 3,
          outbox: repository,
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Aucun problème de synchronisation.'), findsOneWidget);
  });

  testWidgets('shows details safely for valid, invalid, and missing events', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final event = await _issue(
      repository,
      uuid: 'details',
      entityType: 'sale',
      action: 'create',
      status: OutboxStatus.rejected,
      error: 'Client invalide.',
      serverResultJson: '{"code":"customer_invalid","status":"rejected"}',
    );
    await tester.pumpWidget(
      MaterialApp(
        home: SyncIssueDetailScreen(
          key: const ValueKey('details-a'),
          organizationId: 1,
          eventId: event.id,
          outbox: repository,
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Rejeté'), findsOneWidget);
    expect(find.text('Vente'), findsOneWidget);
    expect(find.text('Création'), findsOneWidget);
    expect(find.text('Client invalide.'), findsOneWidget);
    expect(find.text(event.eventUuid), findsOneWidget);
    expect(find.text(event.entityId), findsOneWidget);
    expect(find.text('10'), findsOneWidget);
    await tester.scrollUntilVisible(find.text('20'), 200);
    expect(find.text('20'), findsOneWidget);
    expect(find.text('customer_invalid'), findsOneWidget);
    expect(find.text('rejected'), findsOneWidget);

    await tester.pumpWidget(
      MaterialApp(
        home: SyncIssueDetailScreen(
          key: const ValueKey('details-other-tenant'),
          organizationId: 2,
          eventId: event.id,
          outbox: repository,
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(
      find.text('Événement de synchronisation introuvable.'),
      findsOneWidget,
    );
  });

  testWidgets('uses a fallback for a missing error and ignores invalid JSON', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final event = await _enqueue(
      repository,
      uuid: 'no-error',
      entityType: 'custom_entity',
      action: 'custom_action',
    );
    await database.customUpdate(
      'UPDATE sync_outbox SET status = ?, last_error = NULL, '
      'server_result_json = ? WHERE id = ?',
      variables: [
        Variable.withString(OutboxStatus.failed.databaseValue),
        Variable.withString('not-json'),
        Variable.withInt(event.id),
      ],
    );
    await tester.pumpWidget(
      MaterialApp(
        home: SyncIssueDetailScreen(
          organizationId: 1,
          eventId: event.id,
          outbox: repository,
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('custom_entity'), findsOneWidget);
    expect(find.text('custom_action'), findsOneWidget);
    expect(
      find.text('Aucun détail supplémentaire n’a été fourni.'),
      findsOneWidget,
    );
    expect(find.textContaining('private'), findsNothing);
  });

  testWidgets('navigates from SyncScreen without HTTP or outbox mutations', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final event = await _issue(
      repository,
      uuid: 'immutable',
      entityType: 'custom_entity',
      action: 'custom_action',
      status: OutboxStatus.failed,
      error: 'Erreur de diagnostic.',
      serverResultJson: 'not-json',
    );
    final before = await repository.findById(organizationId: 1, id: event.id);
    final client = _NoRequestClient();
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
    await tester.tap(find.textContaining('Voir les 1 éléments à traiter'));
    await tester.pumpAndSettle();
    expect(find.text('Problèmes de synchronisation'), findsOneWidget);
    await tester.tap(find.text('Erreur'));
    await tester.pumpAndSettle();
    expect(find.text('Détail de synchronisation'), findsOneWidget);
    expect(find.text('custom_entity'), findsOneWidget);
    expect(find.text('custom_action'), findsOneWidget);
    expect(find.text('Erreur de diagnostic.'), findsOneWidget);
    await tester.pageBack();
    await tester.pumpAndSettle();
    await tester.pageBack();
    await tester.pumpAndSettle();

    final after = await repository.findById(organizationId: 1, id: event.id);
    expect(after!.status, before!.status);
    expect(after.attemptCount, before.attemptCount);
    expect(after.lastError, before.lastError);
    expect(after.serverResultJson, before.serverResultJson);
    expect(after.updatedAt, before.updatedAt);
    expect(client.requests, isZero);
  });
}

Future<OutboxEvent> _enqueue(
  OutboxRepository repository, {
  int organizationId = 1,
  required String uuid,
  String entityType = 'sale',
  String action = 'create',
}) => repository.enqueue(
  organizationId: organizationId,
  shopId: 10,
  deviceId: 20,
  eventUuid: uuid,
  entityType: entityType,
  entityId: 'entity-$uuid',
  action: action,
  payloadJson: '{"private":"not-rendered"}',
  occurredAt: DateTime.utc(2026, 9, 5, 0, 14),
);

Future<OutboxEvent> _issue(
  OutboxRepository repository, {
  int organizationId = 1,
  required String uuid,
  required OutboxStatus status,
  required String error,
  String entityType = 'sale',
  String action = 'create',
  String? serverResultJson,
}) async {
  final event = await _enqueue(
    repository,
    organizationId: organizationId,
    uuid: uuid,
    entityType: entityType,
    action: action,
  );
  await repository.markSending(organizationId: organizationId, id: event.id);
  return switch (status) {
    OutboxStatus.conflict => (await repository.markConflict(
      organizationId: organizationId,
      id: event.id,
      error: error,
      serverResultJson: serverResultJson,
    ))!,
    OutboxStatus.rejected => (await repository.markRejected(
      organizationId: organizationId,
      id: event.id,
      error: error,
      serverResultJson: serverResultJson,
    ))!,
    OutboxStatus.failed => (await repository.markFailed(
      organizationId: organizationId,
      id: event.id,
      error: error,
      serverResultJson: serverResultJson,
    ))!,
    _ => throw ArgumentError.value(status, 'status', 'Statut issue attendu'),
  };
}

SyncOutboxService _service(
  OutboxRepository repository,
  _NoRequestClient client,
) => SyncOutboxService(
  ApiClient(
    client: client,
    tokenStorage: TokenStorage(storage: const FlutterSecureStorage()),
  )..organizationId = 1,
  repository,
);

class _NoRequestClient extends http.BaseClient {
  var requests = 0;

  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) {
    requests++;
    throw StateError(
      'Aucun HTTP ne doit être déclenché pendant la consultation.',
    );
  }
}
