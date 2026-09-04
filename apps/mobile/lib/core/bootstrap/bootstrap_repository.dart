import '../api/api_client.dart';
import '../models/caisse_models.dart';
import '../storage/local_cache_store.dart';

class BootstrapData {
  const BootstrapData({
    required this.entitlements,
    required this.categories,
    required this.products,
    required this.customers,
    required this.stock,
    required this.sessions,
  });
  final Entitlements entitlements;
  final List<CaisseEntity> categories;
  final List<Product> products;
  final List<Customer> customers;
  final List<Map<String, dynamic>> stock;
  final List<Map<String, dynamic>> sessions;
}

class BootstrapRepository {
  BootstrapRepository(this._api, this._cache);
  final ApiClient _api;
  final LocalCacheStore _cache;

  Future<List<CaisseEntity>> shops() => _entities('/caisse/shops', 'shops');
  Future<List<CaisseEntity>> terminals() =>
      _entities('/caisse/terminals', 'terminals');
  Future<List<CaisseEntity>> devices() =>
      _entities('/caisse/devices', 'devices');

  Future<BootstrapData> bootstrap(int userId, int organizationId) async {
    final results = await Future.wait([
      _api.get('/organization/entitlements'),
      _api.get('/caisse/categories', query: {'per_page': '100'}),
      _api.get('/caisse/products', query: {'per_page': '100'}),
      _api.get('/caisse/customers', query: {'per_page': '100'}),
      _api.get('/caisse/stock', query: {'per_page': '100'}),
      _api.get('/caisse/cash-sessions'),
    ]);
    final data = BootstrapData(
      entitlements: Entitlements.fromJson(results[0]),
      categories: _list(
        results[1],
        'categories',
      ).map(CaisseEntity.fromJson).toList(),
      products: _list(results[2], 'products').map(Product.fromJson).toList(),
      customers: _list(results[3], 'customers').map(Customer.fromJson).toList(),
      stock: _list(results[4], 'stock'),
      sessions: (results[5]['cash_sessions'] as List)
          .cast<Map<String, dynamic>>(),
    );
    await _cache.writeJson(userId, organizationId, 'bootstrap', {
      'entitlements': data.entitlements.toJson(),
      'categories': data.categories.map((item) => item.toJson()).toList(),
      'products': data.products.map((item) => item.toJson()).toList(),
      'customers': data.customers.map((item) => item.toJson()).toList(),
      'stock': data.stock,
      'sessions': data.sessions,
      'last_bootstrap_at': DateTime.now().toIso8601String(),
    });
    return data;
  }

  Future<BootstrapData?> cached(int userId, int organizationId) async {
    final raw = await _cache.readJson(userId, organizationId, 'bootstrap');
    if (raw is! Map<String, dynamic>) return null;
    return BootstrapData(
      entitlements: Entitlements.fromJson(
        raw['entitlements'] as Map<String, dynamic>,
      ),
      categories: (raw['categories'] as List)
          .cast<Map<String, dynamic>>()
          .map(CaisseEntity.fromJson)
          .toList(),
      products: (raw['products'] as List)
          .cast<Map<String, dynamic>>()
          .map(Product.fromJson)
          .toList(),
      customers: (raw['customers'] as List)
          .cast<Map<String, dynamic>>()
          .map(Customer.fromJson)
          .toList(),
      stock: (raw['stock'] as List).cast<Map<String, dynamic>>(),
      sessions: (raw['sessions'] as List).cast<Map<String, dynamic>>(),
    );
  }

  Future<List<CaisseEntity>> _entities(String path, String key) async =>
      _list(await _api.get(path), key).map(CaisseEntity.fromJson).toList();
  List<Map<String, dynamic>> _list(Map<String, dynamic> json, String key) {
    final value = json[key];
    final list = value is Map<String, dynamic> ? value['data'] : value;
    return (list as List).cast<Map<String, dynamic>>();
  }
}
