import 'dart:async';

import 'package:flutter/material.dart';

import '../../app/app_session.dart';
import '../../core/database/app_database.dart'
    hide Customer, Product, ProductVariant;
import '../../core/database/local_repositories.dart';
import '../../core/errors/api_exception.dart';
import '../../core/formatters/currency.dart';
import '../../core/models/caisse_models.dart';
import 'cart.dart';
import 'sale_controller.dart';
import 'sale_repository.dart';

class SalesScreen extends StatefulWidget {
  const SalesScreen({
    super.key,
    required this.session,
    required this.database,
    required this.repository,
  });
  final AppSession session;
  final AppDatabase database;
  final SaleRepository repository;
  @override
  State<SalesScreen> createState() => _SalesScreenState();
}

class _SalesScreenState extends State<SalesScreen> {
  final CartState cart = CartState();
  late final SaleController controller = SaleController(widget.repository);
  final search = TextEditingController();
  Timer? debounce;
  late final CatalogueLocalRepository catalogue = CatalogueLocalRepository(
    widget.database,
  );
  late final StockLocalRepository stock = StockLocalRepository(widget.database);
  List<Product> products = const [];
  Map<int, double> quantities = const {};
  bool loading = true;

  int get organizationId => widget.session.organization!.id;
  int get shopId => widget.session.shop!.id;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    debounce?.cancel();
    search.dispose();
    cart.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    final found = await catalogue.searchProducts(
      search.text,
      organizationId,
      shopId,
    );
    final levels = await catalogue.productStockById(
      organizationId,
      shopId,
      found.map((p) => p.id),
    );
    if (mounted) {
      setState(() {
        products = found;
        quantities = levels;
        loading = false;
      });
    }
  }

  Future<void> _add(Product product) async {
    final variants = await catalogue.variantsForProduct(
      organizationId,
      product.id,
    );
    if (!mounted) return;
    if (variants.isNotEmpty) {
      final variant = await showModalBottomSheet<ProductVariant>(
        context: context,
        builder: (_) => ListView(
          children: variants
              .map(
                (v) => ListTile(
                  title: Text(v.name),
                  subtitle: Text(formatFcfa(v.salePrice)),
                  onTap: () => Navigator.pop(context, v),
                ),
              )
              .toList(),
        ),
      );
      if (variant == null) return;
      final available = await stock.variantQuantity(
        organizationId: organizationId,
        shopId: shopId,
        variantId: variant.id,
      );
      cart.add(
        CartItem(
          productId: product.id,
          variantId: variant.id,
          name: '${product.name} – ${variant.name}',
          unitPrice: variant.salePrice,
          quantity: 1,
          availableStock: available,
        ),
      );
    } else {
      cart.add(
        CartItem(
          productId: product.id,
          variantId: null,
          name: product.name,
          unitPrice: product.salePrice,
          quantity: 1,
          availableStock: quantities[product.id],
        ),
      );
    }
    final item = cart.items.lastWhere((i) => i.productId == product.id);
    if (item.availableStock != null && item.quantity > item.availableStock!) {
      _message(
        'Stock local indicatif insuffisant. Le serveur confirmera la vente.',
      );
    }
  }

  void _message(String text) =>
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text)));

  @override
  Widget build(BuildContext context) {
    final allowed = widget.session.bootstrapData?.entitlements.allowed ?? false;
    if (!allowed) {
      return const Scaffold(body: Center(child: Text('Accès Caisse bloqué.')));
    }
    return Scaffold(
      appBar: AppBar(
        title: const Text('Vente cash'),
        actions: [
          AnimatedBuilder(
            animation: cart,
            builder: (_, _) => TextButton.icon(
              onPressed: () => Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => CartScreen(
                    session: widget.session,
                    database: widget.database,
                    cart: cart,
                    repository: widget.repository,
                    controller: controller,
                    onSuccess: _load,
                  ),
                ),
              ),
              icon: const Icon(Icons.shopping_cart),
              label: Text('${cart.itemCount}'),
            ),
          ),
        ],
      ),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 760),
          child: Column(
            children: [
              if (widget.session.isOffline) const _OfflineNotice(),
              Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  controller: search,
                  onChanged: (_) {
                    debounce?.cancel();
                    debounce = Timer(const Duration(milliseconds: 300), _load);
                  },
                  decoration: const InputDecoration(
                    labelText: 'Rechercher un produit',
                    prefixIcon: Icon(Icons.search),
                    border: OutlineInputBorder(),
                  ),
                ),
              ),
              Expanded(
                child: loading
                    ? const Center(child: CircularProgressIndicator())
                    : products.isEmpty
                    ? const Center(child: Text('Aucun produit disponible.'))
                    : ListView.separated(
                        itemCount: products.length,
                        separatorBuilder: (_, _) => const Divider(height: 1),
                        itemBuilder: (_, i) {
                          final p = products[i];
                          return ListTile(
                            title: Text(p.name),
                            subtitle: Text(
                              '${formatFcfa(p.salePrice)} • Stock : ${quantities[p.id] == null ? '—' : formatQuantity(quantities[p.id]!)}',
                            ),
                            trailing: FilledButton(
                              onPressed: () => _add(p),
                              child: const Text('Ajouter'),
                            ),
                          );
                        },
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class CartScreen extends StatefulWidget {
  const CartScreen({
    super.key,
    required this.session,
    required this.database,
    required this.cart,
    required this.repository,
    required this.controller,
    required this.onSuccess,
  });
  final AppSession session;
  final AppDatabase database;
  final CartState cart;
  final SaleRepository repository;
  final SaleController controller;
  final Future<void> Function() onSuccess;
  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  Customer? customer;
  bool submitting = false;
  Future<void> _chooseCustomer() async {
    final items = await CustomerLocalRepository(widget.database)
        .searchCustomers(
          '',
          widget.session.organization!.id,
          widget.session.shop!.id,
        );
    if (!mounted) return;
    final value = await showModalBottomSheet<Customer?>(
      context: context,
      builder: (_) => ListView(
        children: [
          const ListTile(title: Text('Vente comptoir'), onTap: null),
          ...items.map(
            (c) => ListTile(
              title: Text(c.name),
              subtitle: Text(c.phone ?? ''),
              onTap: () => Navigator.pop(context, c),
            ),
          ),
        ],
      ),
    );
    if (mounted) setState(() => customer = value);
  }

  Future<void> _submit() async {
    if (widget.session.isOffline) {
      _error('Connexion requise pour enregistrer cette vente.');
      return;
    }
    if (widget.cart.items.isEmpty) {
      _error('Le panier est vide.');
      return;
    }
    setState(() => submitting = true);
    widget.controller.beginAttempt();
    try {
      final cashSessionId = await widget.repository.activeCashSession(
        widget.session.terminal!.id,
      );
      if (cashSessionId == null) {
        _error('Une session de caisse doit être ouverte.');
        return;
      }
      final sale = await widget.controller.submit(
        shopId: widget.session.shop!.id,
        terminalId: widget.session.terminal!.id,
        cashSessionId: cashSessionId,
        deviceId: widget.session.device?.id,
        customerId: customer?.id,
        receiptNumber: widget.controller.receiptNumber!,
        items: widget.cart.items,
      );
      if (!mounted) return;
      widget.cart.clear();
      widget.session.bootstrap();
      await widget.onSuccess();
      if (!mounted) return;
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => SaleSuccessScreen(sale: sale, customer: customer),
        ),
      );
    } on ApiException catch (e) {
      if (e.isValidationError && e.message.toLowerCase().contains('stock')) {
        widget.session.bootstrap();
      }
      _error(e.message);
    } finally {
      if (mounted) setState(() => submitting = false);
    }
  }

  void _error(String text) {
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text)));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Panier')),
    body: AnimatedBuilder(
      animation: widget.cart,
      builder: (_, _) => Column(
        children: [
          Expanded(
            child: widget.cart.items.isEmpty
                ? const Center(child: Text('Votre panier est vide.'))
                : ListView(
                    children: widget.cart.items
                        .map(
                          (item) => ListTile(
                            title: Text(item.name),
                            subtitle: Text(
                              '${formatFcfa(item.unitPrice)} • Sous-total : ${formatFcfa(item.subtotal)}',
                            ),
                            leading: IconButton(
                              onPressed: () => widget.cart.setQuantity(
                                item.key,
                                item.quantity - 1,
                              ),
                              icon: const Icon(Icons.remove),
                            ),
                            trailing: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text('${item.quantity}'),
                                IconButton(
                                  onPressed: () => widget.cart.setQuantity(
                                    item.key,
                                    item.quantity + 1,
                                  ),
                                  icon: const Icon(Icons.add),
                                ),
                                IconButton(
                                  onPressed: () => widget.cart.remove(item.key),
                                  icon: const Icon(Icons.delete_outline),
                                ),
                              ],
                            ),
                          ),
                        )
                        .toList(),
                  ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                OutlinedButton(
                  onPressed: _chooseCustomer,
                  child: Text(
                    customer == null
                        ? 'Vente comptoir / choisir un client'
                        : customer!.name,
                  ),
                ),
                Text(
                  'Total : ${formatFcfa(widget.cart.total)}',
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
                const SizedBox(height: 8),
                FilledButton(
                  onPressed: submitting ? null : _submit,
                  child: Text(
                    submitting ? 'Enregistrement…' : 'Payer comptant',
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    ),
  );
}

class SaleSuccessScreen extends StatelessWidget {
  const SaleSuccessScreen({super.key, required this.sale, this.customer});
  final SaleRecord sale;
  final Customer? customer;
  @override
  Widget build(BuildContext context) => Scaffold(
    body: Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.check_circle, size: 64, color: Colors.green),
            const SizedBox(height: 16),
            const Text('Vente finalisée'),
            if (sale.receiptNumber != null)
              Text('Reçu : ${sale.receiptNumber}'),
            Text(formatFcfa(sale.total)),
            const Text('Paiement : Cash'),
            if (customer != null) Text('Client : ${customer!.name}'),
            Text(DateTime.now().toLocal().toString()),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: () =>
                  Navigator.popUntil(context, (route) => route.isFirst),
              child: const Text('Retour accueil'),
            ),
          ],
        ),
      ),
    ),
  );
}

class _OfflineNotice extends StatelessWidget {
  const _OfflineNotice();
  @override
  Widget build(BuildContext context) => const Padding(
    padding: EdgeInsets.all(12),
    child: Text('Mode hors ligne : la vente en ligne est indisponible.'),
  );
}
