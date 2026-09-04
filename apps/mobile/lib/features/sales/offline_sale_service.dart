import 'dart:convert';

import 'package:drift/drift.dart';

import '../../core/database/app_database.dart' as db;
import '../../core/sync/outbox_repository.dart';
import 'cart.dart';
import 'sale_identifiers.dart';

class OfflineSaleException implements Exception {
  const OfflineSaleException(this.message);

  final String message;

  @override
  String toString() => message;
}

/// Creates immutable local `sale/create` snapshots without contacting the API.
class OfflineSaleService {
  OfflineSaleService(
    this._database,
    this._outbox, {
    DateTime Function()? clock,
    String Function()? uuidGenerator,
  }) : _clock = clock ?? (() => DateTime.now().toUtc()),
       _uuidGenerator = uuidGenerator ?? generateUuidV4;

  final db.AppDatabase _database;
  final OutboxRepository _outbox;
  final DateTime Function() _clock;
  final String Function() _uuidGenerator;

  Future<OutboxEvent> createOfflineSale({
    required int organizationId,
    required int shopId,
    required int terminalId,
    required int deviceId,
    required List<CartItem> items,
    int? customerId,
  }) async {
    _validateContext(
      organizationId: organizationId,
      shopId: shopId,
      terminalId: terminalId,
      deviceId: deviceId,
    );
    if (items.isEmpty) throw const OfflineSaleException('Le panier est vide.');
    if (items.any((item) => item.quantity <= 0 || item.unitPrice < 0)) {
      throw const OfflineSaleException(
        'Le panier contient une ligne invalide.',
      );
    }

    final session =
        await (_database.select(_database.cashSessions)
              ..where(
                (row) =>
                    row.organizationId.equals(organizationId) &
                    row.shopId.equals(shopId) &
                    row.terminalId.equals(terminalId) &
                    row.status.equals('open'),
              )
              ..orderBy([(row) => OrderingTerm.desc(row.id)])
              ..limit(1))
            .getSingleOrNull();
    if (session == null) {
      throw const OfflineSaleException(
        'Une session de caisse ouverte est requise.',
      );
    }

    final eventUuid = _uuidGenerator();
    final localUuid = _uuidGenerator();
    final receiptNumber = receiptNumberFor(localUuid);
    final total = items.fold<int>(0, (sum, item) => sum + item.subtotal);
    final occurredAt = _clock().toUtc();
    final payload = <String, dynamic>{
      'terminal_id': terminalId,
      'cash_session_id': session.id,
      'local_uuid': localUuid,
      'receipt_number': receiptNumber,
      'currency': 'XOF',
      'customer_id': ?customerId,
      'lines': items
          .map(
            (item) => <String, dynamic>{
              if (item.variantId == null) 'product_id': item.productId,
              if (item.variantId != null) 'product_variant_id': item.variantId,
              'quantity': item.quantity,
              'unit_price': item.unitPrice,
            },
          )
          .toList(growable: false),
      'payment': <String, dynamic>{'method': 'cash', 'amount': total},
      'finalize': true,
    };
    final payloadJson = jsonEncode(payload);

    return _outbox.enqueue(
      organizationId: organizationId,
      shopId: shopId,
      deviceId: deviceId,
      eventUuid: eventUuid,
      entityType: 'sale',
      entityId: localUuid,
      action: 'create',
      payloadJson: payloadJson,
      occurredAt: occurredAt,
    );
  }

  void _validateContext({
    required int organizationId,
    required int shopId,
    required int terminalId,
    required int deviceId,
  }) {
    if (organizationId <= 0 ||
        shopId <= 0 ||
        terminalId <= 0 ||
        deviceId <= 0) {
      throw const OfflineSaleException('Le contexte de caisse est invalide.');
    }
  }
}
