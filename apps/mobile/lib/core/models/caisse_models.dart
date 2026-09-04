class UserProfile {
  const UserProfile({
    required this.id,
    required this.name,
    required this.email,
  });
  final int id;
  final String name;
  final String email;
  factory UserProfile.fromJson(Map<String, dynamic> json) => UserProfile(
    id: json['id'] as int,
    name: json['name'] as String,
    email: json['email'] as String,
  );
  Map<String, dynamic> toJson() => {'id': id, 'name': name, 'email': email};
}

class Organization {
  const Organization({required this.id, required this.name});
  final int id;
  final String name;
  factory Organization.fromJson(Map<String, dynamic> json) =>
      Organization(id: json['id'] as int, name: json['name'] as String);
  Map<String, dynamic> toJson() => {'id': id, 'name': name};
}

class CaisseEntity {
  const CaisseEntity({
    required this.id,
    required this.name,
    this.raw = const {},
  });
  final int id;
  final String name;
  final Map<String, dynamic> raw;
  factory CaisseEntity.fromJson(Map<String, dynamic> json) => CaisseEntity(
    id: json['id'] as int,
    name: (json['name'] ?? json['code'] ?? '#${json['id']}') as String,
    raw: json,
  );
  Map<String, dynamic> toJson() => raw.isEmpty ? {'id': id, 'name': name} : raw;
}

class Product {
  const Product({
    required this.id,
    required this.name,
    required this.salePrice,
    required this.raw,
  });
  final int id;
  final String name;
  final int salePrice;
  final Map<String, dynamic> raw;
  factory Product.fromJson(Map<String, dynamic> json) => Product(
    id: json['id'] as int,
    name: json['name'] as String,
    salePrice: (json['sale_price'] ?? 0) as int,
    raw: json,
  );
  Map<String, dynamic> toJson() => raw;
}

class ProductVariant {
  const ProductVariant({
    required this.id,
    required this.productId,
    required this.name,
    required this.salePrice,
    required this.raw,
  });
  final int id;
  final int productId;
  final String name;
  final int salePrice;
  final Map<String, dynamic> raw;
  factory ProductVariant.fromJson(Map<String, dynamic> json) => ProductVariant(
    id: json['id'] as int,
    productId: json['product_id'] as int,
    name: json['name'] as String,
    salePrice: (json['sale_price'] ?? 0) as int,
    raw: json,
  );
  Map<String, dynamic> toJson() => raw;
}

class Customer {
  const Customer({
    required this.id,
    required this.name,
    this.phone,
    this.email,
    required this.raw,
  });
  final int id;
  final String name;
  final String? phone;
  final String? email;
  final Map<String, dynamic> raw;
  factory Customer.fromJson(Map<String, dynamic> json) => Customer(
    id: json['id'] as int,
    name: json['name'] as String,
    phone: json['phone'] as String?,
    email: json['email'] as String?,
    raw: json,
  );
  Map<String, dynamic> toJson() => raw;
}

class Entitlements {
  const Entitlements({
    required this.allowed,
    required this.entitlements,
    required this.raw,
  });
  final bool allowed;
  final Set<String> entitlements;
  final Map<String, dynamic> raw;
  factory Entitlements.fromJson(Map<String, dynamic> json) {
    final values = ((json['entitlements'] ?? []) as List)
        .map((item) => (item as Map<String, dynamic>)['slug'] as String)
        .toSet();
    return Entitlements(
      allowed: values.contains('pos.sell'),
      entitlements: values,
      raw: json,
    );
  }
  Map<String, dynamic> toJson() => raw;
}
