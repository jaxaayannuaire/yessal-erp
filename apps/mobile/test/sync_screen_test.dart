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
import 'package:yessal_caisse/core/sync/sync_outbox_service.dart';
import 'package:yessal_caisse/core/sync/sync_screen.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  const channel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');
  setUp(
    () => TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(channel, (call) async => null),
  );

  testWidgets('Home indicator opens SyncScreen without a sync button', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'queued');
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: SyncIndicator(
            organizationId: 1,
            outbox: repository,
            service: _service(repository, Client([])),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.textContaining('Sync : 1 en attente'), findsOneWidget);
    expect(find.text('Synchroniser maintenant'), findsNothing);
    await tester.tap(find.textContaining('Sync : 1 en attente'));
    await tester.pumpAndSettle();
    expect(find.text('Synchronisation'), findsOneWidget);
    expect(find.text('Synchroniser maintenant'), findsOneWidget);
  });

  testWidgets('SyncScreen displays counts and disables empty queue', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final conflict = await _event(repository, 'conflict');
    final rejected = await _event(repository, 'rejected');
    final failed = await _event(repository, 'failed');
    await repository.markSending(organizationId: 1, id: conflict.id);
    await repository.markConflict(
      organizationId: 1,
      id: conflict.id,
      error: 'x',
    );
    await repository.markSending(organizationId: 1, id: rejected.id);
    await repository.markRejected(
      organizationId: 1,
      id: rejected.id,
      error: 'x',
    );
    await repository.markSending(organizationId: 1, id: failed.id);
    await repository.markFailed(organizationId: 1, id: failed.id, error: 'x');
    await tester.pumpWidget(
      MaterialApp(
        home: SyncScreen(
          organizationId: 1,
          outbox: repository,
          service: _service(repository, Client([])),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.textContaining('Conflits      1'), findsOneWidget);
    expect(find.textContaining('Rejetés      1'), findsOneWidget);
    expect(find.textContaining('Erreurs      1'), findsOneWidget);
    expect(find.text('Aucune opération en attente'), findsOneWidget);
    expect(
      tester.widget<FilledButton>(find.byType(FilledButton)).onPressed,
      isNull,
    );
  });

  testWidgets('SyncScreen shows syncing then success once', (tester) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'one');
    final response = Completer<http.Response>();
    final client = Client([() => response.future]);
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
    await tester.tap(find.text('Synchroniser maintenant'));
    await tester.tap(find.text('Synchroniser maintenant'));
    await tester.pump();
    expect(find.textContaining('En cours'), findsOneWidget);
    expect(client.requests, hasLength(1));
    response.complete(
      http.Response(
        jsonEncode({
          'accepted': [
            {'status': 'applied'},
          ],
        }),
        200,
      ),
    );
    await tester.pumpAndSettle();
    expect(find.textContaining('Terminé'), findsOneWidget);
    expect(client.requests, hasLength(1));
  });

  testWidgets('SyncScreen reports a timeout and returns without replaying', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    await _event(repository, 'timeout');
    final client = Client([() => throw TimeoutException('network')]);
    final service = _service(repository, client);
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: SyncIndicator(
            organizationId: 1,
            outbox: repository,
            service: service,
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.textContaining('Sync : 1 en attente'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Synchroniser maintenant'));
    await tester.pumpAndSettle();

    expect(
      find.text(
        'Synchronisation interrompue. Vérifiez votre connexion et réessayez.',
      ),
      findsOneWidget,
    );
    expect(client.requests, hasLength(1));

    await tester.pageBack();
    await tester.pumpAndSettle();
    expect(find.textContaining('Sync : 1 en attente'), findsOneWidget);
    expect(client.requests, hasLength(1));
  });
}

Future<OutboxEvent> _event(OutboxRepository repository, String uuid) =>
    repository.enqueue(
      organizationId: 1,
      shopId: 10,
      deviceId: 30,
      eventUuid: uuid,
      entityType: 'sale',
      entityId: uuid,
      action: 'create',
      payloadJson: '{}',
      occurredAt: DateTime.utc(2026, 1, 1),
    );
SyncOutboxService _service(OutboxRepository repository, Client client) =>
    SyncOutboxService(
      ApiClient(
        client: client,
        tokenStorage: TokenStorage(storage: const FlutterSecureStorage()),
      )..organizationId = 1,
      repository,
    );

class Client extends http.BaseClient {
  Client(this.actions);
  final List<FutureOr<http.Response> Function()> actions;
  final requests = <http.Request>[];
  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) async {
    requests.add(request as http.Request);
    final response = await actions.removeAt(0)();
    return http.StreamedResponse(
      Stream.value(response.bodyBytes),
      response.statusCode,
      headers: response.headers,
    );
  }
}
