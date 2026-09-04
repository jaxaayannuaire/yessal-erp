import '../api/api_client.dart';
import '../database/local_repositories.dart';
import '../models/caisse_models.dart';

class BootstrapData {
  const BootstrapData({
    required this.entitlements,
    required this.categories,
    required this.products,
    required this.variants,
    required this.customers,
    required this.stock,
    required this.sessions,
  });

  final Entitlements entitlements;
  final List<CaisseEntity> categories;
  final List<Product> products;
  final List<ProductVariant> variants;
  final List<Customer> customers;
  final List<Map<String, dynamic>> stock;
  final List<Map<String, dynamic>> sessions;
}

class BootstrapRepository {
  BootstrapRepository(this._api, this._local);

  final ApiClient _api;
  final BootstrapLocalRepository _local;

  Future<List<CaisseEntity>> shops() => _entities('/caisse/shops', 'shops');
  Future<List<CaisseEntity>> terminals() =>
      _entities('/caisse/terminals', 'terminals');
  Future<List<CaisseEntity>> devices() =>
      _entities('/caisse/devices', 'devices');

  Future<void> cacheOrganization(Organization organization) =>
      _local.cacheOrganization(organization);

  Future<BootstrapData> bootstrap(int organizationId, int shopId) async {
    final results = await Future.wait([
      _api.get('/organization/entitlements'),
      _api.get('/caisse/categories', query: {'per_page': '100'}),
      _api.get(
        '/caisse/products',
        query: {'per_page': '100', 'shop_id': '$shopId'},
      ),
      _api.get('/caisse/customers', query: {'per_page': '100'}),
      _api.get('/caisse/stock', query: {'per_page': '100'}),
      _api.get('/caisse/cash-sessions'),
    ]);
    final products = _list(
      results[2],
      'products',
    ).map(Product.fromJson).toList();
    final variants = await _variantsFor(products);
    final data = BootstrapData(
      entitlements: Entitlements.fromJson(results[0]),
      categories: _list(
        results[1],
        'categories',
      ).map(CaisseEntity.fromJson).toList(),
      products: products,
      variants: variants,
      customers: _list(results[3], 'customers').map(Customer.fromJson).toList(),
      stock: _list(results[4], 'stock'),
      sessions: (results[5]['cash_sessions'] as List? ?? const [])
          .cast<Map<String, dynamic>>(),
    );
    await _local.replaceSnapshot(
      organizationId: organizationId,
      shopId: shopId,
      entitlements: data.entitlements,
      categories: data.categories,
      products: data.products,
      variants: data.variants,
      customers: data.customers,
      stock: data.stock,
      sessions: data.sessions,
    );
    return data;
  }

  Future<BootstrapData?> cached(int organizationId, int shopId) async {
    final cached = await _local.readSnapshot(
      organizationId: organizationId,
      shopId: shopId,
    );
    if (cached == null) return null;
    return BootstrapData(
      entitlements: cached.entitlements,
      categories: cached.categories,
      products: cached.products,
      variants: cached.variants,
      customers: cached.customers,
      stock: cached.stock,
      sessions: cached.sessions,
    );
  }

  Future<List<ProductVariant>> _variantsFor(List<Product> products) async {
    final perProduct = await Future.wait(
      products.map((product) async {
        final response = await _api.get(
          '/caisse/products/${product.id}/variants',
          query: {'per_page': '100'},
        );
        return _list(response, 'variants')
            .map(
              (raw) => ProductVariant.fromJson({
                ...raw,
                'product_id': raw['product_id'] ?? product.id,
                'shop_id': raw['shop_id'] ?? product.raw['shop_id'],
              }),
            )
            .toList();
      }),
    );
    return perProduct.expand((variants) => variants).toList();
  }

  Future<List<CaisseEntity>> _entities(String path, String key) async =>
      _list(await _api.get(path), key).map(CaisseEntity.fromJson).toList();

  List<Map<String, dynamic>> _list(Map<String, dynamic> json, String key) {
    final value = json[key];
    final list = value is Map<String, dynamic> ? value['data'] : value;
    return (list as List? ?? const [])
        .map((item) => (item as Map).cast<String, dynamic>())
        .toList();
  }
}
