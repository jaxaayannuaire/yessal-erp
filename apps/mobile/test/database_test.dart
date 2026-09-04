import 'dart:io';

import 'package:drift/drift.dart' hide isNull, isNotNull;
import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:yessal_caisse/core/database/app_database.dart'
    hide Customer, Product, ProductVariant;
import 'package:yessal_caisse/core/database/local_repositories.dart';
import 'package:yessal_caisse/core/models/caisse_models.dart';

void main() {
  late AppDatabase database;
  late BootstrapLocalRepository local;

  setUp(() {
    database = AppDatabase(NativeDatabase.memory());
    local = BootstrapLocalRepository(database);
  });

  tearDown(() => database.close());

  test('opens an in-memory Drift database', () async {
    expect(await database.select(database.products).get(), isEmpty);
  });

  test('inserts a complete bootstrap snapshot', () async {
    await _writeSnapshot(local);

    expect(await database.select(database.products).get(), hasLength(1));
    expect(
      await database.select(database.bootstrapMetadata).get(),
      hasLength(1),
    );
  });

  test('isolates snapshots by organization', () async {
    await _writeSnapshot(local, organizationId: 1);
    await _writeSnapshot(local, organizationId: 2);

    expect(
      (await local.readSnapshot(
        organizationId: 1,
        shopId: 10,
      ))!.products.single.id,
      100,
    );
    expect(
      (await local.readSnapshot(
        organizationId: 2,
        shopId: 10,
      ))!.products.single.id,
      100,
    );
    expect(await database.select(database.products).get(), hasLength(2));
  });

  test('replaces only the selected shop snapshot', () async {
    await _writeSnapshot(local);
    await _writeSnapshot(local, productName: 'Mil');

    final products = await CatalogueLocalRepository(database)
        .searchProducts('', 1, 10);
    expect(products.single.name, 'Mil');
  });

  test('persists products with searchable fields', () async {
    await _writeSnapshot(local);

    final product = (await local.readSnapshot(
      organizationId: 1,
      shopId: 10,
    ))!.products.single;
    expect(product.toJson()['sku'], 'RIZ-1');
  });

  test('persists variants globally for a product', () async {
    await _writeSnapshot(local);

    final variants = await CatalogueLocalRepository(database)
        .variantsForProduct(1, 100);
    expect(variants.single.name, 'Sac 5 kg');
  });

  test('persists customers', () async {
    await _writeSnapshot(local);

    expect(
      (await local.readSnapshot(
        organizationId: 1,
        shopId: 10,
      ))!.customers.single.phone,
      '770000000',
    );
  });

  test('persists stock by product and shop', () async {
    await _writeSnapshot(local);

    final stock = await StockLocalRepository(database)
        .forProduct(organizationId: 1, shopId: 10, productId: 100);
    expect(stock.single['quantity'], 12);
  });

  test('searches products by name', () async {
    await _writeSnapshot(local);

    expect(
      (await CatalogueLocalRepository(
        database,
      ).searchProducts('riz', 1, 10)).single.id,
      100,
    );
  });

  test('searches products by SKU', () async {
    await _writeSnapshot(local);

    expect(
      (await CatalogueLocalRepository(
        database,
      ).searchProducts('RIZ-1', 1, 10)).single.name,
      'Riz',
    );
  });

  test('searches products by barcode', () async {
    await _writeSnapshot(local);

    expect(
      (await CatalogueLocalRepository(
        database,
      ).searchProducts('123456', 1, 10)).single.id,
      100,
    );
  });

  test('filters products strictly by shop', () async {
    await _writeSnapshot(local, shopId: 10, productName: 'Riz boutique 10');
    await _writeSnapshot(
      local,
      shopId: 11,
      productId: 102,
      productName: 'Riz boutique 11',
    );

    final products = await CatalogueLocalRepository(database)
        .searchProducts('', 1, 10);
    expect(products.single.name, 'Riz boutique 10');
  });

  test('filters products by category', () async {
    await _writeSnapshot(local);
    expect(
      await CatalogueLocalRepository(database)
          .searchProducts('', 1, 10, categoryId: 99),
      isEmpty,
    );
  });

  test('searches customers by name and phone', () async {
    await _writeSnapshot(local);
    final repository = CustomerLocalRepository(database);

    expect((await repository.searchCustomers('awa', 1, 10)).single.id, 200);
    expect(
      (await repository.searchCustomers('7700', 1, 10)).single.name,
      'Awa Ndiaye',
    );
  });

  test('searches customers by email', () async {
    await _writeSnapshot(local);
    expect(
      (await CustomerLocalRepository(
        database,
      ).searchCustomers('example.test', 1, 10)).single.id,
      200,
    );
  });

  test('keeps product and variant stock aggregation separate', () async {
    await _writeSnapshot(
      local,
      stock: [
        {
          'stock_location_id': 50,
          'shop_id': 10,
          'product_id': 100,
          'quantity': 12,
        },
        {
          'stock_location_id': 51,
          'shop_id': 10,
          'product_id': 100,
          'variant_id': 101,
          'quantity': 4,
        },
        {
          'stock_location_id': 52,
          'shop_id': 10,
          'product_id': 100,
          'variant_id': 101,
          'quantity': 3,
        },
      ],
    );
    final repository = StockLocalRepository(database);
    expect(
      await repository.productQuantity(
        organizationId: 1,
        shopId: 10,
        productId: 100,
      ),
      12,
    );
    expect(
      await repository.variantQuantity(
        organizationId: 1,
        shopId: 10,
        variantId: 101,
      ),
      7,
    );
    expect(
      await CatalogueLocalRepository(database).productStockById(1, 10, [100]),
      {100: 12},
    );
  });

  test('stores sync cursor per organization', () async {
    await local.updateCursor(1, 42);
    await local.updateCursor(2, 99);

    expect(await local.readCursor(1), 42);
    expect(await local.readCursor(2), 99);
  });

  test(
    'reads a complete offline snapshot only after metadata is written',
    () async {
      await _writeSnapshot(local);

      expect(
        await local.readSnapshot(organizationId: 1, shopId: 10),
        isNotNull,
      );
      expect(await local.readSnapshot(organizationId: 1, shopId: 11), isNull);
    },
  );

  test('rolls back an incomplete bootstrap transaction', () async {
    final failing = BootstrapLocalRepository(
      database,
      afterSnapshotWrite: () => Future<void>.error(StateError('write failed')),
    );

    await expectLater(_writeSnapshot(failing), throwsStateError);
    expect(await database.select(database.products).get(), isEmpty);
    expect(await local.readSnapshot(organizationId: 1, shopId: 10), isNull);
  });

  test('stores tenant-isolated queued sync outbox events', () async {
    final now = DateTime.utc(2026, 1, 1);
    final payload =
        '{"terminal_id":20,"cash_session_id":30,"local_uuid":"sale-1","receipt_number":"MOB-1","currency":"XOF","lines":[],"payment":{"method":"cash","amount":0},"finalize":true}';
    final id = await database
        .into(database.syncOutbox)
        .insert(
          SyncOutboxCompanion.insert(
            organizationId: 1,
            shopId: 10,
            deviceId: 20,
            eventUuid: 'event-1',
            entityType: 'sale',
            entityId: 'sale-1',
            action: 'create',
            payloadJson: payload,
            occurredAt: now,
            createdAt: now,
            updatedAt: now,
          ),
        );
    final row = await (database.select(
      database.syncOutbox,
    )..where((r) => r.id.equals(id))).getSingle();
    expect(row.status, 'queued');
    expect(row.attemptCount, 0);
    expect(row.payloadJson, payload);
    await expectLater(
      database
          .into(database.syncOutbox)
          .insert(
            SyncOutboxCompanion.insert(
              organizationId: 1,
              shopId: 10,
              deviceId: 20,
              eventUuid: 'event-1',
              entityType: 'sale',
              entityId: 'sale-2',
              action: 'create',
              payloadJson: payload,
              occurredAt: now,
              createdAt: now,
              updatedAt: now,
            ),
          ),
      throwsA(isA<Exception>()),
    );
    await database
        .into(database.syncOutbox)
        .insert(
          SyncOutboxCompanion.insert(
            organizationId: 2,
            shopId: 10,
            deviceId: 20,
            eventUuid: 'event-1',
            entityType: 'sale',
            entityId: 'sale-1',
            action: 'create',
            payloadJson: payload,
            occurredAt: now,
            createdAt: now,
            updatedAt: now,
          ),
        );
    expect(await database.select(database.syncOutbox).get(), hasLength(2));
  });

  test(
    'persists a sync outbox event after reopening the same SQLite file',
    () async {
      await database.close();
      final directory = await Directory.systemTemp.createTemp('yessal-outbox-');
      final file = File(
        '${directory.path}${Platform.pathSeparator}outbox.sqlite',
      );
      final now = DateTime.utc(2026, 1, 2);
      final executorA = NativeDatabase(file);
      final first = AppDatabase(executorA);
      await first
          .into(first.syncOutbox)
          .insert(
            SyncOutboxCompanion.insert(
              organizationId: 1,
              shopId: 10,
              deviceId: 20,
              eventUuid: 'event-reopen',
              entityType: 'sale',
              entityId: 'sale-reopen',
              action: 'create',
              payloadJson: '{"local_uuid":"sale-reopen"}',
              occurredAt: now,
              createdAt: now,
              updatedAt: now,
            ),
          );
      await first.close();
      final executorB = NativeDatabase(file);
      final reopened = AppDatabase(executorB);
      final row = await (reopened.select(
        reopened.syncOutbox,
      )..where((r) => r.eventUuid.equals('event-reopen'))).getSingle();
      expect(row.organizationId, 1);
      expect(row.shopId, 10);
      expect(row.deviceId, 20);
      expect(row.entityId, 'sale-reopen');
      expect(row.payloadJson, '{"local_uuid":"sale-reopen"}');
      expect(row.status, 'queued');
      expect(row.attemptCount, 0);
      await reopened.close();
      await directory.delete(recursive: true);
    },
  );

  test('keeps existing tables usable alongside sync outbox', () async {
    await database
        .into(database.products)
        .insert(
          ProductsCompanion.insert(
            organizationId: 1,
            id: 9,
            shopId: 10,
            name: 'Produit existant',
            salePrice: 100,
            rawJson: '{}',
          ),
        );
    final now = DateTime.utc(2026, 1, 3);
    await database
        .into(database.syncOutbox)
        .insert(
          SyncOutboxCompanion.insert(
            organizationId: 1,
            shopId: 10,
            deviceId: 20,
            eventUuid: 'event-existing',
            entityType: 'sale',
            entityId: 'sale-existing',
            action: 'create',
            payloadJson: '{}',
            occurredAt: now,
            createdAt: now,
            updatedAt: now,
          ),
        );
    expect(await database.select(database.products).get(), hasLength(1));
    expect(await database.select(database.syncOutbox).get(), hasLength(1));
  });

  test('updates outbox status and nullable error/result fields', () async {
    final now = DateTime.utc(2026, 1, 4);
    final id = await database
        .into(database.syncOutbox)
        .insert(
          SyncOutboxCompanion.insert(
            organizationId: 1,
            shopId: 10,
            deviceId: 20,
            eventUuid: 'event-status',
            entityType: 'sale',
            entityId: 'sale-status',
            action: 'create',
            payloadJson: '{}',
            occurredAt: now,
            createdAt: now,
            updatedAt: now,
          ),
        );
    var row = await (database.select(
      database.syncOutbox,
    )..where((r) => r.id.equals(id))).getSingle();
    expect(row.lastError, isNull);
    expect(row.serverResultJson, isNull);
    await (database.update(
      database.syncOutbox,
    )..where((r) => r.id.equals(id))).write(
      SyncOutboxCompanion(
        status: const Value('sending'),
        attemptCount: const Value(1),
        lastAttemptAt: Value(now),
      ),
    );
    row = await (database.select(
      database.syncOutbox,
    )..where((r) => r.id.equals(id))).getSingle();
    expect(row.status, 'sending');
    expect(row.attemptCount, 1);
    expect(row.lastAttemptAt!.toUtc(), now);
    await (database.update(
      database.syncOutbox,
    )..where((r) => r.id.equals(id))).write(
      const SyncOutboxCompanion(
        status: Value('applied'),
        lastError: Value('none'),
        serverResultJson: Value('{"sale_id":123,"status":"finalized"}'),
      ),
    );
    row = await (database.select(
      database.syncOutbox,
    )..where((r) => r.id.equals(id))).getSingle();
    expect(row.status, 'applied');
    expect(row.lastError, 'none');
    expect(row.serverResultJson, contains('sale_id'));
  });
}

