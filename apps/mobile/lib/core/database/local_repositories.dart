import 'package:drift/drift.dart';

import '../models/caisse_models.dart';
import 'app_database.dart' as db;

class CachedBootstrap {
  const CachedBootstrap({
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

class BootstrapLocalRepository {
  BootstrapLocalRepository(this._database, {this.afterSnapshotWrite});

  final db.AppDatabase _database;
  final Future<void> Function()? afterSnapshotWrite;

  Future<void> cacheOrganization(Organization organization) {
    return _database
        .into(_database.organizationsCache)
        .insertOnConflictUpdate(
          db.OrganizationsCacheCompanion.insert(
            organizationId: Value(organization.id),
            name: organization.name,
          ),
        );
  }

  Future<void> replaceSnapshot({
    required int organizationId,
    required int shopId,
    required Entitlements entitlements,
    required List<CaisseEntity> categories,
    required List<Product> products,
    required List<ProductVariant> variants,
    required List<Customer> customers,
    required List<Map<String, dynamic>> stock,
    required List<Map<String, dynamic>> sessions,
  }) async {
    await _database.transaction(() async {
      await (_database.delete(
        _database.entitlementsCache,
      )..where((row) => row.organizationId.equals(organizationId))).go();
      await (_database.delete(_database.categories)..where(
            (row) =>
                row.organizationId.equals(organizationId) &
                (row.shopId.equals(shopId) | row.shopId.isNull()),
          ))
          .go();
      await (_database.delete(_database.products)..where(
            (row) =>
                row.organizationId.equals(organizationId) &
                row.shopId.equals(shopId),
          ))
          .go();
      await (_database.delete(_database.productVariants)..where(
            (row) =>
                row.organizationId.equals(organizationId) &
                row.shopId.equals(shopId),
          ))
          .go();
      await (_database.delete(_database.customers)..where(
            (row) =>
                row.organizationId.equals(organizationId) &
                (row.shopId.equals(shopId) | row.shopId.isNull()),
          ))
          .go();
      await (_database.delete(_database.stockLevels)..where(
            (row) =>
                row.organizationId.equals(organizationId) &
                row.shopId.equals(shopId),
          ))
          .go();
      await (_database.delete(_database.cashSessions)..where(
            (row) =>
                row.organizationId.equals(organizationId) &
                (row.shopId.equals(shopId) | row.shopId.isNull()),
          ))
          .go();

      for (final entitlement
          in entitlements.raw['entitlements'] as List? ?? []) {
        final raw = (entitlement as Map).cast<String, dynamic>();
        final slug = raw['slug'] as String?;
        if (slug == null) continue;
        await _database
            .into(_database.entitlementsCache)
            .insertOnConflictUpdate(
              db.EntitlementsCacheCompanion.insert(
                organizationId: organizationId,
                slug: slug,
                rawJson: db.encodeRaw(raw),
              ),
            );
      }
      for (final category in categories) {
        final raw = category.toJson();
        await _database
            .into(_database.categories)
            .insertOnConflictUpdate(
              db.CategoriesCompanion.insert(
                organizationId: organizationId,
                id: category.id,
                shopId: Value(_int(raw['shop_id'])),
                name: category.name,
                status: Value(raw['status'] as String?),
                rawJson: db.encodeRaw(raw),
              ),
            );
      }
      for (final product in products) {
        final raw = product.toJson();
        await _database
            .into(_database.products)
            .insertOnConflictUpdate(
              db.ProductsCompanion.insert(
                organizationId: organizationId,
                id: product.id,
                shopId: _requiredInt(raw['shop_id'], 'shop_id'),
                categoryId: Value(_int(raw['category_id'])),
                name: product.name,
                sku: Value(raw['sku'] as String?),
                barcode: Value(raw['barcode'] as String?),
                salePrice: product.salePrice,
                purchasePrice: Value(_int(raw['purchase_price'])),
                status: Value(raw['status'] as String?),
                rawJson: db.encodeRaw(raw),
              ),
            );
      }
      for (final variant in variants) {
        final raw = variant.toJson();
        await _database
            .into(_database.productVariants)
            .insertOnConflictUpdate(
              db.ProductVariantsCompanion.insert(
                organizationId: organizationId,
                id: variant.id,
                shopId: _requiredInt(raw['shop_id'], 'shop_id'),
                productId: variant.productId,
                name: variant.name,
                sku: Value(raw['sku'] as String?),
                barcode: Value(raw['barcode'] as String?),
                salePrice: variant.salePrice,
                purchasePrice: Value(_int(raw['purchase_price'])),
                attributesJson: Value(
                  raw['attributes'] == null
                      ? null
                      : db.encodeRaw(raw['attributes'] as Map<String, dynamic>),
                ),
                rawJson: db.encodeRaw(raw),
              ),
            );
      }
      for (final customer in customers) {
        final raw = customer.toJson();
        await _database
            .into(_database.customers)
            .insertOnConflictUpdate(
              db.CustomersCompanion.insert(
                organizationId: organizationId,
                id: customer.id,
                shopId: Value(_int(raw['shop_id'])),
                name: customer.name,
                phone: Value(customer.phone),
                email: Value(customer.email),
                status: Value(raw['status'] as String?),
                rawJson: db.encodeRaw(raw),
              ),
            );
      }
      for (final row in stock) {
        final location =
            _int(row['stock_location_id']) ?? _int(row['location_id']);
        final productId = _int(row['product_id']);
        final variantId =
            _int(row['product_variant_id']) ?? _int(row['variant_id']);
        final stockShopId = _stockShopId(row);
        if (location == null ||
            stockShopId == null ||
            (productId == null && variantId == null)) {
          continue;
        }
        final identity = productId != null
            ? 'product:$productId'
            : 'variant:$variantId';
        await _database
            .into(_database.stockLevels)
            .insertOnConflictUpdate(
              db.StockLevelsCompanion.insert(
                organizationId: organizationId,
                shopId: stockShopId,
                stockLocationId: location,
                stockIdentity: identity,
                productId: Value(productId),
                variantId: Value(variantId),
                quantity: _double(row['quantity']),
                reservedQuantity: Value(
                  _nullableDouble(row['reserved_quantity']),
                ),
                rawJson: db.encodeRaw(row),
              ),
            );
      }
      for (final session in sessions) {
        final id = _int(session['id']);
        if (id == null) continue;
        await _database
            .into(_database.cashSessions)
            .insertOnConflictUpdate(
              db.CashSessionsCompanion.insert(
                organizationId: organizationId,
                id: id,
                shopId: Value(_int(session['shop_id'])),
                terminalId: Value(_int(session['terminal_id'])),
                status: Value(session['status'] as String?),
                rawJson: db.encodeRaw(session),
              ),
            );
      }
      await afterSnapshotWrite?.call();
      await _database
          .into(_database.bootstrapMetadata)
          .insertOnConflictUpdate(
            db.BootstrapMetadataCompanion.insert(
              organizationId: organizationId,
              shopId: shopId,
              lastBootstrapAt: DateTime.now().toUtc(),
            ),
          );
    });
  }

  Future<CachedBootstrap?> readSnapshot({
    required int organizationId,
    required int shopId,
  }) async {
    final metadata =
        await (_database.select(_database.bootstrapMetadata)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  row.shopId.equals(shopId),
            ))
            .getSingleOrNull();
    if (metadata == null) return null;

    final entitlements = await (_database.select(
      _database.entitlementsCache,
    )..where((row) => row.organizationId.equals(organizationId))).get();
    final categories =
        await (_database.select(_database.categories)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  (row.shopId.equals(shopId) | row.shopId.isNull()),
            ))
            .get();
    final products =
        await (_database.select(_database.products)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  row.shopId.equals(shopId),
            ))
            .get();
    final variants =
        await (_database.select(_database.productVariants)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  row.shopId.equals(shopId),
            ))
            .get();
    final customers =
        await (_database.select(_database.customers)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  (row.shopId.equals(shopId) | row.shopId.isNull()),
            ))
            .get();
    final stock =
        await (_database.select(_database.stockLevels)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  row.shopId.equals(shopId),
            ))
            .get();
    final sessions =
        await (_database.select(_database.cashSessions)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  (row.shopId.equals(shopId) | row.shopId.isNull()),
            ))
            .get();

    return CachedBootstrap(
      entitlements: Entitlements.fromJson({
        'entitlements': entitlements
            .map((row) => db.decodeRaw(row.rawJson))
            .toList(),
      }),
      categories: categories
          .map((row) => CaisseEntity.fromJson(db.decodeRaw(row.rawJson)))
          .toList(),
      products: products
          .map((row) => Product.fromJson(db.decodeRaw(row.rawJson)))
          .toList(),
      variants: variants
          .map((row) => ProductVariant.fromJson(db.decodeRaw(row.rawJson)))
          .toList(),
      customers: customers
          .map((row) => Customer.fromJson(db.decodeRaw(row.rawJson)))
          .toList(),
      stock: stock.map((row) => db.decodeRaw(row.rawJson)).toList(),
      sessions: sessions.map((row) => db.decodeRaw(row.rawJson)).toList(),
    );
  }

  Future<void> updateCursor(int organizationId, int? cursor) {
    return _database
        .into(_database.syncMetadata)
        .insertOnConflictUpdate(
          db.SyncMetadataCompanion.insert(
            organizationId: Value(organizationId),
            lastCursor: Value(cursor),
            lastSyncAt: Value(DateTime.now().toUtc()),
          ),
        );
  }

  Future<int?> readCursor(int organizationId) async =>
      (await (_database.select(_database.syncMetadata)
                ..where((row) => row.organizationId.equals(organizationId)))
              .getSingleOrNull())
          ?.lastCursor;
}

