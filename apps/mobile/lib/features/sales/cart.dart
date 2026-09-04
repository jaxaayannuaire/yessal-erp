import 'package:flutter/foundation.dart';

class CartItem {
  const CartItem({
    required this.productId,
    required this.variantId,
    required this.name,
    required this.unitPrice,
    required this.quantity,
    this.availableStock,
  });

  final int productId;
  final int? variantId;
  final String name;
  final int unitPrice;
  final int quantity;
  final double? availableStock;
  int get subtotal => unitPrice * quantity;
  String get key => '$productId:${variantId ?? 'product'}';

  CartItem copyWith({int? quantity}) => CartItem(
    productId: productId,
    variantId: variantId,
    name: name,
    unitPrice: unitPrice,
    quantity: quantity ?? this.quantity,
    availableStock: availableStock,
  );
}

class CartState extends ChangeNotifier {
  final List<CartItem> _items = [];
  List<CartItem> get items => List.unmodifiable(_items);
  int get total => _items.fold(0, (sum, item) => sum + item.subtotal);
  int get itemCount => _items.fold(0, (sum, item) => sum + item.quantity);

  void add(CartItem item) {
    final index = _items.indexWhere((current) => current.key == item.key);
    if (index < 0) {
      _items.add(item);
    } else {
      _items[index] = _items[index].copyWith(
        quantity: _items[index].quantity + item.quantity,
      );
    }
    notifyListeners();
  }

  void setQuantity(String key, int quantity) {
    final index = _items.indexWhere((item) => item.key == key);
    if (index < 0) return;
    if (quantity <= 0) {
      _items.removeAt(index);
    } else {
      _items[index] = _items[index].copyWith(quantity: quantity);
    }
    notifyListeners();
  }

  void remove(String key) {
    _items.removeWhere((item) => item.key == key);
    notifyListeners();
  }

  void clear() {
    _items.clear();
    notifyListeners();
  }
}
