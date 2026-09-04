import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/database/app_database.dart'
    hide Customer, Product, ProductVariant;
import '../../core/database/local_repositories.dart';
import '../../core/formatters/currency.dart';
import '../../core/models/caisse_models.dart';

class LocalScope {
  const LocalScope({
    required this.organizationId,
    required this.shopId,
    this.isOffline = false,
    this.onRefresh,
  });

  final int organizationId;
  final int shopId;
  final bool isOffline;
  final Future<void> Function()? onRefresh;
}

class ProductListScreen extends StatefulWidget {
  const ProductListScreen({
    super.key,
    required this.database,
    required this.scope,
  });

  final AppDatabase database;
  final LocalScope scope;

  @override
  State<ProductListScreen> createState() => _ProductListScreenState();
}

class _ProductListScreenState extends State<ProductListScreen> {
  late final CatalogueLocalRepository _catalogue = CatalogueLocalRepository(
    widget.database,
  );
  final _search = TextEditingController();
  Timer? _debounce;
  List<CaisseEntity> _categories = const [];
  List<Product> _products = const [];
  Map<int, double> _stock = const {};
  int? _categoryId;
  bool _loading = true;
  bool _refreshing = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    final products = await _catalogue.searchProducts(
      _search.text,
      widget.scope.organizationId,
      widget.scope.shopId,
      categoryId: _categoryId,
    );
    final values = await Future.wait([
      _catalogue.categories(widget.scope.organizationId, widget.scope.shopId),
      _catalogue.productStockById(
        widget.scope.organizationId,
        widget.scope.shopId,
        products.map((product) => product.id),
      ),
    ]);
    if (!mounted) return;
    setState(() {
      _products = products;
      _categories = values[0] as List<CaisseEntity>;
      _stock = values[1] as Map<int, double>;
      _loading = false;
    });
  }

  void _searchChanged(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 300), _load);
  }

  Future<void> _refresh() async {
    if (widget.scope.onRefresh == null) return _load();
    setState(() => _refreshing = true);
    try {
      await widget.scope.onRefresh!();
      await _load();
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Rafraîchissement impossible.')),
        );
      }
    } finally {
      if (mounted) setState(() => _refreshing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Produits'),
        actions: [
          IconButton(
            tooltip: 'Rafraîchir',
            onPressed: _refreshing ? null : _refresh,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 760),
            child: Column(
              children: [
                if (widget.scope.isOffline) const _OfflineBanner(),
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: TextField(
                    controller: _search,
                    onChanged: _searchChanged,
                    decoration: const InputDecoration(
                      labelText: 'Rechercher un produit',
                      hintText: 'Nom, SKU ou code-barres',
                      prefixIcon: Icon(Icons.search),
                      border: OutlineInputBorder(),
                    ),
                  ),
                ),
                _CategoryFilter(
                  categories: _categories,
                  selectedId: _categoryId,
                  onSelected: (value) {
                    setState(() => _categoryId = value);
                    _load();
                  },
                ),
                const SizedBox(height: 8),
                Expanded(child: _productBody()),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _productBody() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_products.isEmpty) {
      return const _EmptyState(
        icon: Icons.inventory_2_outlined,
        message: 'Aucun produit disponible pour cette boutique.',
      );
    }
    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView.separated(
        physics: const AlwaysScrollableScrollPhysics(),
        itemCount: _products.length,
        separatorBuilder: (_, _) => const Divider(height: 1),
        itemBuilder: (context, index) {
          final product = _products[index];
          final raw = product.raw;
          final quantity = _stock[product.id];
          return ListTile(
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 20,
              vertical: 8,
            ),
            title: Text(product.name),
            subtitle: Text(_productDetails(raw, quantity)),
            trailing: Text(formatFcfa(product.salePrice)),
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute<void>(
                builder: (_) => ProductDetailScreen(
                  database: widget.database,
                  scope: widget.scope,
                  product: product,
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class ProductDetailScreen extends StatefulWidget {
  const ProductDetailScreen({
    super.key,
    required this.database,
    required this.scope,
    required this.product,
  });

  final AppDatabase database;
  final LocalScope scope;
  final Product product;

  @override
  State<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends State<ProductDetailScreen> {
  late final CatalogueLocalRepository _catalogue = CatalogueLocalRepository(
    widget.database,
  );
  late final StockLocalRepository _stockRepository = StockLocalRepository(
    widget.database,
  );
  List<ProductVariant> _variants = const [];
  Map<int, double> _variantStock = const {};
  double _productStock = 0;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final variants = await _catalogue.variantsForProduct(
      widget.scope.organizationId,
      widget.product.id,
    );
    final values = await Future.wait([
      _stockRepository.productQuantity(
        organizationId: widget.scope.organizationId,
        shopId: widget.scope.shopId,
        productId: widget.product.id,
      ),
      _stockRepository.variantStockById(
        widget.scope.organizationId,
        widget.scope.shopId,
        variants.map((variant) => variant.id),
      ),
    ]);
    if (!mounted) return;
    setState(() {
      _variants = variants;
      _productStock = values[0] as double;
      _variantStock = values[1] as Map<int, double>;
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    final raw = widget.product.raw;
    return Scaffold(
      appBar: AppBar(title: const Text('Détail produit')),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 760),
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : ListView(
                  padding: const EdgeInsets.all(20),
                  children: [
                    Text(
                      widget.product.name,
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    const SizedBox(height: 8),
                    Text(formatFcfa(widget.product.salePrice)),
                    if (raw['sku'] != null) Text('SKU : ${raw['sku']}'),
                    if (raw['barcode'] != null)
                      Text('Code-barres : ${raw['barcode']}'),
                    const SizedBox(height: 12),
                    Text('Stock produit : ${formatQuantity(_productStock)}'),
                    const SizedBox(height: 24),
                    Text(
                      'Variantes',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    if (_variants.isEmpty)
                      const Padding(
                        padding: EdgeInsets.only(top: 8),
                        child: Text('Ce produit ne possède pas de variante.'),
                      )
                    else
                      ..._variants.map(
                        (variant) => ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(variant.name),
                          subtitle: Text(_variantDetails(variant.raw)),
                          trailing: Text(
                            '${formatFcfa(variant.salePrice)}\nStock : ${formatQuantity(_variantStock[variant.id] ?? 0)}',
                            textAlign: TextAlign.end,
                          ),
                        ),
                      ),
                  ],
                ),
        ),
      ),
    );
  }
}

class CustomerListScreen extends StatefulWidget {
  const CustomerListScreen({
    super.key,
    required this.database,
    required this.scope,
  });

  final AppDatabase database;
  final LocalScope scope;

  @override
  State<CustomerListScreen> createState() => _CustomerListScreenState();
}

class _CustomerListScreenState extends State<CustomerListScreen> {
  late final CustomerLocalRepository _customers = CustomerLocalRepository(
    widget.database,
  );
  final _search = TextEditingController();
  Timer? _debounce;
  List<Customer> _items = const [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    final items = await _customers.searchCustomers(
      _search.text,
      widget.scope.organizationId,
      widget.scope.shopId,
    );
    if (mounted) {
      setState(() {
        _items = items;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Clients')),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 760),
            child: Column(
              children: [
                if (widget.scope.isOffline) const _OfflineBanner(),
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: TextField(
                    controller: _search,
                    onChanged: (_) {
                      _debounce?.cancel();
                      _debounce = Timer(
                        const Duration(milliseconds: 300),
                        _load,
                      );
                    },
                    decoration: const InputDecoration(
                      labelText: 'Rechercher un client',
                      hintText: 'Nom, téléphone ou e-mail',
                      prefixIcon: Icon(Icons.search),
                      border: OutlineInputBorder(),
                    ),
                  ),
                ),
                Expanded(
                  child: _loading
                      ? const Center(child: CircularProgressIndicator())
                      : _items.isEmpty
                      ? const _EmptyState(
                          icon: Icons.people_outline,
                          message:
                              'Aucun client disponible pour cette boutique.',
                        )
                      : ListView.separated(
                          itemCount: _items.length,
                          separatorBuilder: (_, _) => const Divider(height: 1),
                          itemBuilder: (_, index) {
                            final customer = _items[index];
                            return ListTile(
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 20,
                                vertical: 8,
                              ),
                              title: Text(customer.name),
                              subtitle: Text(_customerDetails(customer)),
                            );
                          },
                        ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class StockScreen extends StatefulWidget {
  const StockScreen({super.key, required this.database, required this.scope});

  final AppDatabase database;
  final LocalScope scope;

  @override
  State<StockScreen> createState() => _StockScreenState();
}

class _StockScreenState extends State<StockScreen> {
  late final StockLocalRepository _stock = StockLocalRepository(
    widget.database,
  );
  final _search = TextEditingController();
  Timer? _debounce;
  List<StockEntry> _items = const [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    final items = await _stock.searchStock(
      organizationId: widget.scope.organizationId,
      shopId: widget.scope.shopId,
      query: _search.text,
    );
    if (mounted) {
      setState(() {
        _items = items;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Stock')),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 760),
            child: Column(
              children: [
                if (widget.scope.isOffline) const _OfflineBanner(),
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: TextField(
                    controller: _search,
                    onChanged: (_) {
                      _debounce?.cancel();
                      _debounce = Timer(
                        const Duration(milliseconds: 300),
                        _load,
                      );
                    },
                    decoration: const InputDecoration(
                      labelText: 'Rechercher dans le stock',
                      hintText: 'Produit, SKU ou code-barres',
                      prefixIcon: Icon(Icons.search),
                      border: OutlineInputBorder(),
                    ),
                  ),
                ),
                Expanded(
                  child: _loading
                      ? const Center(child: CircularProgressIndicator())
                      : _items.isEmpty
                      ? const _EmptyState(
                          icon: Icons.inventory_outlined,
                          message:
                              'Aucun stock disponible pour cette boutique.',
                        )
                      : ListView.separated(
                          itemCount: _items.length,
                          separatorBuilder: (_, _) => const Divider(height: 1),
                          itemBuilder: (_, index) {
                            final entry = _items[index];
                            return ListTile(
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 20,
                                vertical: 8,
                              ),
                              title: Text(entry.label),
                              subtitle: Text(_stockDetails(entry)),
                              trailing: Text(
                                formatQuantity(entry.quantity),
                                style: Theme.of(context).textTheme.titleMedium,
                              ),
                            );
                          },
                        ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _CategoryFilter extends StatelessWidget {
  const _CategoryFilter({
    required this.categories,
    required this.selectedId,
    required this.onSelected,
  });

  final List<CaisseEntity> categories;
  final int? selectedId;
  final ValueChanged<int?> onSelected;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 42,
      child: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        scrollDirection: Axis.horizontal,
        children: [
          ChoiceChip(
            label: const Text('Toutes'),
            selected: selectedId == null,
            onSelected: (_) => onSelected(null),
          ),
          ...categories.map(
            (category) => Padding(
              padding: const EdgeInsets.only(left: 8),
              child: ChoiceChip(
                label: Text(category.name),
                selected: selectedId == category.id,
                onSelected: (_) => onSelected(category.id),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _OfflineBanner extends StatelessWidget {
  const _OfflineBanner();

  @override
  Widget build(BuildContext context) => Container(
    width: double.infinity,
    color: Theme.of(context).colorScheme.secondaryContainer,
    padding: const EdgeInsets.all(10),
    child: const Text('Mode hors ligne : données locales affichées.'),
  );
}

class _EmptyState extends StatelessWidget {
  const _EmptyState({required this.icon, required this.message});
  final IconData icon;
  final String message;

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 48),
          const SizedBox(height: 12),
          Text(message, textAlign: TextAlign.center),
        ],
      ),
    ),
  );
}

String _productDetails(Map<String, dynamic> raw, double? quantity) {
  final values = <String>[
    if (raw['sku'] != null) 'SKU : ${raw['sku']}',
    if (raw['barcode'] != null) 'Code-barres : ${raw['barcode']}',
    'Statut : ${raw['status'] ?? 'non renseigné'}',
    quantity == null
        ? 'Stock : non renseigné'
        : 'Stock : ${formatQuantity(quantity)}',
  ];
  return values.join(' • ');
}

String _variantDetails(Map<String, dynamic> raw) => [
  if (raw['sku'] != null) 'SKU : ${raw['sku']}',
  if (raw['barcode'] != null) 'Code-barres : ${raw['barcode']}',
].join(' • ');

String _customerDetails(Customer customer) => [
  if (customer.phone != null) customer.phone!,
  if (customer.email != null) customer.email!,
  'Statut : ${customer.raw['status'] ?? 'non renseigné'}',
].join(' • ');

String _stockDetails(StockEntry entry) => [
  entry.isVariant ? 'Variante' : 'Produit',
  if (entry.sku != null) 'SKU : ${entry.sku}',
  if (entry.barcode != null) 'Code-barres : ${entry.barcode}',
  if (entry.location != null) 'Emplacement : ${entry.location}',
].join(' • ');
