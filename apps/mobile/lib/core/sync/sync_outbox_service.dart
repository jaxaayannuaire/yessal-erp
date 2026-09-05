import 'dart:convert';

import '../api/api_client.dart';
import '../bootstrap/bootstrap_repository.dart';
import '../errors/api_exception.dart';
import 'outbox_repository.dart';
import 'outbox_retry_policy.dart';

typedef OutboxRefresh = Future<void> Function({
  required int organizationId,
  required int shopId,
});

class SyncOutboxException implements Exception {
  const SyncOutboxException(this.message);

  final String message;

  @override
  String toString() => message;
}

/// Replays exactly one queued outbox event. Queue orchestration stays outside
/// this service so callers can decide when a network attempt is appropriate.
class SyncOutboxService {
  SyncOutboxService(
    this._api,
    this._outbox, {
    BootstrapRepository? bootstrap,
    OutboxRefresh? refresh,
  }) : assert(bootstrap == null || refresh == null),
       _refresh =
           refresh ??
           (bootstrap == null
               ? null
               : ({required int organizationId, required int shopId}) async {
                   await bootstrap.bootstrap(organizationId, shopId);
                 });

  final ApiClient _api;
  final OutboxRepository _outbox;
  final OutboxRefresh? _refresh;
  Future<List<OutboxEvent>>? _activePendingSync;
  Future<OutboxEvent>? _activeRetry;
  final _retryPolicy = const OutboxRetryPolicy();

  /// Replays the current tenant's queued events in FIFO order, one request at
  /// a time. A retryable network failure stops this run to avoid a burst.
  Future<List<OutboxEvent>> syncPending(int organizationId) {
    final active = _activePendingSync;
    if (active != null) return active;

    late final Future<List<OutboxEvent>> run;
    run = _syncPending(organizationId).whenComplete(() {
      if (identical(_activePendingSync, run)) _activePendingSync = null;
    });
    _activePendingSync = run;
    return run;
  }

  Future<List<OutboxEvent>> _syncPending(int organizationId) async {
    if (_api.organizationId != organizationId) {
      throw const SyncOutboxException(
        'Le contexte d’organisation de synchronisation est incohérent.',
      );
    }
    final pending = await _outbox.listPending(organizationId);
    final results = <OutboxEvent>[];
    for (final event in pending) {
      final result = await syncOne(
        organizationId: organizationId,
        outboxId: event.id,
      );
      results.add(result);
      if (result.status == OutboxStatus.queued) break;
    }
    final applied = results.where(
      (event) => event.status == OutboxStatus.applied,
    );
    if (applied.isNotEmpty && _refresh != null) {
      final shopId = applied.first.shopId;
      await _refresh(organizationId: organizationId, shopId: shopId);
    }
    return results;
  }

  Future<OutboxEvent> syncOne({
    required int organizationId,
    required int outboxId,
  }) async {
    final event = await _outbox.findById(
      organizationId: organizationId,
      id: outboxId,
    );
    if (event == null) {
      throw const SyncOutboxException(
        'Événement de synchronisation introuvable.',
      );
    }
    if (event.status != OutboxStatus.queued) {
      throw const SyncOutboxException(
        'Cet événement n’est pas prêt à être synchronisé.',
      );
    }
    if (_api.organizationId != organizationId) {
      throw const SyncOutboxException(
        'Le contexte d’organisation de synchronisation est incohérent.',
      );
    }

    final sending = await _outbox.markSending(
      organizationId: organizationId,
      id: outboxId,
    );
    if (sending == null) {
      throw const SyncOutboxException(
        'Cet événement n’est plus prêt à être synchronisé.',
      );
    }

    final payload = _decodePayload(sending.payloadJson);
    if (payload == null) {
      return _required(
        _outbox.markFailed(
          organizationId: organizationId,
          id: outboxId,
          error: 'Le payload de synchronisation local est invalide.',
          failureKind: OutboxFailureKind.localPayloadInvalid,
        ),
      );
    }

    try {
      final response = await _api.post(
        '/caisse/sync/push',
        body: {
          'device_id': sending.deviceId,
          'events': [
            {
              'event_uuid': sending.eventUuid,
              'shop_id': sending.shopId,
              'entity_type': sending.entityType,
              'entity_id': sending.entityId,
              'action': sending.action,
              'payload': payload,
              'occurred_at': sending.occurredAt.toUtc().toIso8601String(),
            },
          ],
        },
      );
      return await _applyResponse(
        organizationId: organizationId,
        outboxId: outboxId,
        response: response,
      );
    } on ApiException catch (exception) {
      if (exception.statusCode == null) {
        return _required(
          _outbox.markQueuedAfterNetworkFailure(
            organizationId: organizationId,
            id: outboxId,
            error: exception.message,
          ),
        );
      }
      return _required(
        _outbox.markFailed(
          organizationId: organizationId,
          id: outboxId,
          error: 'HTTP ${exception.statusCode} : ${exception.message}',
          failureKind: OutboxFailureKind.http,
          httpStatusCode: exception.statusCode,
        ),
      );
    }
  }

