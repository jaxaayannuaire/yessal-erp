import 'dart:math';

import '../../core/errors/api_exception.dart';
import 'cart.dart';
import 'sale_repository.dart';

enum SaleProgress { idle, saleCreated, paymentConfirmed, finalized, failed }

class SaleController {
  SaleController(this.repository);
  final SaleRepository repository;
  String? localUuid;
  String? receiptNumber;
  SaleRecord? sale;
  bool paymentConfirmed = false;
  SaleProgress progress = SaleProgress.idle;

  void beginAttempt() {
    localUuid ??= _uuid();
    receiptNumber ??= 'MOB-${localUuid!.replaceAll('-', '').substring(0, 20)}';
  }

  Future<SaleRecord> submit({
    required int shopId,
    required int terminalId,
    required int cashSessionId,
    required String receiptNumber,
    required List<CartItem> items,
    int? customerId,
    int? deviceId,
  }) async {
    try {
      sale ??= await repository.createSale(
        shopId: shopId,
        terminalId: terminalId,
        cashSessionId: cashSessionId,
        localUuid: localUuid!,
        receiptNumber: receiptNumber,
        items: items,
        customerId: customerId,
        deviceId: deviceId,
      );
      progress = SaleProgress.saleCreated;
      if (!paymentConfirmed) {
        final current = await repository.getSale(sale!.id);
        sale = current;
        if (current.due > 0) {
          sale = await repository.makeCashPayment(current.id, current.due);
        }
        paymentConfirmed = sale!.due == 0;
      }
      progress = SaleProgress.paymentConfirmed;
      sale = await repository.finalizeSale(sale!.id);
      progress = SaleProgress.finalized;
      return sale!;
    } on ApiException {
      progress = SaleProgress.failed;
      rethrow;
    }
  }
}

String _uuid() {
  final random = Random.secure();
  final bytes = List.generate(16, (_) => random.nextInt(256));
  bytes[6] = (bytes[6] & 0x0f) | 0x40;
  bytes[8] = (bytes[8] & 0x3f) | 0x80;
  final hex = bytes
      .map((value) => value.toRadixString(16).padLeft(2, '0'))
      .join();
  return '${hex.substring(0, 8)}-${hex.substring(8, 12)}-${hex.substring(12, 16)}-${hex.substring(16, 20)}-${hex.substring(20)}';
}
