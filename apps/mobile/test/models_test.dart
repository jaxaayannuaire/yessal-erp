import 'package:flutter_test/flutter_test.dart';
import 'package:yessal_caisse/core/models/caisse_models.dart';

void main() {
  test('Product preserves the Laravel product payload', () {
    final product = Product.fromJson({
      'id': 7,
      'name': 'Riz',
      'sale_price': 1500,
      'shop_id': 2,
    });

    expect(product.id, 7);
    expect(product.salePrice, 1500);
    expect(product.toJson()['shop_id'], 2);
  });

  test('Customer parses optional contact fields', () {
    final customer = Customer.fromJson({
      'id': 5,
      'name': 'Awa Ndiaye',
      'phone': '770000000',
      'email': null,
    });

    expect(customer.phone, '770000000');
    expect(customer.email, isNull);
  });

  test('Entitlements blocks Caisse when pos.sell is absent', () {
    expect(Entitlements.fromJson({'entitlements': []}).allowed, isFalse);
    expect(
      Entitlements.fromJson({
        'entitlements': [
          {'slug': 'pos.sell'},
        ],
      }).allowed,
      isTrue,
    );
  });
}
