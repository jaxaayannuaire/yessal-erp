import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:yessal_caisse/core/database/app_database.dart'
    hide Customer, Product, ProductVariant;
import 'package:yessal_caisse/core/database/local_repositories.dart';
import 'package:yessal_caisse/core/models/caisse_models.dart';
import 'package:yessal_caisse/features/catalogue/local_catalogue_screens.dart';

void main() {
  late AppDatabase database;
  late LocalScope scope;

  setUp(() async {
    database = AppDatabase(NativeDatabase.memory());
    scope = const LocalScope(organizationId: 1, shopId: 10, isOffline: true);
    await _seed(database);
  });

  tearDown(() => database.close());

  testWidgets('ProductListScreen displays and searches local products', (
    tester,
  ) async {
    await tester.pumpWidget(
      _app(ProductListScreen(database: database, scope: scope)),
    );
    await tester.pumpAndSettle();

    expect(find.text('Riz'), findsOneWidget);
    await tester.enterText(find.byType(TextField), 'RIZ-1');
    await tester.pump(const Duration(milliseconds: 350));
    await tester.pumpAndSettle();
    expect(find.text('Riz'), findsOneWidget);
  });

  testWidgets('ProductListScreen displays its empty state', (tester) async {
    await tester.pumpWidget(
      _app(
        ProductListScreen(
          database: database,
          scope: const LocalScope(organizationId: 1, shopId: 99),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(
      find.text('Aucun produit disponible pour cette boutique.'),
      findsOneWidget,
    );
  });

  testWidgets('ProductDetailScreen displays variants and separate stock', (
    tester,
  ) async {
    final product = Product.fromJson({
      'id': 100,
      'name': 'Riz',
      'sale_price': 1500,
      'shop_id': 10,
      'sku': 'RIZ-1',
    });
    await tester.pumpWidget(
      _app(
        ProductDetailScreen(database: database, scope: scope, product: product),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Sac 5 kg'), findsOneWidget);
    expect(find.textContaining('Stock produit : 12'), findsOneWidget);
  });

  testWidgets('CustomerListScreen displays local customers', (tester) async {
    await tester.pumpWidget(
      _app(CustomerListScreen(database: database, scope: scope)),
    );
    await tester.pumpAndSettle();

    expect(find.text('Awa Ndiaye'), findsOneWidget);
  });

  testWidgets('StockScreen displays local stock', (tester) async {
    await tester.pumpWidget(
      _app(StockScreen(database: database, scope: scope)),
    );
    await tester.pumpAndSettle();

    expect(find.text('Riz'), findsOneWidget);
    expect(find.text('12'), findsOneWidget);
  });
}

Widget _app(Widget child) => MaterialApp(home: child);

Future<void> _seed(AppDatabase database) {
  return BootstrapLocalRepository(database).replaceSnapshot(
    organizationId: 1,
    shopId: 10,
    entitlements: Entitlements.fromJson({
      'entitlements': [
        {'slug': 'pos.sell'},
      ],
    }),
    categories: [
      CaisseEntity(id: 1, name: 'Céréales', raw: {'id': 1, 'name': 'Céréales'}),
    ],
    products: [
      Product(
        id: 100,
        name: 'Riz',
        salePrice: 1500,
        raw: {
          'id': 100,
          'shop_id': 10,
          'category_id': 1,
          'name': 'Riz',
          'sku': 'RIZ-1',
          'barcode': '123456',
          'sale_price': 1500,
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
          'sale_price': 7000,
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
      },
      {
        'stock_location_id': 51,
        'shop_id': 10,
        'product_id': 100,
        'variant_id': 101,
        'quantity': 4,
      },
    ],
    sessions: const [],
  );
}
