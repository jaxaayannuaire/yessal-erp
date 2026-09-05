import 'package:flutter_test/flutter_test.dart';
import 'package:yessal_caisse/core/sync/outbox_repository.dart';
import 'package:yessal_caisse/core/sync/outbox_retry_policy.dart';

void main() {
  const policy = OutboxRetryPolicy();

  for (final code in [408, 429, 500, 503, 599]) {
    test('allows HTTP $code', () {
      expect(policy.evaluate(_event(code: code)).allowed, isTrue);
    });
  }

  for (final code in [400, 401, 403, 404, 409, 422, 499, 600]) {
    test('denies HTTP $code', () {
      expect(policy.evaluate(_event(code: code)).allowed, isFalse);
    });
  }

  test('denies business, local, protocol and unknown failures', () {
    for (final kind in [
      OutboxFailureKind.businessConflict,
      OutboxFailureKind.businessRejected,
      OutboxFailureKind.serverProcessing,
      OutboxFailureKind.localPayloadInvalid,
      OutboxFailureKind.protocolInvalid,
      null,
    ]) {
      expect(policy.evaluate(_event(kind: kind)).allowed, isFalse);
    }
    expect(
      policy.evaluate(_event(status: OutboxStatus.conflict, code: 500)).allowed,
      isFalse,
    );
    expect(
      policy.evaluate(_event(status: OutboxStatus.rejected, code: 500)).allowed,
      isFalse,
    );
    expect(
      policy
          .evaluate(
            _event(
              status: OutboxStatus.queued,
              kind: OutboxFailureKind.network,
            ),
          )
          .allowed,
      isFalse,
    );
    expect(
      policy.evaluate(_event(kind: OutboxFailureKind.http, code: null)).allowed,
      isFalse,
    );
    expect(
      policy
          .evaluate(
            _event(status: OutboxStatus.applied, kind: OutboxFailureKind.http),
          )
          .allowed,
      isFalse,
    );
  });
}

OutboxEvent _event({
  OutboxStatus status = OutboxStatus.failed,
  OutboxFailureKind? kind = OutboxFailureKind.http,
  int? code = 500,
}) => OutboxEvent(
  id: 1,
  organizationId: 1,
  shopId: 1,
  deviceId: 1,
  eventUuid: 'event',
  entityType: 'sale',
  entityId: 'entity',
  action: 'create',
  payloadJson: '{}',
  occurredAt: DateTime.utc(2026),
  status: status,
  attemptCount: 1,
  lastAttemptAt: null,
  lastError: 'error',
  serverResultJson: null,
  failureKind: kind,
  httpStatusCode: code,
  createdAt: DateTime.utc(2026),
  updatedAt: DateTime.utc(2026),
);
