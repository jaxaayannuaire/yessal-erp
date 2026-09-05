import 'package:drift/drift.dart';

import '../database/app_database.dart' as db;

/// Stateless values persisted by the local synchronization outbox.
enum OutboxStatus {
  queued,
  sending,
  applied,
  conflict,
  rejected,
  failed;

  String get databaseValue => name;

  static OutboxStatus fromDatabaseValue(String value) =>
      OutboxStatus.values.firstWhere(
        (status) => status.databaseValue == value,
        orElse: () => throw StateError('Statut outbox inconnu : $value'),
      );
}

enum OutboxFailureKind {
  network('network'),
  http('http'),
  localPayloadInvalid('local_payload_invalid'),
  protocolInvalid('protocol_invalid'),
  businessConflict('business_conflict'),
  businessRejected('business_rejected'),
  serverProcessing('server_processing');

  const OutboxFailureKind(this.databaseValue);
  final String databaseValue;

  static OutboxFailureKind? fromDatabaseValue(String? value) {
    for (final kind in OutboxFailureKind.values) {
      if (kind.databaseValue == value) return kind;
    }
    return null;
  }
}

/// Immutable representation of an outbox row outside of Drift generated types.
class OutboxEvent {
  const OutboxEvent({
    required this.id,
    required this.organizationId,
    required this.shopId,
    required this.deviceId,
    required this.eventUuid,
    required this.entityType,
    required this.entityId,
    required this.action,
    required this.payloadJson,
    required this.occurredAt,
    required this.status,
    required this.attemptCount,
    required this.lastAttemptAt,
    required this.lastError,
    required this.serverResultJson,
    required this.failureKind,
    required this.httpStatusCode,
    required this.createdAt,
    required this.updatedAt,
  });

  final int id;
  final int organizationId;
  final int shopId;
  final int deviceId;
  final String eventUuid;
  final String entityType;
  final String entityId;
  final String action;
  final String payloadJson;
  final DateTime occurredAt;
  final OutboxStatus status;
  final int attemptCount;
  final DateTime? lastAttemptAt;
  final String? lastError;
  final String? serverResultJson;
  final OutboxFailureKind? failureKind;
  final int? httpStatusCode;
  final DateTime createdAt;
  final DateTime updatedAt;

  factory OutboxEvent.fromRow(db.SyncOutboxData row) => OutboxEvent(
    id: row.id,
    organizationId: row.organizationId,
    shopId: row.shopId,
    deviceId: row.deviceId,
    eventUuid: row.eventUuid,
    entityType: row.entityType,
    entityId: row.entityId,
    action: row.action,
    payloadJson: row.payloadJson,
    occurredAt: row.occurredAt,
    status: OutboxStatus.fromDatabaseValue(row.status),
    attemptCount: row.attemptCount,
    lastAttemptAt: row.lastAttemptAt,
    lastError: row.lastError,
    serverResultJson: row.serverResultJson,
    failureKind: OutboxFailureKind.fromDatabaseValue(row.failureKind),
    httpStatusCode: row.httpStatusCode,
    createdAt: row.createdAt,
    updatedAt: row.updatedAt,
  );
}

/// Local-only access to the synchronization outbox.
///
/// Terminal statuses are retained for diagnostics and are intentionally not
/// returned by [listPending]. The repository does not perform HTTP requests.
class OutboxRepository {
  OutboxRepository(this._database, {DateTime Function()? clock})
    : _clock = clock ?? (() => DateTime.now().toUtc());

  final db.AppDatabase _database;
  final DateTime Function() _clock;

  Future<OutboxEvent> enqueue({
    required int organizationId,
    required int shopId,
    required int deviceId,
    required String eventUuid,
    required String entityType,
    required String entityId,
    required String action,
    required String payloadJson,
    required DateTime occurredAt,
  }) async {
    final now = _now();
    final id = await _database
        .into(_database.syncOutbox)
        .insert(
          db.SyncOutboxCompanion.insert(
            organizationId: organizationId,
            shopId: shopId,
            deviceId: deviceId,
            eventUuid: eventUuid,
            entityType: entityType,
            entityId: entityId,
            action: action,
            payloadJson: payloadJson,
            occurredAt: occurredAt.toUtc(),
            status: const Value('queued'),
            attemptCount: const Value(0),
            lastAttemptAt: const Value(null),
            lastError: const Value(null),
            serverResultJson: const Value(null),
            failureKind: const Value(null),
            httpStatusCode: const Value(null),
            createdAt: now,
            updatedAt: now,
          ),
        );
    return _required(organizationId, id);
  }

