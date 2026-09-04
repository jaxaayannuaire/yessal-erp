import '../../core/api/api_client.dart';
import 'cart.dart';

class SaleRecord {
  const SaleRecord(this.raw);
  final Map<String, dynamic> raw;
  int get id => raw['id'] as int;
  int get total => (raw['total_amount'] ?? 0) as int;
  int get due => (raw['due_amount'] ?? 0) as int;
  String? get receiptNumber => raw['receipt_number'] as String?;
}

class SaleRepository {
  SaleRepository(this._api);
  final ApiClient _api;

  Future<int?> activeCashSession(int terminalId) async {
    final response = await _api.get('/caisse/cash-sessions');
    final sessions = (response['cash_sessions'] as List? ?? const [])
        .cast<Map<String, dynamic>>();
    for (final session in sessions) {
      if (session['terminal_id'] == terminalId && session['status'] == 'open') {
        return session['id'] as int;
      }
    }
    return null;
  }

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
    final response = await _api.post(
      '/caisse/sales',
      body: {
        'shop_id': shopId,
        'terminal_id': terminalId,
        'cash_session_id': cashSessionId,
        'device_id': ?deviceId,
        'customer_id': ?customerId,
        'local_uuid': localUuid,
        'receipt_number': receiptNumber,
        'currency': 'XOF',
        'lines': items
            .map(
              (item) => {
                if (item.variantId == null) 'product_id': item.productId,
                if (item.variantId != null)
                  'product_variant_id': item.variantId,
                'quantity': item.quantity,
                'unit_price': item.unitPrice,
              },
            )
            .toList(),
      },
    );
    return SaleRecord((response['sale'] as Map).cast<String, dynamic>());
  }

  Future<SaleRecord> getSale(int saleId) async {
    final response = await _api.get('/caisse/sales/$saleId');
    return SaleRecord((response['sale'] as Map).cast<String, dynamic>());
  }

  Future<SaleRecord> makeCashPayment(int saleId, int amount) async {
    final response = await _api.post(
      '/caisse/sales/$saleId/payments/cash',
      body: {'amount': amount},
    );
    return SaleRecord((response['sale'] as Map).cast<String, dynamic>());
  }

  Future<SaleRecord> finalizeSale(int saleId) async {
    final response = await _api.post('/caisse/sales/$saleId/finalize');
    return SaleRecord((response['sale'] as Map).cast<String, dynamic>());
  }
}
