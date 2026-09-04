import '../../core/errors/api_exception.dart';
import 'cart.dart';
import 'sale_identifiers.dart';
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
    localUuid ??= generateUuidV4();
    receiptNumber ??= receiptNumberFor(localUuid!);
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
