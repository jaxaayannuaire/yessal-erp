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

  test('searches customers by name and phone', () async {
    await _writeSnapshot(local);
    final repository = CustomerLocalRepository(database);

    expect((await repository.searchCustomers('awa', 1, 10)).single.id, 200);
    expect(
      (await repository.searchCustomers('7700', 1, 10)).single.name,
      'Awa Ndiaye',
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
}

Future<void> _writeSnapshot(
  BootstrapLocalRepository local, {
  int organizationId = 1,
  String productName = 'Riz',
}) {
  return local.replaceSnapshot(
    organizationId: organizationId,
    shopId: 10,
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
        id: 100,
        name: productName,
        salePrice: 1500,
        raw: {
          'id': 100,
          'name': productName,
          'shop_id': 10,
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
        id: 101,
        productId: 100,
        name: 'Sac 5 kg',
        salePrice: 7000,
        raw: {
          'id': 101,
          'product_id': 100,
          'shop_id': 10,
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
          'shop_id': 10,
          'name': 'Awa Ndiaye',
          'phone': '770000000',
          'email': 'awa@example.test',
          'status': 'active',
        },
      ),
    ],
    stock: [
      {
        'stock_location_id': 50,
        'shop_id': 10,
        'product_id': 100,
        'quantity': 12,
        'reserved_quantity': 1,
      },
    ],
    sessions: [
      {'id': 300, 'shop_id': 10, 'terminal_id': 20, 'status': 'open'},
    ],
  );
}
