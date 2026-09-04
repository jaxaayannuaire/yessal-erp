import 'package:flutter_test/flutter_test.dart';
import 'package:yessal_caisse/core/api/api_client.dart';
import 'package:yessal_caisse/features/sales/cart.dart';
import 'package:yessal_caisse/features/sales/sale_controller.dart';
import 'package:yessal_caisse/features/sales/sale_repository.dart';

void main() {
  test('retry after payment failure does not create a second sale', () async {
    final repository = _FakeSales(paymentFailsOnce: true);
    final controller = SaleController(repository)..beginAttempt();
    final receipt = controller.receiptNumber;
    await expectLater(_submit(controller), throwsA(isA<Object>()));
    await _submit(controller);
    expect(repository.createCalls, 1);
    expect(controller.receiptNumber, receipt);
  });

  test('retry after finalization failure does not pay twice', () async {
    final repository = _FakeSales(finalizeFailsOnce: true);
    final controller = SaleController(repository)..beginAttempt();
    await expectLater(_submit(controller), throwsA(isA<Object>()));
    await _submit(controller);
    expect(repository.createCalls, 1);
    expect(repository.paymentCalls, 1);
  });

  test(
    'identifiers remain stable when creation fails before a retry',
    () async {
      final repository = _FakeSales(createFailsOnce: true);
      final controller = SaleController(repository)..beginAttempt();
      final uuid = controller.localUuid;
      final receipt = controller.receiptNumber;
      await expectLater(_submit(controller), throwsA(isA<Object>()));
      await _submit(controller);
      expect(repository.uuids, [uuid, uuid]);
      expect(repository.receipts, [receipt, receipt]);
    },
  );
}

Future<void> _submit(SaleController controller) => controller.submit(
  shopId: 1,
  terminalId: 2,
  cashSessionId: 3,
  receiptNumber: controller.receiptNumber!,
  items: const [
    CartItem(
      productId: 4,
      variantId: null,
      name: 'Article',
      unitPrice: 1000,
      quantity: 1,
    ),
  ],
);

class _FakeSales extends SaleRepository {
  _FakeSales({
    this.createFailsOnce = false,
    this.paymentFailsOnce = false,
    this.finalizeFailsOnce = false,
  }) : super(ApiClient());
  bool createFailsOnce;
  bool paymentFailsOnce;
  bool finalizeFailsOnce;
  int createCalls = 0;
  int paymentCalls = 0;
  final uuids = <String?>[];
  final receipts = <String?>[];
  SaleRecord _sale = const SaleRecord({
    'id': 1,
    'total_amount': 1000,
    'due_amount': 1000,
  });
  @override
  Future<SaleRecord> createSale({
    required int shopId,
    required int terminalId,
    required int cashSessionId,
    required String localUuid,
    required String receiptNumber,
    required List<CartItem> items,
    int? customerId,
    int? deviceId,
  }) async {
    createCalls++;
    uuids.add(localUuid);
    receipts.add(receiptNumber);
    if (createFailsOnce) {
      createFailsOnce = false;
      throw StateError('network');
    }
    return _sale;
  }

  @override
  Future<SaleRecord> getSale(int saleId) async => _sale;
  @override
  Future<SaleRecord> makeCashPayment(int saleId, int amount) async {
    paymentCalls++;
    if (paymentFailsOnce) {
      paymentFailsOnce = false;
      throw StateError('network');
    }
    return _sale = const SaleRecord({
      'id': 1,
      'total_amount': 1000,
      'due_amount': 0,
    });
  }

  @override
  Future<SaleRecord> finalizeSale(int saleId) async {
    if (finalizeFailsOnce) {
      finalizeFailsOnce = false;
      throw StateError('network');
    }
    return _sale = const SaleRecord({
      'id': 1,
      'total_amount': 1000,
      'due_amount': 0,
      'status': 'finalized',
    });
  }
}