class CatalogueLocalRepository {
  CatalogueLocalRepository(this._database);
  final db.AppDatabase _database;

  Future<List<Product>> searchProducts(
    String query,
    int organizationId,
    int shopId, {
    int? categoryId,
  }) async {
    final pattern = '%${query.trim()}%';
    final rows =
        await (_database.select(_database.products)
              ..where((row) {
                var expression =
                    row.organizationId.equals(organizationId) &
                    row.shopId.equals(shopId) &
                    (row.name.like(pattern) |
                        row.sku.like(pattern) |
                        row.barcode.like(pattern));
                if (categoryId != null) {
                  expression = expression & row.categoryId.equals(categoryId);
                }
                return expression;
              })
              ..orderBy([(row) => OrderingTerm.asc(row.name)]))
            .get();
    return rows
        .map((row) => Product.fromJson(db.decodeRaw(row.rawJson)))
        .toList();
  }

  Future<List<ProductVariant>> variantsForProduct(
    int organizationId,
    int productId,
  ) async {
    final rows =
        await (_database.select(_database.productVariants)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  row.productId.equals(productId),
            ))
            .get();
    return rows
        .map((row) => ProductVariant.fromJson(db.decodeRaw(row.rawJson)))
        .toList();
  }

  Future<List<CaisseEntity>> categories(int organizationId, int shopId) async {
    final rows =
        await (_database.select(_database.categories)
              ..where(
                (row) =>
                    row.organizationId.equals(organizationId) &
                    (row.shopId.equals(shopId) | row.shopId.isNull()),
              )
              ..orderBy([(row) => OrderingTerm.asc(row.name)]))
            .get();
    return rows
        .map((row) => CaisseEntity.fromJson(db.decodeRaw(row.rawJson)))
        .toList();
  }

  /// Returns direct product stock only. Variant stock stays separate so the
  /// catalogue never double-counts variant quantities as product quantities.
  Future<Map<int, double>> productStockById(
    int organizationId,
    int shopId,
    Iterable<int> productIds,
  ) async {
    final ids = productIds.toSet().toList();
    if (ids.isEmpty) return const {};
    final rows =
        await (_database.select(_database.stockLevels)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  row.shopId.equals(shopId) &
                  row.productId.isIn(ids) &
                  row.variantId.isNull(),
            ))
            .get();
    final totals = <int, double>{};
    for (final row in rows) {
      final productId = row.productId;
      if (productId == null) continue;
      totals.update(
        productId,
        (total) => total + row.quantity,
        ifAbsent: () => row.quantity,
      );
    }
    return totals;
  }
}

