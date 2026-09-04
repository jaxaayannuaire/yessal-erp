import 'dart:convert';

import 'package:drift/drift.dart';
import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:yessal_caisse/core/database/app_database.dart';
import 'package:yessal_caisse/core/sync/outbox_repository.dart';
import 'package:yessal_caisse/features/sales/cart.dart';
import 'package:yessal_caisse/features/sales/offline_sale_service.dart';

void main() {
  group('OfflineSaleService', () {
    test('snapshots a simple product sale with cash payment', () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      await _insertSession(database);
      final service = _service(database);

      final event = await service.createOfflineSale(
        organizationId: 1,
        shopId: 10,
        terminalId: 20,
        deviceId: 30,
        items: const [
          CartItem(
            productId: 100,
            variantId: null,
            name: 'Riz',
            unitPrice: 1000,
            quantity: 2,
          ),
        ],
      );
      final payload = _payload(event);
      final line = (payload['lines'] as List).single as Map<String, dynamic>;

      expect(event.organizationId, 1);
      expect(event.shopId, 10);
      expect(event.deviceId, 30);
      expect(event.entityType, 'sale');
      expect(event.action, 'create');
      expect(event.status, OutboxStatus.queued);
      expect(event.attemptCount, 0);
      expect(event.eventUuid, matches(_uuidPattern));
      expect(payload['local_uuid'], matches(_uuidPattern));
      expect(event.entityId, payload['local_uuid']);
      expect(payload['receipt_number'], 'MOB-11112222333344448555');
      expect(
        (payload['receipt_number'] as String).length,
        lessThanOrEqualTo(100),
      );
      expect(payload['terminal_id'], 20);
      expect(payload['cash_session_id'], 300);
      expect(payload['currency'], 'XOF');
      expect(payload.containsKey('customer_id'), isFalse);
      expect(line['product_id'], 100);
      expect(line.containsKey('product_variant_id'), isFalse);
      expect(line['quantity'], 2);
      expect(line['unit_price'], 1000);
      expect(payload['payment'], {'method': 'cash', 'amount': 2000});
      expect(payload['finalize'], isTrue);
    });

    test(
      'snapshots variants and several lines with the selected customer',
      () async {
        final database = AppDatabase(NativeDatabase.memory());
        addTearDown(database.close);
        await _insertSession(database);
        final event = await _service(database).createOfflineSale(
          organizationId: 1,
          shopId: 10,
          terminalId: 20,
          deviceId: 30,
          customerId: 900,
          items: const [
            CartItem(
              productId: 100,
              variantId: 101,
              name: 'Riz 5 kg',
              unitPrice: 2500,
              quantity: 3,
            ),
            CartItem(
              productId: 200,
              variantId: null,
              name: 'Huile',
              unitPrice: 1500,
              quantity: 1,
            ),
          ],
        );
        final payload = _payload(event);
        final lines = (payload['lines'] as List).cast<Map<String, dynamic>>();

        expect(payload['customer_id'], 900);
        expect(lines[0]['product_variant_id'], 101);
        expect(lines[0].containsKey('product_id'), isFalse);
        expect(lines[0]['quantity'], 3);
        expect(lines[0]['unit_price'], 2500);
        expect(lines[1]['product_id'], 200);
        expect(lines[1].containsKey('product_variant_id'), isFalse);
        expect((payload['payment'] as Map)['amount'], 9000);
      },
    );

    test('uses only the matching local open cash session', () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      await _insertSession(database, id: 1, status: 'closed');
      await _insertSession(database, id: 2, terminalId: 99);
      await _insertSession(database, id: 3, organizationId: 2);
      await _insertSession(database, id: 4, status: 'open');

      final event = await _service(database).createOfflineSale(
        organizationId: 1,
        shopId: 10,
        terminalId: 20,
        deviceId: 30,
        items: _items,
      );

      expect(_payload(event)['cash_session_id'], 4);
    });

    test(
      'rejects a missing session, an empty cart and invalid context',
      () async {
        final database = AppDatabase(NativeDatabase.memory());
        addTearDown(database.close);
        final service = _service(database);

        await expectLater(
          service.createOfflineSale(
            organizationId: 1,
            shopId: 10,
            terminalId: 20,
            deviceId: 30,
            items: _items,
          ),
          throwsA(
            isA<OfflineSaleException>().having(
              (error) => error.message,
              'message',
              'Une session de caisse ouverte est requise.',
            ),
          ),
        );
        await _insertSession(database);
        await expectLater(
          service.createOfflineSale(
            organizationId: 1,
            shopId: 10,
            terminalId: 20,
            deviceId: 30,
            items: const [],
          ),
          throwsA(isA<OfflineSaleException>()),
        );
        await expectLater(
          service.createOfflineSale(
            organizationId: 0,
            shopId: 10,
            terminalId: 20,
            deviceId: 30,
            items: _items,
          ),
          throwsA(isA<OfflineSaleException>()),
        );
        expect(await _outboxCount(database), 0);
      },
    );

    test('keeps the JSON snapshot immutable after cart changes', () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      await _insertSession(database);
      final cart = CartState()
        ..add(
          const CartItem(
            productId: 100,
            variantId: null,
            name: 'Produit A',
            unitPrice: 1000,
            quantity: 2,
          ),
        );
      final repository = OutboxRepository(database);
      final event =
          await OfflineSaleService(
            database,
            repository,
            uuidGenerator: _uuidGenerator(),
          ).createOfflineSale(
            organizationId: 1,
            shopId: 10,
            terminalId: 20,
            deviceId: 30,
            items: cart.items,
          );
      cart.clear();
      cart.add(
        const CartItem(
          productId: 200,
          variantId: null,
          name: 'Produit B',
          unitPrice: 9999,
          quantity: 1,
        ),
      );

      final stored = await repository.findById(organizationId: 1, id: event.id);
      final payload = _payload(stored!);
      final line = (payload['lines'] as List).single as Map<String, dynamic>;
      expect(line['product_id'], 100);
      expect(line['quantity'], 2);
      expect(line['unit_price'], 1000);
      expect((payload['payment'] as Map)['amount'], 2000);
    });

    test(
      'does not change local stock and preserves tenant isolation',
      () async {
        final database = AppDatabase(NativeDatabase.memory());
        addTearDown(database.close);
        await _insertSession(database);
        await database
            .into(database.stockLevels)
            .insert(
              StockLevelsCompanion.insert(
                organizationId: 1,
                shopId: 10,
                stockLocationId: 50,
                stockIdentity: 'product:100',
                productId: const Value(100),
                variantId: const Value(null),
                quantity: 1,
                rawJson: '{}',
              ),
            );
        final event = await _service(database).createOfflineSale(
          organizationId: 1,
          shopId: 10,
          terminalId: 20,
          deviceId: 30,
          items: const [
            CartItem(
              productId: 100,
              variantId: null,
              name: 'Riz',
              unitPrice: 500,
              quantity: 3,
            ),
          ],
        );

        expect(await database.select(database.stockLevels).get(), hasLength(1));
        expect(
          (await database.select(database.stockLevels).getSingle()).quantity,
          1,
        );
        expect(await OutboxRepository(database).listByOrganization(2), isEmpty);
        expect(event.organizationId, 1);
      },
    );

    test('creates new identifiers for each new logical sale', () async {
      final database = AppDatabase(NativeDatabase.memory());
      addTearDown(database.close);
      await _insertSession(database);
      final service = OfflineSaleService(
        database,
        OutboxRepository(database),
        uuidGenerator: _uuidGenerator(),
      );

      final first = await service.createOfflineSale(
        organizationId: 1,
        shopId: 10,
        terminalId: 20,
        deviceId: 30,
        items: _items,
      );
      final second = await service.createOfflineSale(
        organizationId: 1,
        shopId: 10,
        terminalId: 20,
        deviceId: 30,
        items: _items,
      );

      expect(first.eventUuid, isNot(second.eventUuid));
      expect(first.entityId, isNot(second.entityId));
      expect(
        _payload(first)['receipt_number'],
        isNot(_payload(second)['receipt_number']),
      );
    });
  });
}

