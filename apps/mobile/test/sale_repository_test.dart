import 'dart:convert';

import 'package:flutter/services.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:yessal_caisse/core/api/api_client.dart';
import 'package:yessal_caisse/core/errors/api_exception.dart';
import 'package:yessal_caisse/core/storage/token_storage.dart';
import 'package:yessal_caisse/features/sales/cart.dart';
import 'package:yessal_caisse/features/sales/sale_repository.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  const channel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');
  setUp(() {
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(
          channel,
          (call) async => call.method == 'read' ? null : null,
        );
  });

  ApiClient client(RecordingClient httpClient) {
    final api = ApiClient(
      client: httpClient,
      tokenStorage: TokenStorage(storage: const FlutterSecureStorage()),
    )..organizationId = 7;
    return api;
  }

  test('createSale sends the exact tenant payload for a variant', () async {
    final httpClient = RecordingClient.success({'sale': _sale(1, 2000)});
    final repository = SaleRepository(client(httpClient));
    await repository.createSale(
      shopId: 2,
      terminalId: 3,
      cashSessionId: 4,
      localUuid: '123e4567-e89b-42d3-a456-426614174000',
      receiptNumber: 'MOB-001',
      customerId: 9,
      items: [
        const CartItem(
          productId: 10,
          variantId: 11,
          name: 'Variante',
          unitPrice: 2000,
          quantity: 1,
        ),
      ],
    );
    final body =
        jsonDecode(httpClient.requests.single.body) as Map<String, dynamic>;
    expect(httpClient.requests.single.headers['X-Organization-Id'], '7');
    expect(body, containsPair('customer_id', 9));
    expect(body['cash_session_id'], 4);
    expect(body['local_uuid'], '123e4567-e89b-42d3-a456-426614174000');
    expect(body['receipt_number'], 'MOB-001');
    expect(body['currency'], 'XOF');
    expect(body['lines'].single['product_variant_id'], 11);
    expect(body['lines'].single.containsKey('product_id'), isFalse);
  });

  test('payment, show, finalize and API errors use server responses', () async {
    final httpClient = RecordingClient.queue([
      http.Response(jsonEncode({'sale': _sale(1, 500)}), 200),
      http.Response(jsonEncode({'sale': _sale(1, 0)}), 201),
      http.Response(
        jsonEncode({
          'sale': {..._sale(1, 0), 'status': 'finalized'},
        }),
        200,
      ),
      http.Response(
        jsonEncode({
          'message': 'Stock insuffisant',
          'errors': {
            'sale': ['Stock insuffisant'],
          },
        }),
        422,
      ),
    ]);
    final repository = SaleRepository(client(httpClient));
    expect((await repository.getSale(1)).due, 500);
    expect((await repository.makeCashPayment(1, 500)).due, 0);
    expect((await repository.finalizeSale(1)).raw['status'], 'finalized');
    await expectLater(
      repository.finalizeSale(1),
      throwsA(isA<ApiException>().having((e) => e.statusCode, 'status', 422)),
    );
    expect(jsonDecode(httpClient.requests[1].body)['amount'], 500);
  });
}

Map<String, dynamic> _sale(int id, int due) => {
  'id': id,
  'total_amount': 2000,
  'due_amount': due,
};

class RecordingClient extends http.BaseClient {
  RecordingClient.success(Map<String, dynamic> body)
    : _responses = [http.Response(jsonEncode(body), 201)];
  RecordingClient.queue(this._responses);
  final List<http.Response> _responses;
  final requests = <http.Request>[];
  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) async {
    requests.add(request as http.Request);
    final response = _responses.removeAt(0);
    return http.StreamedResponse(
      Stream.value(response.bodyBytes),
      response.statusCode,
      headers: response.headers,
    );
  }
}