class CustomerLocalRepository {
  CustomerLocalRepository(this._database);
  final db.AppDatabase _database;

  Future<List<Customer>> searchCustomers(
    String query,
    int organizationId,
    int shopId,
  ) async {
    final pattern = '%${query.trim()}%';
    final rows =
        await (_database.select(_database.customers)
              ..where(
                (row) =>
                    row.organizationId.equals(organizationId) &
                    (row.shopId.equals(shopId) | row.shopId.isNull()) &
                    (row.name.like(pattern) |
                        row.phone.like(pattern) |
                        row.email.like(pattern)),
              )
              ..orderBy([(row) => OrderingTerm.asc(row.name)]))
            .get();
    return rows
        .map((row) => Customer.fromJson(db.decodeRaw(row.rawJson)))
        .toList();
  }
}

class StockLocalRepository {
  StockLocalRepository(this._database);
  final db.AppDatabase _database;

  Future<List<Map<String, dynamic>>> forProduct({
    required int organizationId,
    required int shopId,
    int? productId,
    int? variantId,
  }) async {
    final rows =
        await (_database.select(_database.stockLevels)..where((row) {
              var expression =
                  row.organizationId.equals(organizationId) &
                  row.shopId.equals(shopId);
              if (productId != null) {
                expression = expression & row.productId.equals(productId);
              }
              if (variantId != null) {
                expression = expression & row.variantId.equals(variantId);
              }
              return expression;
            }))
            .get();
    return rows.map((row) => db.decodeRaw(row.rawJson)).toList();
  }

  Future<double> productQuantity({
    required int organizationId,
    required int shopId,
    required int productId,
  }) => _quantity(
    organizationId: organizationId,
    shopId: shopId,
    productId: productId,
    directProductOnly: true,
  );