  Future<OutboxEvent> retryFailed({
    required int organizationId,
    required int outboxId,
  }) {
    final active = _activeRetry;
    if (active != null) return active;
    late final Future<OutboxEvent> run;
    run = _retryFailed(organizationId: organizationId, outboxId: outboxId)
        .whenComplete(() {
          if (identical(_activeRetry, run)) _activeRetry = null;
        });
    _activeRetry = run;
    return run;
  }

  Future<OutboxEvent> _retryFailed({
    required int organizationId,
    required int outboxId,
  }) async {
    if (_api.organizationId != organizationId) {
      throw const SyncOutboxException(
        'Le contexte d’organisation de synchronisation est incohérent.',
      );
    }
    final event = await _outbox.findById(
      organizationId: organizationId,
      id: outboxId,
    );
    if (event == null || !_retryPolicy.evaluate(event).allowed) {
      throw const SyncOutboxException(
        'Cet échec ne peut pas être réessayé en sécurité.',
      );
    }
    final queued = await _outbox.requeueRetryableTechnicalFailure(
      organizationId: organizationId,
      id: outboxId,
    );
    if (queued == null) {
      throw const SyncOutboxException(
        'Cet événement n’est plus éligible au réessai.',
      );
    }
    final result = await syncOne(
      organizationId: organizationId,
      outboxId: queued.id,
    );
    if (result.status == OutboxStatus.applied && _refresh != null) {
      await _refresh(organizationId: organizationId, shopId: result.shopId);
    }
    return result;
  }

  Future<OutboxEvent> _applyResponse({
    required int organizationId,
    required int outboxId,
    required Map<String, dynamic> response,
  }) async {
    final categories = <_SyncCategory>[];
    for (final category in _SyncCategory.values) {
      final raw = response[category.key];
      if (raw is List && raw.isNotEmpty) {
        categories.add(category);
      }
    }
    if (categories.length != 1) {
      return _required(
        _outbox.markFailed(
          organizationId: organizationId,
          id: outboxId,
          error: 'Réponse de synchronisation serveur invalide.',
          failureKind: OutboxFailureKind.protocolInvalid,
          serverResultJson: jsonEncode(response),
        ),
      );
    }

    final rawItem = response[categories.single.key] as List;
    if (rawItem.length != 1 || rawItem.single is! Map) {
      return _required(
        _outbox.markFailed(
          organizationId: organizationId,
          id: outboxId,
          error: 'Réponse de synchronisation serveur invalide.',
          failureKind: OutboxFailureKind.protocolInvalid,
          serverResultJson: jsonEncode(response),
        ),
      );
    }
    final item = (rawItem.single as Map).cast<String, dynamic>();
    final resultJson = jsonEncode(item);

    switch (categories.single) {
      case _SyncCategory.accepted:
        if (item['status'] != 'applied') {
          return _required(
            _outbox.markFailed(
              organizationId: organizationId,
              id: outboxId,
              error: 'Réponse de synchronisation serveur invalide.',
              failureKind: OutboxFailureKind.protocolInvalid,
              serverResultJson: resultJson,
            ),
          );
        }
        return _required(
          _outbox.markApplied(
            organizationId: organizationId,
            id: outboxId,
            serverResultJson: resultJson,
          ),
        );
      case _SyncCategory.conflicts:
        return _required(
          _outbox.markConflict(
            organizationId: organizationId,
            id: outboxId,
            error: _message(item, 'Conflit de synchronisation.'),

            serverResultJson: resultJson,
          ),
        );
      case _SyncCategory.rejected:
        return _required(
          _outbox.markRejected(
            organizationId: organizationId,
            id: outboxId,
            error: _message(item, 'Événement rejeté par le serveur.'),
            serverResultJson: resultJson,
          ),
        );
      case _SyncCategory.failed:
        return _required(
          _outbox.markFailed(
            organizationId: organizationId,
            id: outboxId,
            error: _message(item, 'Échec serveur de synchronisation.'),
            failureKind: OutboxFailureKind.serverProcessing,
            serverResultJson: resultJson,
          ),
        );
    }
  }

  Map<String, dynamic>? _decodePayload(String payloadJson) {
    try {
      final value = jsonDecode(payloadJson);
      return value is Map ? value.cast<String, dynamic>() : null;
    } on FormatException {
      return null;
    }
  }

  String _message(Map<String, dynamic> item, String fallback) {
    final message = item['message'] ?? item['error'];
    return message is String && message.isNotEmpty ? message : fallback;
  }

  Future<OutboxEvent> _required(Future<OutboxEvent?> transition) async =>
      (await transition) ??
      (throw const SyncOutboxException(
        'La transition locale de synchronisation a échoué.',
      ));
}

enum _SyncCategory {
  accepted('accepted'),
  conflicts('conflicts'),
  rejected('rejected'),
  failed('failed');

  const _SyncCategory(this.key);
  final String key;
}
