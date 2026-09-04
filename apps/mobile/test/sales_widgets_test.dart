import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:yessal_caisse/app/app_session.dart';
import 'package:yessal_caisse/core/api/api_client.dart';
import 'package:yessal_caisse/core/auth/auth_repository.dart';
import 'package:yessal_caisse/core/bootstrap/bootstrap_repository.dart';
import 'package:yessal_caisse/core/database/app_database.dart'
    hide Customer, Product, ProductVariant;
import 'package:yessal_caisse/core/database/local_repositories.dart';
import 'package:yessal_caisse/core/errors/api_exception.dart';
import 'package:yessal_caisse/core/models/caisse_models.dart';
import 'package:yessal_caisse/core/storage/local_cache_store.dart';
import 'package:yessal_caisse/core/storage/token_storage.dart';
import 'package:yessal_caisse/features/sales/cart.dart';
import 'package:yessal_caisse/features/sales/sale_controller.dart';
import 'package:yessal_caisse/features/sales/sale_repository.dart';
import 'package:yessal_caisse/features/sales/sales_screen.dart';

void main() {
  testWidgets('SalesScreen adds a simple local product to its cart', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    await _seed(database, variants: const []);
    await tester.pumpWidget(
      _app(
        SalesScreen(
          session: _session(database, offline: false),
          database: database,
          repository: _WidgetSales(),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Vente cash'), findsOneWidget);
    expect(find.text('Rechercher un produit'), findsOneWidget);
    await tester.tap(find.text('Ajouter'));
    await tester.pump();
    await tester.tap(find.byIcon(Icons.shopping_cart));
    await tester.pumpAndSettle();
    expect(find.text('Riz'), findsOneWidget);
    expect(find.text('1'), findsOneWidget);
  });

  testWidgets('SalesScreen requires a variant and keeps variants distinct', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    await _seed(database);
    await tester.pumpWidget(
      _app(
        SalesScreen(
          session: _session(database, offline: false),
          database: database,
          repository: _WidgetSales(),
        ),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('Ajouter'));
    await tester.pumpAndSettle();
    expect(find.text('Sac 5 kg'), findsOneWidget);
    await tester.tap(find.text('Sac 5 kg'));
    await tester.pumpAndSettle();
    await tester.tap(find.byIcon(Icons.shopping_cart));
    await tester.pumpAndSettle();
    expect(find.textContaining('Sac 5 kg'), findsOneWidget);
    expect(find.text('Riz'), findsNothing);
  });

  testWidgets(
    'CartScreen shows lines, quantity, total and optional customer action',
    (tester) async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      final cart = CartState()
        ..add(
          const CartItem(
            productId: 1,
            variantId: null,
            name: 'Riz',
            unitPrice: 1500,
            quantity: 2,
          ),
        );
      await tester.pumpWidget(
        _app(
          CartScreen(
            session: _session(database, offline: true),
            database: database,
            cart: cart,
            repository: SaleRepository(ApiClient()),
            controller: SaleController(SaleRepository(ApiClient()))
              ..beginAttempt(),
            onSuccess: () async {},
          ),
        ),
      );
      expect(find.text('Riz'), findsOneWidget);
      expect(find.textContaining('3 000 FCFA'), findsWidgets);
      expect(find.text('Vente comptoir / choisir un client'), findsOneWidget);
    },
  );

  testWidgets('offline CartScreen blocks submission and keeps the cart', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final cart = CartState()
      ..add(
        const CartItem(
          productId: 1,
          variantId: null,
          name: 'Riz',
          unitPrice: 1000,
          quantity: 1,
        ),
      );
    await tester.pumpWidget(
      _app(
        CartScreen(
          session: _session(database, offline: true),
          database: database,
          cart: cart,
          repository: SaleRepository(ApiClient()),
          controller: SaleController(SaleRepository(ApiClient()))
            ..beginAttempt(),
          onSuccess: () async {},
        ),
      ),
    );
    await tester.tap(find.text('Payer comptant'));
    await tester.pump();
    expect(
      find.text('Connexion requise pour enregistrer cette vente.'),
      findsOneWidget,
    );
    expect(cart.items, hasLength(1));
  });

  testWidgets('CartScreen blocks submission when no session is open', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final cart = CartState()
      ..add(
        const CartItem(
          productId: 1,
          variantId: null,
          name: 'Riz',
          unitPrice: 1000,
          quantity: 1,
        ),
      );
    final repository = _WidgetSales(sessionId: null);
    await tester.pumpWidget(_app(_cart(database, cart, repository)));
    await tester.tap(find.text('Payer comptant'));
    await tester.pumpAndSettle();
    expect(
      find.text('Une session de caisse doit être ouverte.'),
      findsOneWidget,
    );
    expect(repository.createCalls, 0);
  });

  testWidgets('CartScreen shows success and clears its cart', (tester) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final cart = CartState()
      ..add(
        const CartItem(
          productId: 1,
          variantId: null,
          name: 'Riz',
          unitPrice: 1000,
          quantity: 1,
        ),
      );
    await tester.pumpWidget(_app(_cart(database, cart, _WidgetSales())));
    await tester.tap(find.text('Payer comptant'));
    await tester.pumpAndSettle();
    expect(find.text('Vente finalisée'), findsOneWidget);
    expect(find.text('Reçu : TEST-001'), findsOneWidget);
    expect(find.text('Paiement : Cash'), findsOneWidget);
    expect(cart.items, isEmpty);
  });

  testWidgets('stock failure keeps the cart without retrying payment or sale', (
    tester,
  ) async {
    final database = AppDatabase(NativeDatabase.memory());
    addTearDown(database.close);
    final cart = CartState()
      ..add(
        const CartItem(
          productId: 1,
          variantId: null,
          name: 'Riz',
          unitPrice: 1000,
          quantity: 1,
        ),
      );
    final repository = _WidgetSales(stockFailure: true);
    await tester.pumpWidget(_app(_cart(database, cart, repository)));
    await tester.tap(find.text('Payer comptant'));
    await tester.pumpAndSettle();

    expect(find.text('Stock insuffisant'), findsOneWidget);
    expect(find.text('Vente finalisée'), findsNothing);
    expect(cart.items, hasLength(1));
    expect(repository.createCalls, 1);
    expect(repository.paymentCalls, 1);
  });
}

