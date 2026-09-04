import '../database/local_repositories.dart';

class SyncMetadataStore {
  SyncMetadataStore(this._local);
  final BootstrapLocalRepository _local;

  Future<void> saveCursor(int organizationId, int cursor) =>
      _local.updateCursor(organizationId, cursor);

  Future<int?> readCursor(int organizationId) =>
      _local.readCursor(organizationId);
}