  Future<double> variantQuantity({
    required int organizationId,
    required int shopId,
    required int variantId,
  }) => _quantity(
    organizationId: organizationId,
    shopId: shopId,
    variantId: variantId,
  );

  Future<Map<int, double>> variantStockById(
    int organizationId,
    int shopId,
    Iterable<int> variantIds,
  ) async {
    final ids = variantIds.toSet().toList();
    if (ids.isEmpty) return const {};
    final rows =
        await (_database.select(_database.stockLevels)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  row.shopId.equals(shopId) &
                  row.variantId.isIn(ids),
            ))
            .get();
    final totals = <int, double>{};
    for (final row in rows) {
      final variantId = row.variantId;
      if (variantId == null) continue;
      totals.update(
        variantId,
        (total) => total + row.quantity,
        ifAbsent: () => row.quantity,
      );
    }
    return totals;
  }

  Future<double> _quantity({
    required int organizationId,
    required int shopId,
    int? productId,
    int? variantId,
    bool directProductOnly = false,
  }) async {
    final rows =
        await (_database.select(_database.stockLevels)..where((row) {
              var expression =
                  row.organizationId.equals(organizationId) &
                  row.shopId.equals(shopId);
              if (productId != null) {
                expression = expression & row.productId.equals(productId);
              }
              if (variantId != null) {
                expression = expression & row.variantId.equals(variantId);
              }
              if (directProductOnly) {
                expression = expression & row.variantId.isNull();
              }
              return expression;
            }))
            .get();
    return rows.fold<double>(0, (total, row) => total + row.quantity);
  }

  Future<List<StockEntry>> searchStock({
    required int organizationId,
    required int shopId,
    String query = '',
  }) async {
    final stockRows =
        await (_database.select(_database.stockLevels)..where(
              (row) =>
                  row.organizationId.equals(organizationId) &
                  row.shopId.equals(shopId),
            ))
            .get();
    if (stockRows.isEmpty) return const [];

    final productIds = stockRows
        .map((row) => row.productId)
        .whereType<int>()
        .toSet()
        .toList();
    final variantIds = stockRows
        .map((row) => row.variantId)
        .whereType<int>()
        .toSet()
        .toList();
    final productRows = productIds.isEmpty
        ? const <db.Product>[]
        : await (_database.select(_database.products)..where(
                (row) =>
                    row.organizationId.equals(organizationId) &
                    row.id.isIn(productIds),
              ))
              .get();
    final variantRows = variantIds.isEmpty
        ? const <db.ProductVariant>[]
        : await (_database.select(_database.productVariants)..where(
                (row) =>
                    row.organizationId.equals(organizationId) &
                    row.id.isIn(variantIds),
              ))
              .get();
    final products = {for (final row in productRows) row.id: row};
    final variants = {for (final row in variantRows) row.id: row};
    final normalized = query.trim().toLowerCase();

    return stockRows
        .map((row) {
          final variant = row.variantId == null
              ? null
              : variants[row.variantId];
          final product = row.productId == null
              ? null
              : products[row.productId];
          final label = variant?.name ?? product?.name ?? 'Article inconnu';
          final sku = variant?.sku ?? product?.sku;
          final barcode = variant?.barcode ?? product?.barcode;
          return StockEntry(
            label: label,
            sku: sku,
            barcode: barcode,
            quantity: row.quantity,
            isVariant: variant != null,
            location: _locationLabel(db.decodeRaw(row.rawJson)),
          );
        })
        .where(
          (entry) =>
              normalized.isEmpty ||
              entry.label.toLowerCase().contains(normalized) ||
              (entry.sku?.toLowerCase().contains(normalized) ?? false) ||
              (entry.barcode?.toLowerCase().contains(normalized) ?? false),
        )
        .toList()
      ..sort((a, b) => a.label.compareTo(b.label));
  }
}

class StockEntry {
  const StockEntry({
    required this.label,
    required this.sku,
    required this.barcode,
    required this.quantity,
    required this.isVariant,
    required this.location,
  });

  final String label;
  final String? sku;
  final String? barcode;
  final double quantity;
  final bool isVariant;
  final String? location;
}

int _requiredInt(Object? value, String field) =>
    _int(value) ?? (throw StateError('$field est requis dans le bootstrap.'));

int? _int(Object? value) => value is int ? value : int.tryParse('$value');
double _double(Object? value) => double.tryParse('$value') ?? 0;
double? _nullableDouble(Object? value) => value == null ? null : _double(value);
int? _stockShopId(Map<String, dynamic> row) =>
    _int(row['shop_id']) ?? _int((row['location'] as Map?)?['shop_id']);

String? _locationLabel(Map<String, dynamic> row) {
  final location = row['location'];
  if (location is Map) {
    return location['name'] as String? ?? location['code'] as String?;
  }
  return row['stock_location_name'] as String?;
}