Future<void> _writeSnapshot(
  BootstrapLocalRepository local, {
  int organizationId = 1,
  int shopId = 10,
  int productId = 100,
  String productName = 'Riz',
  List<Map<String, dynamic>>? stock,
}) {
  return local.replaceSnapshot(
    organizationId: organizationId,
    shopId: shopId,
    entitlements: Entitlements.fromJson({
      'entitlements': [
        {'slug': 'pos.sell'},
      ],
    }),
    categories: [
      CaisseEntity(
        id: 1,
        name: 'Céréales',
        raw: {'id': 1, 'name': 'Céréales', 'shop_id': 10},
      ),
    ],
    products: [
      Product(
        id: productId,
        name: productName,
        salePrice: 1500,
        raw: {
          'id': productId,
          'name': productName,
          'shop_id': shopId,
          'category_id': 1,
          'sku': 'RIZ-1',
          'barcode': '123456',
          'sale_price': 1500,
          'purchase_price': 1200,
          'status': 'active',
        },
      ),
    ],
    variants: [
      ProductVariant(
        id: productId + 1,
        productId: productId,
        name: 'Sac 5 kg',
        salePrice: 7000,
        raw: {
          'id': productId + 1,
          'product_id': productId,
          'shop_id': shopId,
          'name': 'Sac 5 kg',
          'sku': 'RIZ-5KG',
          'barcode': '654321',
          'sale_price': 7000,
          'attributes': {'weight': '5kg'},
        },
      ),
    ],
    customers: [
      Customer(
        id: 200,
        name: 'Awa Ndiaye',
        phone: '770000000',
        email: 'awa@example.test',
        raw: {
          'id': 200,
          'shop_id': shopId,
          'name': 'Awa Ndiaye',
          'phone': '770000000',
          'email': 'awa@example.test',
          'status': 'active',
        },
      ),
    ],
    stock:
        stock ??
        [
          {
            'stock_location_id': 50,
            'shop_id': shopId,
            'product_id': productId,
            'quantity': 12,
            'reserved_quantity': 1,
          },
        ],
    sessions: [
      {'id': 300, 'shop_id': shopId, 'terminal_id': 20, 'status': 'open'},
    ],
  );
}