Widget _app(Widget home) => MaterialApp(home: home);

AppSession _session(AppDatabase database, {required bool offline}) {
  final api = ApiClient();
  final session = AppSession(
    api,
    AuthRepository(api, TokenStorage()),
    BootstrapRepository(api, BootstrapLocalRepository(database)),
    LocalCacheStore(),
  );
  session.organization = const Organization(id: 1, name: 'Org');
  session.shop = const CaisseEntity(id: 2, name: 'Boutique');
  session.terminal = const CaisseEntity(id: 3, name: 'Terminal');
  session.isOffline = offline;
  session.bootstrapData = BootstrapData(
    entitlements: Entitlements.fromJson({
      'entitlements': [
        {'slug': 'pos.sell'},
      ],
    }),
    categories: const [],
    products: const [],
    variants: const [],
    customers: const [],
    stock: const [],
    sessions: const [],
  );
  return session;
}

Widget _cart(AppDatabase database, CartState cart, _WidgetSales repository) =>
    CartScreen(
      session: _session(database, offline: false),
      database: database,
      cart: cart,
      repository: repository,
      controller: SaleController(repository)..beginAttempt(),
      onSuccess: () async {},
    );

Future<void> _seed(
  AppDatabase database, {
  List<ProductVariant> variants = const [
    ProductVariant(
      id: 11,
      productId: 10,
      name: 'Sac 5 kg',
      salePrice: 5000,
      raw: {
        'id': 11,
        'product_id': 10,
        'shop_id': 2,
        'name': 'Sac 5 kg',
        'sale_price': 5000,
      },
    ),
  ],
}) => BootstrapLocalRepository(database).replaceSnapshot(
  organizationId: 1,
  shopId: 2,
  entitlements: Entitlements.fromJson({
    'entitlements': [
      {'slug': 'pos.sell'},
    ],
  }),
  categories: const [],
  products: const [
    Product(
      id: 10,
      name: 'Riz',
      salePrice: 1000,
      raw: {'id': 10, 'shop_id': 2, 'name': 'Riz', 'sale_price': 1000},
    ),
  ],
  variants: variants,
  customers: const [],
  stock: const [],
  sessions: const [],
);

class _WidgetSales extends SaleRepository {
  _WidgetSales({this.sessionId = 4, this.stockFailure = false})
    : super(ApiClient());
  final int? sessionId;
  final bool stockFailure;
  int createCalls = 0;
  int paymentCalls = 0;
  @override
  Future<int?> activeCashSession(int terminalId) async => sessionId;
  @override
  Future<SaleRecord> createSale({
    required int shopId,
    required int terminalId,
    required int cashSessionId,
    required String localUuid,
    required String receiptNumber,
    required List<CartItem> items,
    int? customerId,
    int? deviceId,
  }) async {
    createCalls++;
    return const SaleRecord({
      'id': 8,
      'total_amount': 1000,
      'due_amount': 1000,
      'receipt_number': 'TEST-001',
    });
  }

  @override
  Future<SaleRecord> getSale(int saleId) async => const SaleRecord({
    'id': 8,
    'total_amount': 1000,
    'due_amount': 1000,
    'receipt_number': 'TEST-001',
  });
  @override
  Future<SaleRecord> makeCashPayment(int saleId, int amount) async {
    paymentCalls++;
    return const SaleRecord({
      'id': 8,
      'total_amount': 1000,
      'due_amount': 0,
      'receipt_number': 'TEST-001',
    });
  }

  @override
  Future<SaleRecord> finalizeSale(int saleId) async {
    if (stockFailure) throw const ApiException(422, 'Stock insuffisant');
    return const SaleRecord({
      'id': 8,
      'total_amount': 1000,
      'due_amount': 0,
      'receipt_number': 'TEST-001',
      'status': 'finalized',
    });
  }
}
