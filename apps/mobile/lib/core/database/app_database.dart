import 'dart:convert';

import 'package:drift/drift.dart';
import 'package:drift_flutter/drift_flutter.dart';

part 'app_database.g.dart';

class OrganizationsCache extends Table {
  IntColumn get organizationId => integer()();
  TextColumn get name => text()();

  @override
  Set<Column<Object>> get primaryKey => {organizationId};
}

class EntitlementsCache extends Table {
  IntColumn get organizationId => integer()();
  TextColumn get slug => text()();
  TextColumn get rawJson => text()();

  @override
  Set<Column<Object>> get primaryKey => {organizationId, slug};
}

class Categories extends Table {
  IntColumn get organizationId => integer()();
  IntColumn get id => integer()();
  IntColumn get shopId => integer().nullable()();
  TextColumn get name => text()();
  TextColumn get status => text().nullable()();
  TextColumn get rawJson => text()();

  @override
  Set<Column<Object>> get primaryKey => {organizationId, id};
}

class Products extends Table {
  IntColumn get organizationId => integer()();
  IntColumn get id => integer()();
  IntColumn get shopId => integer()();
  IntColumn get categoryId => integer().nullable()();
  TextColumn get name => text()();
  TextColumn get sku => text().nullable()();
  TextColumn get barcode => text().nullable()();
  IntColumn get salePrice => integer()();
  IntColumn get purchasePrice => integer().nullable()();
  TextColumn get status => text().nullable()();
  TextColumn get rawJson => text()();

  @override
  Set<Column<Object>> get primaryKey => {organizationId, id};
}

class ProductVariants extends Table {
  IntColumn get organizationId => integer()();
  IntColumn get id => integer()();
  IntColumn get shopId => integer()();
  IntColumn get productId => integer()();
  TextColumn get name => text()();
  TextColumn get sku => text().nullable()();
  TextColumn get barcode => text().nullable()();
  IntColumn get salePrice => integer()();
  IntColumn get purchasePrice => integer().nullable()();
  TextColumn get attributesJson => text().nullable()();
  TextColumn get rawJson => text()();

  @override
  Set<Column<Object>> get primaryKey => {organizationId, id};
}

class Customers extends Table {
  IntColumn get organizationId => integer()();
  IntColumn get id => integer()();
  IntColumn get shopId => integer().nullable()();
  TextColumn get name => text()();
  TextColumn get phone => text().nullable()();
  TextColumn get email => text().nullable()();
  TextColumn get status => text().nullable()();
  TextColumn get rawJson => text()();

  @override
  Set<Column<Object>> get primaryKey => {organizationId, id};
}

class StockLevels extends Table {
  IntColumn get organizationId => integer()();
  IntColumn get shopId => integer()();
  IntColumn get stockLocationId => integer()();
  TextColumn get stockIdentity => text()();
  IntColumn get productId => integer().nullable()();
  IntColumn get variantId => integer().nullable()();
  RealColumn get quantity => real()();
  RealColumn get reservedQuantity => real().nullable()();
  TextColumn get rawJson => text()();

  @override
  Set<Column<Object>> get primaryKey => {
    organizationId,
    stockLocationId,
    stockIdentity,
  };
}

class CashSessions extends Table {
  IntColumn get organizationId => integer()();
  IntColumn get id => integer()();
  IntColumn get shopId => integer().nullable()();
  IntColumn get terminalId => integer().nullable()();
  TextColumn get status => text().nullable()();
  TextColumn get rawJson => text()();

  @override
  Set<Column<Object>> get primaryKey => {organizationId, id};
}

class SyncMetadata extends Table {
  IntColumn get organizationId => integer()();
  IntColumn get lastCursor => integer().nullable()();
  DateTimeColumn get lastSyncAt => dateTime().nullable()();

  @override
  Set<Column<Object>> get primaryKey => {organizationId};
}

class BootstrapMetadata extends Table {
  IntColumn get organizationId => integer()();
  IntColumn get shopId => integer()();
  DateTimeColumn get lastBootstrapAt => dateTime()();

  @override
  Set<Column<Object>> get primaryKey => {organizationId, shopId};
}

class SyncOutbox extends Table {
  IntColumn get id => integer().autoIncrement()();
  IntColumn get organizationId => integer()();
  IntColumn get shopId => integer()();
  IntColumn get deviceId => integer()();
  TextColumn get eventUuid => text()();
  TextColumn get entityType => text()();
  TextColumn get entityId => text()();
  TextColumn get action => text()();
  TextColumn get payloadJson => text()();
  DateTimeColumn get occurredAt => dateTime()();
  TextColumn get status => text().withDefault(const Constant('queued'))();
  IntColumn get attemptCount => integer().withDefault(const Constant(0))();
  DateTimeColumn get lastAttemptAt => dateTime().nullable()();
  TextColumn get lastError => text().nullable()();
  TextColumn get serverResultJson => text().nullable()();
  DateTimeColumn get createdAt => dateTime()();
  DateTimeColumn get updatedAt => dateTime()();

  @override
  List<Set<Column<Object>>> get uniqueKeys => [
    {organizationId, eventUuid},
  ];
}

@DriftDatabase(
  tables: [
    OrganizationsCache,
    EntitlementsCache,
    Categories,
    Products,
    ProductVariants,
    Customers,
    StockLevels,
    CashSessions,
    SyncMetadata,
    BootstrapMetadata,
    SyncOutbox,
  ],
)
class AppDatabase extends _$AppDatabase {
  AppDatabase(super.executor);

  AppDatabase.defaults()
    : super(
        driftDatabase(
          name: 'yessal_caisse',
          web: DriftWebOptions(
            sqlite3Wasm: Uri.parse('sqlite3.wasm'),
            driftWorker: Uri.parse('drift_worker.js'),
          ),
        ),
      );

  @override
  int get schemaVersion => 2;

  @override
  MigrationStrategy get migration => MigrationStrategy(
    onCreate: (migrator) async {
      await migrator.createAll();
      await _createIndexes();
    },
    onUpgrade: (migrator, from, to) async {
      if (from < 2) await migrator.createTable(syncOutbox);
    },
    beforeOpen: (details) async {
      await customStatement('PRAGMA foreign_keys = ON');
      await _createIndexes();
    },
  );

  Future<void> _createIndexes() async {
    await customStatement(
      'CREATE INDEX IF NOT EXISTS products_lookup '
      'ON products (organization_id, shop_id, name, sku, barcode)',
    );
    await customStatement(
      'CREATE INDEX IF NOT EXISTS sync_outbox_status ON sync_outbox (organization_id, status)',
    );
    await customStatement(
      'CREATE INDEX IF NOT EXISTS sync_outbox_created ON sync_outbox (organization_id, created_at)',
    );
    await customStatement(
      'CREATE INDEX IF NOT EXISTS variants_lookup '
      'ON product_variants (organization_id, product_id, sku, barcode)',
    );
    await customStatement(
      'CREATE INDEX IF NOT EXISTS customers_lookup '
      'ON customers (organization_id, shop_id, name, phone)',
    );
    await customStatement(
      'CREATE INDEX IF NOT EXISTS stock_lookup '
      'ON stock_levels (organization_id, shop_id, product_id, variant_id)',
    );
  }
}

String encodeRaw(Map<String, dynamic> value) => jsonEncode(value);

Map<String, dynamic> decodeRaw(String value) =>
    (jsonDecode(value) as Map).cast<String, dynamic>();
