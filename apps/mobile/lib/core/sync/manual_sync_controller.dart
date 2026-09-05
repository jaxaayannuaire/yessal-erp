import 'package:flutter/foundation.dart';

import 'outbox_repository.dart';
import 'sync_outbox_service.dart';

enum ManualSyncState { idle, syncing, success, error }

class ManualSyncController extends ChangeNotifier {
  ManualSyncController(this._service, this._outbox);

  final SyncOutboxService _service;
  final OutboxRepository _outbox;

  ManualSyncState state = ManualSyncState.idle;
  String? error;
  int queued = 0;
  int conflict = 0;
  int rejected = 0;
  int failed = 0;
  int? _organizationId;

  bool get isSyncing => state == ManualSyncState.syncing;

  Future<void> load(int organizationId) async {
    _organizationId = organizationId;
    await _reload(organizationId);
  }

  Future<void> sync(int organizationId) async {
    if (isSyncing) return;
    if (_organizationId != organizationId) await load(organizationId);
    state = ManualSyncState.syncing;
    error = null;
    notifyListeners();
    try {
      final results = await _service.syncPending(organizationId);
      if (_organizationId == organizationId) {
        final networkFailure = results.where(
          (event) => event.status == OutboxStatus.queued,
        );
        if (networkFailure.isNotEmpty) {
          state = ManualSyncState.error;
          error = networkFailure.first.lastError;
        } else {
          state = ManualSyncState.success;
        }
      }
    } catch (exception) {
      if (_organizationId == organizationId) {
        state = ManualSyncState.error;
        error = exception.toString();
      }
    } finally {
      if (_organizationId == organizationId) {
        await _reload(organizationId);
      }
    }
  }

  Future<void> _reload(int organizationId) async {
    final events = await _outbox.listByOrganization(organizationId);
    if (_organizationId != organizationId) return;
    queued = events
        .where((event) => event.status == OutboxStatus.queued)
        .length;
    conflict = events
        .where((event) => event.status == OutboxStatus.conflict)
        .length;
    rejected = events
        .where((event) => event.status == OutboxStatus.rejected)
        .length;
    failed = events
        .where((event) => event.status == OutboxStatus.failed)
        .length;
    notifyListeners();
  }
}