final _uuidPattern = RegExp(
  r'^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
);

const _items = [
  CartItem(
    productId: 100,
    variantId: null,
    name: 'Riz',
    unitPrice: 1000,
    quantity: 1,
  ),
];

OfflineSaleService _service(AppDatabase database) => OfflineSaleService(
  database,
  OutboxRepository(database),
  uuidGenerator: _uuidGenerator(),
);

String Function() _uuidGenerator() {
  final values = <String>[
    '00112233-4455-4677-8899-aabbccddeeff',
    '11112222-3333-4444-8555-666677778888',
    '21112222-3333-4444-8555-666677778888',
    '31112222-3333-4444-8555-666677778888',
  ];
  return () => values.removeAt(0);
}

Future<void> _insertSession(
  AppDatabase database, {
  int organizationId = 1,
  int shopId = 10,
  int terminalId = 20,
  int id = 300,
  String status = 'open',
}) => database
    .into(database.cashSessions)
    .insert(
      CashSessionsCompanion.insert(
        organizationId: organizationId,
        id: id,
        shopId: Value(shopId),
        terminalId: Value(terminalId),
        status: Value(status),
        rawJson: '{}',
      ),
    );

Map<String, dynamic> _payload(OutboxEvent event) =>
    (jsonDecode(event.payloadJson) as Map).cast<String, dynamic>();

Future<int> _outboxCount(AppDatabase database) async =>
    (await database.select(database.syncOutbox).get()).length;