  Future<OutboxEvent?> findByEventUuid({
    required int organizationId,
    required String eventUuid,
  }) async {
    final row =
        await (_database.select(_database.syncOutbox)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  row.eventUuid.equals(eventUuid),
            ))
            .getSingleOrNull();
    return row == null ? null : OutboxEvent.fromRow(row);
  }

  Future<OutboxEvent?> findById({
    required int organizationId,
    required int id,
  }) async {
    final row =
        await (_database.select(_database.syncOutbox)..where(
              (row) =>
                  row.organizationId.equals(organizationId) & row.id.equals(id),
            ))
            .getSingleOrNull();
    return row == null ? null : OutboxEvent.fromRow(row);
  }

  Future<List<OutboxEvent>> listByOrganization(int organizationId) async {
    final rows =
        await (_database.select(_database.syncOutbox)
              ..where((row) => row.organizationId.equals(organizationId))
              ..orderBy([
                (row) => OrderingTerm.asc(row.createdAt),
                (row) => OrderingTerm.asc(row.id),
              ]))
            .get();
    return rows.map(OutboxEvent.fromRow).toList();
  }

  /// Events eligible for the MVP's automatic replay: queued only.
  Future<List<OutboxEvent>> listPending(int organizationId) async {
    final rows =
        await (_database.select(_database.syncOutbox)
              ..where(
                (row) =>
                    row.organizationId.equals(organizationId) &
                    row.status.equals(OutboxStatus.queued.databaseValue),
              )
              ..orderBy([
                (row) => OrderingTerm.asc(row.createdAt),
                (row) => OrderingTerm.asc(row.id),
              ]))
            .get();
    return rows.map(OutboxEvent.fromRow).toList();
  }

  /// Terminal events that require human review. They remain immutable here.
  Future<List<OutboxEvent>> listIssues(int organizationId) async {
    final rows =
        await (_database.select(_database.syncOutbox)
              ..where(
                (row) =>
                    row.organizationId.equals(organizationId) &
                    (row.status.equals(OutboxStatus.conflict.databaseValue) |
                        row.status.equals(OutboxStatus.rejected.databaseValue) |
                        row.status.equals(OutboxStatus.failed.databaseValue)),
              )
              ..orderBy([
                (row) => OrderingTerm.desc(row.updatedAt),
                (row) => OrderingTerm.desc(row.id),
              ]))
            .get();
    return rows.map(OutboxEvent.fromRow).toList();
  }

  /// Counts automatic-retry candidates. `sending` is deliberately excluded.
  Future<int> countPending(int organizationId) async {
    final count = _database.syncOutbox.id.count();
    final query = _database.selectOnly(_database.syncOutbox)
      ..addColumns([count])
      ..where(
        _database.syncOutbox.organizationId.equals(organizationId) &
            _database.syncOutbox.status.equals(
              OutboxStatus.queued.databaseValue,
            ),
      );
    return (await query.getSingle()).read(count) ?? 0;
  }

  /// Atomically claims an event for a future network attempt.
  Future<OutboxEvent?> markSending({
    required int organizationId,
    required int id,
  }) async {
    final now = _now();
    final changed = await _database.customUpdate(
      'UPDATE sync_outbox '
      'SET status = ?, attempt_count = attempt_count + 1, '
      'last_attempt_at = ?, last_error = NULL, updated_at = ? '
      ', failure_kind = NULL, http_status_code = NULL '
      'WHERE id = ? AND organization_id = ? AND status = ?',
      variables: [
        Variable.withString(OutboxStatus.sending.databaseValue),
        Variable.withDateTime(now),
        Variable.withDateTime(now),
        Variable.withInt(id),
        Variable.withInt(organizationId),
        Variable.withString(OutboxStatus.queued.databaseValue),
      ],
    );
    if (changed == 0) return null;
    return _required(organizationId, id);
  }

  Future<OutboxEvent?> markQueuedAfterNetworkFailure({
    required int organizationId,
    required int id,
    required String error,
  }) => _updateStatus(
    organizationId: organizationId,
    id: id,
    from: OutboxStatus.sending,
    status: OutboxStatus.queued,
    lastError: error,
    failureKind: OutboxFailureKind.network,
  );

  Future<OutboxEvent?> markApplied({
    required int organizationId,
    required int id,
    required String serverResultJson,
  }) => _updateStatus(
    organizationId: organizationId,
    id: id,
    from: OutboxStatus.sending,
    status: OutboxStatus.applied,
    lastError: null,
    serverResultJson: serverResultJson,
    failureKind: null,
  );

  Future<OutboxEvent?> markConflict({
    required int organizationId,
    required int id,
    required String error,
    String? serverResultJson,
  }) => _updateStatus(
    organizationId: organizationId,
    id: id,
    from: OutboxStatus.sending,
    status: OutboxStatus.conflict,
    lastError: error,
    serverResultJson: serverResultJson,
    failureKind: OutboxFailureKind.businessConflict,
  );

  Future<OutboxEvent?> markRejected({
    required int organizationId,
    required int id,
    required String error,
    String? serverResultJson,
  }) => _updateStatus(
    organizationId: organizationId,
    id: id,
    from: OutboxStatus.sending,
    status: OutboxStatus.rejected,
    lastError: error,
    serverResultJson: serverResultJson,
    failureKind: OutboxFailureKind.businessRejected,
  );

  Future<OutboxEvent?> markFailed({
    required int organizationId,
    required int id,
    required String error,
    String? serverResultJson,
    OutboxFailureKind? failureKind,
    int? httpStatusCode,
  }) => _updateStatus(
    organizationId: organizationId,
    id: id,
    from: OutboxStatus.sending,
    status: OutboxStatus.failed,
    lastError: error,
    serverResultJson: serverResultJson,
    failureKind: failureKind,
    httpStatusCode: httpStatusCode,
  );

  Future<OutboxEvent?> requeueRetryableTechnicalFailure({
    required int organizationId,
    required int id,
  }) async {
    final now = _now();
    final changed = await _database.customUpdate(
      'UPDATE sync_outbox SET status = ?, updated_at = ? '
      'WHERE id = ? AND organization_id = ? AND status = ? '
      'AND failure_kind = ? AND (http_status_code = ? OR http_status_code = ? '
      'OR (http_status_code >= ? AND http_status_code <= ?))',
      variables: [
        Variable.withString(OutboxStatus.queued.databaseValue),
        Variable.withDateTime(now),
        Variable.withInt(id),
        Variable.withInt(organizationId),
        Variable.withString(OutboxStatus.failed.databaseValue),
        Variable.withString(OutboxFailureKind.http.databaseValue),
        Variable.withInt(408),
        Variable.withInt(429),
        Variable.withInt(500),
        Variable.withInt(599),
      ],
    );
    if (changed == 0) return null;
    return _required(organizationId, id);
  }

  /// Releases events left in `sending` by an interrupted application process.
  Future<int> recoverInterruptedSending(int organizationId) {
    final now = _now();
    return (_database.update(_database.syncOutbox)..where(
          (row) =>
              row.organizationId.equals(organizationId) &
              row.status.equals(OutboxStatus.sending.databaseValue),
        ))
        .write(
          db.SyncOutboxCompanion(
            status: const Value('queued'),
            lastError: const Value(
              'Synchronisation interrompue avant confirmation.',
            ),
            updatedAt: Value(now),
          ),
        );
  }

  Future<OutboxEvent?> _updateStatus({
    required int organizationId,
    required int id,
    required OutboxStatus from,
    required OutboxStatus status,
    required String? lastError,
    String? serverResultJson,
    OutboxFailureKind? failureKind,
    int? httpStatusCode,
  }) async {
    final now = _now();
    final changed =
        await (_database.update(_database.syncOutbox)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  row.id.equals(id) &
                  row.status.equals(from.databaseValue),
            ))
            .write(
              db.SyncOutboxCompanion(
                status: Value(status.databaseValue),
                lastError: Value(lastError),
                serverResultJson: Value(serverResultJson),
                failureKind: Value(failureKind?.databaseValue),
                httpStatusCode: Value(httpStatusCode),
                updatedAt: Value(now),
              ),
            );
    if (changed == 0) return null;
    return _required(organizationId, id);
  }

  Future<OutboxEvent> _required(int organizationId, int id) async =>
      (await findById(organizationId: organizationId, id: id))!;

  DateTime _now() => _clock().toUtc();
}
