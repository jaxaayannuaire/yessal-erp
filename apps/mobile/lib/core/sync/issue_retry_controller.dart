import 'package:flutter/foundation.dart';

import 'outbox_repository.dart';
import 'sync_outbox_service.dart';

enum IssueRetryState { idle, retrying, success, error }

class IssueRetryController extends ChangeNotifier {
  IssueRetryController(this._service);

  final SyncOutboxService _service;
  IssueRetryState state = IssueRetryState.idle;
  String? error;

  Future<OutboxEvent?> retry({
    required int organizationId,
    required int outboxId,
  }) async {
    if (state == IssueRetryState.retrying) return null;
    state = IssueRetryState.retrying;
    error = null;
    notifyListeners();
    try {
      final event = await _service.retryFailed(
        organizationId: organizationId,
        outboxId: outboxId,
      );
      if (event.status == OutboxStatus.applied) {
        state = IssueRetryState.success;
      } else {
        state = IssueRetryState.error;
        error = event.lastError ?? 'La reprise nâ€™a pas abouti.';
      }
      notifyListeners();
      return event;
    } catch (exception) {
      state = IssueRetryState.error;
      error = exception.toString();
      notifyListeners();
      return null;
    }
  }
}
