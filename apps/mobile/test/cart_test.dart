import 'package:flutter_test/flutter_test.dart';
import 'package:yessal_caisse/features/sales/cart.dart';

void main() {
  CartItem product({int? variantId, int price = 1000}) => CartItem(
    productId: 1,
    variantId: variantId,
    name: variantId == null ? 'Produit' : 'Variante $variantId',
    unitPrice: price,
    quantity: 1,
  );

  test('merges the same product and keeps variants distinct', () {
    final cart = CartState();
    cart.add(product());
    cart.add(product());
    cart.add(product(variantId: 2));
    cart.add(product(variantId: 3));

    expect(cart.items, hasLength(3));
    expect(cart.items.first.quantity, 2);
  });

  test('changes quantities, removes lines and calculates total', () {
    final cart = CartState();
    cart.add(product(price: 1500));
    cart.setQuantity(cart.items.single.key, 3);
    expect(cart.total, 4500);
    cart.setQuantity(cart.items.single.key, 0);
    expect(cart.items, isEmpty);
    cart.add(product());
    cart.clear();
    expect(cart.total, 0);
  });
}
