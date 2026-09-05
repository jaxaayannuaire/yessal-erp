import 'dart:io';

import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:yessal_caisse/core/database/app_database.dart';
import 'package:yessal_caisse/core/sync/outbox_repository.dart';

void main() {
  group('OutboxRepository', () {
    test(
      'enqueues queued events and finds them in their organization',
      () async {
        final database = AppDatabase(NativeDatabase.memory());
        addTearDown(database.close);
        final repository = OutboxRepository(
          database,
          clock: () => DateTime.utc(2026, 2, 1),
        );

        final event = await _enqueue(repository, eventUuid: 'event-a');

        expect(event.status, OutboxStatus.queued);
        expect(event.attemptCount, 0);
        expect(event.lastAttemptAt, isNull);
        expect(event.lastError, isNull);
        expect(event.serverResultJson, isNull);
        expect(event.failureKind, isNull);
        expect(event.httpStatusCode, isNull);
        expect(
          await repository.findByEventUuid(
            organizationId: 1,
            eventUuid: 'event-a',
          ),
          isNotNull,
        );
        expect(
          await repository.findByEventUuid(
            organizationId: 2,
            eventUuid: 'event-a',
          ),
          isNull,
        );
        expect(
          await repository.findById(organizationId: 1, id: event.id),
          isNotNull,
        );
        expect(
          await repository.findById(organizationId: 2, id: event.id),
          isNull,
        );
      },
    );

    test('enforces event UUID uniqueness per organization', () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);

      await _enqueue(repository, eventUuid: 'shared-event');
      await expectLater(
        _enqueue(repository, eventUuid: 'shared-event'),
        throwsA(isA<Exception>()),
      );
      final otherTenant = await _enqueue(
        repository,
        organizationId: 2,
        eventUuid: 'shared-event',
      );
      expect(otherTenant.organizationId, 2);
    });

    test(
      'lists organization events in FIFO order and pending queued only',
      () async {
        final database = AppDatabase(NativeDatabase.memory());
        addTearDown(database.close);
        var tick = 0;
        final repository = OutboxRepository(
          database,
          clock: () => DateTime.utc(2026, 2, 2, 0, 0, tick++),
        );

        final first = await _enqueue(repository, eventUuid: 'first');
        final second = await _enqueue(repository, eventUuid: 'second');
        final otherTenant = await _enqueue(
          repository,
          organizationId: 2,
          eventUuid: 'other',
        );
        await repository.markSending(organizationId: 1, id: second.id);

        expect(
          (await repository.listByOrganization(1)).map((event) => event.id),
          [first.id, second.id],
        );
        expect(
          (await repository.listByOrganization(2)).map((event) => event.id),
          [otherTenant.id],
        );
        expect((await repository.listPending(1)).map((event) => event.id), [
          first.id,
        ]);
        expect(await repository.countPending(1), 1);
        expect(await repository.countPending(2), 1);
      },
    );

    test(
      'persists status transitions without changing immutable event data',
      () async {
        final database = AppDatabase(NativeDatabase.memory());
        addTearDown(database.close);
        final repository = OutboxRepository(
          database,
          clock: () => DateTime.utc(2026, 2, 3),
        );
        final initial = await _enqueue(repository, eventUuid: 'event-status');

        var event = await repository.markSending(
          organizationId: 1,
          id: initial.id,
        );
        expect(event!.status, OutboxStatus.sending);
        expect(event.attemptCount, 1);
        expect(event.lastAttemptAt, isNotNull);
        expect(event.lastError, isNull);
        await repository.markQueuedAfterNetworkFailure(
          organizationId: 1,
          id: initial.id,
          error: 'Connexion interrompue.',
        );
        event = await repository.findById(organizationId: 1, id: initial.id);
        expect(event!.status, OutboxStatus.queued);
        expect(event.attemptCount, 1);
        expect(event.lastError, 'Connexion interrompue.');

        await repository.markSending(organizationId: 1, id: initial.id);

        event = await repository.markApplied(
          organizationId: 1,
          id: initial.id,
          serverResultJson: '{"sale_id":123}',
        );
        expect(event!.status, OutboxStatus.applied);
        expect(event.lastError, isNull);
        expect(event.serverResultJson, '{"sale_id":123}');
        expect(event.eventUuid, initial.eventUuid);
        expect(event.payloadJson, initial.payloadJson);
        expect(event.entityId, initial.entityId);
        expect(event.createdAt, initial.createdAt);
        expect(await repository.listPending(1), isEmpty);
      },
    );

    test(
      'retains terminal business statuses outside automatic retry',
      () async {
        final database = AppDatabase(NativeDatabase.memory());
        addTearDown(database.close);
        final repository = OutboxRepository(database);
        final conflict = await _enqueue(repository, eventUuid: 'conflict');
        final rejected = await _enqueue(repository, eventUuid: 'rejected');
        final failed = await _enqueue(repository, eventUuid: 'failed');
        await repository.markSending(organizationId: 1, id: conflict.id);
        await repository.markSending(organizationId: 1, id: rejected.id);
        await repository.markSending(organizationId: 1, id: failed.id);

        expect(
          (await repository.markConflict(
            organizationId: 1,
            id: conflict.id,
            error: 'Stock insuffisant.',
            serverResultJson: '{"reason":"stock"}',
          ))!.status,
          OutboxStatus.conflict,
        );
        expect(
          (await repository.markRejected(
            organizationId: 1,
            id: rejected.id,
            error: 'Session fermée.',
          ))!.status,
          OutboxStatus.rejected,
        );
        expect(
          (await repository.markFailed(
            organizationId: 1,
            id: failed.id,
            error: 'Erreur serveur explicite.',
          ))!.status,
          OutboxStatus.failed,
        );

        final events = await repository.listByOrganization(1);
        expect(
          events.map((event) => event.lastError),
          contains('Stock insuffisant.'),
        );
        expect(
          events.map((event) => event.lastError),
          contains('Session fermée.'),
        );
        expect(
          events.map((event) => event.lastError),
          contains('Erreur serveur explicite.'),
        );
        expect(await repository.listPending(1), isEmpty);
      },
    );

    test(
      'recovers only interrupted sending events in the requested tenant',
      () async {
        final database = AppDatabase(NativeDatabase.memory());
        addTearDown(database.close);
        final repository = OutboxRepository(database);
        final sending = await _enqueue(repository, eventUuid: 'sending');
        final failed = await _enqueue(repository, eventUuid: 'failed');
        final conflict = await _enqueue(repository, eventUuid: 'conflict');
        final otherTenant = await _enqueue(
          repository,
          organizationId: 2,
          eventUuid: 'other-sending',
        );
        await repository.markSending(organizationId: 1, id: sending.id);
        await repository.markSending(organizationId: 1, id: failed.id);
        await repository.markSending(organizationId: 1, id: conflict.id);
        await repository.markFailed(
          organizationId: 1,
          id: failed.id,
          error: 'Erreur.',
        );
        await repository.markConflict(
          organizationId: 1,
          id: conflict.id,
          error: 'Conflit.',
        );
        await repository.markSending(organizationId: 2, id: otherTenant.id);

        expect(await repository.recoverInterruptedSending(1), 1);
        final recovered = await repository.findById(
          organizationId: 1,
          id: sending.id,
        );
        expect(recovered!.status, OutboxStatus.queued);
        expect(recovered.attemptCount, 1);
        expect(
          recovered.lastError,
          'Synchronisation interrompue avant confirmation.',
        );
        expect(
          (await repository.findById(organizationId: 1, id: failed.id))!.status,
          OutboxStatus.failed,
        );
        expect(
          (await repository.findById(
            organizationId: 1,
            id: conflict.id,
          ))!.status,
          OutboxStatus.conflict,
        );
        expect(
          (await repository.findById(
            organizationId: 2,
            id: otherTenant.id,
          ))!.status,
          OutboxStatus.sending,
        );
      },
    );

    test('does not modify another tenant event during a transition', () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      final otherTenant = await _enqueue(
        repository,
        organizationId: 2,
        eventUuid: 'tenant-b',
      );

      expect(
        await repository.markApplied(
          organizationId: 1,
          id: otherTenant.id,
          serverResultJson: '{}',
        ),
        isNull,
      );
      expect(
        (await repository.findById(
          organizationId: 2,
          id: otherTenant.id,
        ))!.status,
        OutboxStatus.queued,
      );
    });

    test('lists only tenant issues from newest to oldest', () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final repository = OutboxRepository(database);
      final conflict = await _enqueue(repository, eventUuid: 'conflict');
      final rejected = await _enqueue(repository, eventUuid: 'rejected');
      final failed = await _enqueue(repository, eventUuid: 'failed');
      final queued = await _enqueue(repository, eventUuid: 'queued');
      final sending = await _enqueue(repository, eventUuid: 'sending');
      final applied = await _enqueue(repository, eventUuid: 'applied');
      final otherTenant = await _enqueue(
        repository,
        organizationId: 2,
        eventUuid: 'other-tenant',
      );

      for (final event in [conflict, rejected, failed, applied]) {
        await repository.markSending(organizationId: 1, id: event.id);
      }
      await repository.markSending(organizationId: 1, id: sending.id);
      await repository.markConflict(
        organizationId: 1,
        id: conflict.id,
        error: 'Conflit.',
      );
      await repository.markRejected(
        organizationId: 1,
        id: rejected.id,
        error: 'Rejet.',
      );
      await repository.markFailed(
        organizationId: 1,
        id: failed.id,
        error: 'Erreur.',
      );
      await repository.markApplied(
        organizationId: 1,
        id: applied.id,
        serverResultJson: '{}',
      );
      await repository.markSending(organizationId: 2, id: otherTenant.id);
      await repository.markFailed(
        organizationId: 2,
        id: otherTenant.id,
        error: 'Autre tenant.',
      );

      expect((await repository.listIssues(1)).map((event) => event.id), [
        failed.id,
        rejected.id,
        conflict.id,
      ]);
      expect((await repository.listIssues(2)).map((event) => event.id), [
        otherTenant.id,
      ]);
      expect(
        (await repository.listIssues(1)).map((event) => event.id),
        isNot(containsAll([queued.id, sending.id, applied.id])),
      );
    });

    test(
      'requeues only retryable HTTP failures without changing identity',
      () async {
        final database = AppDatabase(NativeDatabase.memory());
        addTearDown(database.close);
        final repository = OutboxRepository(database);
        final event = await _enqueue(repository, eventUuid: 'retryable');
        await repository.markSending(organizationId: 1, id: event.id);
        await repository.markFailed(
          organizationId: 1,
          id: event.id,
          error: 'HTTP 500',
          failureKind: OutboxFailureKind.http,
          httpStatusCode: 500,
        );
        final requeued = await repository.requeueRetryableTechnicalFailure(
          organizationId: 1,
          id: event.id,
        );
        expect(requeued!.status, OutboxStatus.queued);
        expect(requeued.eventUuid, event.eventUuid);
        expect(requeued.payloadJson, event.payloadJson);
        expect(requeued.entityId, event.entityId);
        expect(requeued.occurredAt, event.occurredAt);
        expect(requeued.attemptCount, 1);
        expect(
          await repository.requeueRetryableTechnicalFailure(
            organizationId: 1,
            id: event.id,
          ),
          isNull,
        );
      },
    );

    test(
      'clears failure metadata only when a queued event is claimed',
      () async {
        final database = AppDatabase(NativeDatabase.memory());
        addTearDown(database.close);
        var now = DateTime.utc(2026, 9, 5, 10);
        final repository = OutboxRepository(database, clock: () => now);
        final initial = await _enqueue(repository, eventUuid: 'clean-metadata');
        await repository.markSending(organizationId: 1, id: initial.id);
        final firstAttemptAt = (await repository.findById(
          organizationId: 1,
          id: initial.id,
        ))!.lastAttemptAt;
        expect(_unixSeconds(firstAttemptAt!), _unixSeconds(now));
        await repository.markFailed(
          organizationId: 1,
          id: initial.id,
          error: 'HTTP 500',
          failureKind: OutboxFailureKind.http,
          httpStatusCode: 500,
        );

        final requeued = await repository.requeueRetryableTechnicalFailure(
          organizationId: 1,
          id: initial.id,
        );
        expect(requeued!.attemptCount, 1);
        expect(requeued.failureKind, OutboxFailureKind.http);
        expect(requeued.httpStatusCode, 500);
        expect(requeued.lastError, 'HTTP 500');
        expect(requeued.lastAttemptAt, firstAttemptAt);

        now = now.add(const Duration(minutes: 1));
        final sending = await repository.markSending(
          organizationId: 1,
          id: initial.id,
        );
        expect(sending!.status, OutboxStatus.sending);
        expect(sending.attemptCount, 2);
        expect(_unixSeconds(sending.lastAttemptAt!), _unixSeconds(now));
        expect(sending.lastError, isNull);
        expect(sending.failureKind, isNull);
        expect(sending.httpStatusCode, isNull);
        _expectIdentity(sending, initial);
      },
    );

    test(
      'atomically requeues one retryable failure for concurrent callers',
      () async {
        final database = AppDatabase(NativeDatabase.memory());
        addTearDown(database.close);
        final repository = OutboxRepository(database);
        final event = await _enqueue(repository, eventUuid: 'atomic-requeue');
        await repository.markSending(organizationId: 1, id: event.id);
        await repository.markFailed(
          organizationId: 1,
          id: event.id,
          error: 'HTTP 503',
          failureKind: OutboxFailureKind.http,
          httpStatusCode: 503,
        );

        final results = await Future.wait([
          repository.requeueRetryableTechnicalFailure(
            organizationId: 1,
            id: event.id,
          ),
          repository.requeueRetryableTechnicalFailure(
            organizationId: 1,
            id: event.id,
          ),
        ]);

        expect(results.whereType<OutboxEvent>(), hasLength(1));
        final reloaded = await repository.findById(
          organizationId: 1,
          id: event.id,
        );
        expect(reloaded!.status, OutboxStatus.queued);
        expect(reloaded.attemptCount, 1);
        _expectIdentity(reloaded, event);
      },
    );

    test('restores an enqueued event after reopening a SQLite file', () async {
      final directory = await Directory.systemTemp.createTemp('yessal-outbox-');
      final file = File(
        '${directory.path}${Platform.pathSeparator}outbox.sqlite',
      );
      addTearDown(() async {
        if (await directory.exists()) await directory.delete(recursive: true);
      });
      final first = AppDatabase(NativeDatabase(file));
      final created = await _enqueue(
        OutboxRepository(first),
        eventUuid: 'event-restart',
      );
      await first.close();

      final reopened = AppDatabase(NativeDatabase(file));
      addTearDown(reopened.close);
      final restored = await OutboxRepository(reopened)
          .findByEventUuid(organizationId: 1, eventUuid: 'event-restart');

      expect(restored, isNotNull);
      expect(restored!.status, OutboxStatus.queued);
      expect(restored.attemptCount, created.attemptCount);
      expect(restored.payloadJson, created.payloadJson);
    });
  });
}

Future<OutboxEvent> _enqueue(
  OutboxRepository repository, {
  int organizationId = 1,
  String eventUuid = 'event',
}) => repository.enqueue(
  organizationId: organizationId,
  shopId: 10,
  deviceId: 20,
  eventUuid: eventUuid,
  entityType: 'sale',
  entityId: 'local-$eventUuid',
  action: 'create',
  payloadJson: '{"local_uuid":"local-$eventUuid","currency":"XOF"}',
  occurredAt: DateTime.utc(2026, 2, 1),
);

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

int _unixSeconds(DateTime value) =>
    value.toUtc().millisecondsSinceEpoch ~/ Duration.millisecondsPerSecond;
