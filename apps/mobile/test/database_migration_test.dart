import 'dart:io';

import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';
// Drift's SQLite executor depends on sqlite3; this fixture needs raw v2 DDL.
// ignore: depend_on_referenced_packages
import 'package:sqlite3/sqlite3.dart';
import 'package:yessal_caisse/core/database/app_database.dart';
import 'package:yessal_caisse/core/sync/outbox_repository.dart';
import 'package:yessal_caisse/core/sync/outbox_retry_policy.dart';

void main() {
  test('migrates a real schema v2 outbox row to v3 without data loss', () async {
    final directory = await Directory.systemTemp.createTemp('yessal-v2-v3-');
    final file = File(
      '${directory.path}${Platform.pathSeparator}outbox.sqlite',
    );
    addTearDown(() async {
      if (await directory.exists()) await directory.delete(recursive: true);
    });
    final occurredAt = DateTime.utc(2026, 9, 5, 10, 11, 12);
    final lastAttemptAt = DateTime.utc(2026, 9, 5, 10, 12, 12);
    final createdAt = DateTime.utc(2026, 9, 5, 10, 0);
    final updatedAt = DateTime.utc(2026, 9, 5, 10, 13);
    final payload =
        '{"local_uuid":"local-001","receipt_number":"OFF-001","currency":"XOF"}';
    final result = '{"status":"failed"}';

    final v2 = sqlite3.open(file.path);
    v2.execute('''
      CREATE TABLE sync_outbox (
        id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
        organization_id INTEGER NOT NULL, shop_id INTEGER NOT NULL,
        device_id INTEGER NOT NULL, event_uuid TEXT NOT NULL,
        entity_type TEXT NOT NULL, entity_id TEXT NOT NULL, action TEXT NOT NULL,
        payload_json TEXT NOT NULL, occurred_at INTEGER NOT NULL,
        status TEXT NOT NULL DEFAULT 'queued', attempt_count INTEGER NOT NULL DEFAULT 0,
        last_attempt_at INTEGER NULL, last_error TEXT NULL,
        server_result_json TEXT NULL, created_at INTEGER NOT NULL,
        updated_at INTEGER NOT NULL,
        UNIQUE (organization_id, event_uuid)
      )
    ''');
    v2.execute(
      'CREATE TABLE products (organization_id INTEGER, shop_id INTEGER, name TEXT, sku TEXT, barcode TEXT)',
    );
    v2.execute(
      'CREATE TABLE product_variants (organization_id INTEGER, product_id INTEGER, sku TEXT, barcode TEXT)',
    );
    v2.execute(
      'CREATE TABLE customers (organization_id INTEGER, shop_id INTEGER, name TEXT, phone TEXT)',
    );
    v2.execute(
      'CREATE TABLE stock_levels (organization_id INTEGER, shop_id INTEGER, product_id INTEGER, variant_id INTEGER)',
    );
    v2.execute('PRAGMA user_version = 2');
    v2.execute(
      'INSERT INTO sync_outbox (organization_id,shop_id,device_id,event_uuid,entity_type,entity_id,action,payload_json,occurred_at,status,attempt_count,last_attempt_at,last_error,server_result_json,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
      [
        11,
        22,
        33,
        '11111111-2222-3333-4444-555555555555',
        'sale',
        'local-sale-001',
        'create',
        payload,
        _unixSeconds(occurredAt),
        'failed',
        3,
        _unixSeconds(lastAttemptAt),
        'Erreur historique',
        result,
        _unixSeconds(createdAt),
        _unixSeconds(updatedAt),
      ],
    );
    v2.close();

    final database = AppDatabase(NativeDatabase(file));
    addTearDown(database.close);
    final event = await OutboxRepository(database).findByEventUuid(
      organizationId: 11,
      eventUuid: '11111111-2222-3333-4444-555555555555',
    );
    expect(database.schemaVersion, 3);
    expect(event, isNotNull);
    expect(event!.organizationId, 11);
    expect(event.shopId, 22);
    expect(event.deviceId, 33);
    expect(event.entityType, 'sale');
    expect(event.entityId, 'local-sale-001');
    expect(event.action, 'create');
    expect(event.payloadJson, payload);
    expect(_unixSeconds(event.occurredAt), _unixSeconds(occurredAt));
    expect(event.status, OutboxStatus.failed);
    expect(event.attemptCount, 3);
    expect(event.lastAttemptAt, isNotNull);
    expect(_unixSeconds(event.lastAttemptAt!), _unixSeconds(lastAttemptAt));
    expect(event.lastError, 'Erreur historique');
    expect(event.serverResultJson, result);
    expect(_unixSeconds(event.createdAt), _unixSeconds(createdAt));
    expect(_unixSeconds(event.updatedAt), _unixSeconds(updatedAt));
    expect(event.failureKind, isNull);
    expect(event.httpStatusCode, isNull);
    expect(const OutboxRetryPolicy().evaluate(event).allowed, isFalse);
  });

  test('creates a v3 outbox with nullable failure metadata', () async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final repository = OutboxRepository(database);
    final event = await repository.enqueue(
      organizationId: 1,
      shopId: 2,
      deviceId: 3,
      eventUuid: 'v3-event',
      entityType: 'sale',
      entityId: 'local-v3',
      action: 'create',
      payloadJson: '{"currency":"XOF"}',
      occurredAt: DateTime.utc(2026),
    );
    final columns = await database
        .customSelect('PRAGMA table_info(sync_outbox)')
        .get();
    expect(database.schemaVersion, 3);
    expect(
      columns.map((row) => row.read<String>('name')),
      containsAll(['failure_kind', 'http_status_code']),
    );
    expect(event.status, OutboxStatus.queued);
    expect(event.attemptCount, 0);
    expect(event.failureKind, isNull);
    expect(event.httpStatusCode, isNull);
    expect(event.organizationId, 1);
    expect(event.shopId, 2);
    expect(event.deviceId, 3);
    expect(event.eventUuid, 'v3-event');
    expect(event.payloadJson, '{"currency":"XOF"}');
  });
}

int _unixSeconds(DateTime value) =>
    value.toUtc().millisecondsSinceEpoch ~/ Duration.millisecondsPerSecond;
