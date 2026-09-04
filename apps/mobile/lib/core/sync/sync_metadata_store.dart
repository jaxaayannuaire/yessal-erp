import '../storage/local_cache_store.dart';

class SyncMetadataStore {
  SyncMetadataStore(this._cache);
  final LocalCacheStore _cache;

  Future<void> saveCursor(int userId, int organizationId, int cursor) =>
      _cache.writeJson(userId, organizationId, 'sync_metadata', {
        'last_cursor': cursor,
        'last_sync_at': DateTime.now().toIso8601String(),
      });
  Future<Map<String, dynamic>?> read(int userId, int organizationId) async =>
      (await _cache.readJson(userId, organizationId, 'sync_metadata'))
          as Map<String, dynamic>?;
}
