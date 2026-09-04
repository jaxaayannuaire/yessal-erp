String formatFcfa(int amount) {
  final value = amount.abs().toString();
  final groups = <String>[];
  for (var end = value.length; end > 0; end -= 3) {
    groups.add(value.substring(end > 3 ? end - 3 : 0, end));
  }
  return '${amount.isNegative ? '-' : ''}${groups.reversed.join(' ')} FCFA';
}

String formatQuantity(double quantity) {
  return quantity == quantity.roundToDouble()
      ? quantity.toInt().toString()
      : quantity.toStringAsFixed(2);
}
