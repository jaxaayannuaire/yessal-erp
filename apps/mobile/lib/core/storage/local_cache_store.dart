import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Stockage chiffré, isolé par utilisateur et organisation.
/// La couche est volontairement interchangeable avec une base Drift future.
class LocalCacheStore {
  LocalCacheStore({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  String _key(int userId, int organizationId, String resource) =>
      'yessal.cache.$userId.$organizationId.$resource';

  String _sessionKey(String resource) => 'yessal.session.$resource';

  Future<void> writeJson(
    int userId,
    int organizationId,
    String resource,
    Object value,
  ) {
    return _storage.write(
      key: _key(userId, organizationId, resource),
      value: jsonEncode(value),
    );
  }

  Future<dynamic> readJson(
    int userId,
    int organizationId,
    String resource,
  ) async {
    final value = await _storage.read(
      key: _key(userId, organizationId, resource),
    );
    return value == null ? null : jsonDecode(value);
  }

  Future<void> writeSessionJson(String resource, Object value) {
    return _storage.write(key: _sessionKey(resource), value: jsonEncode(value));
  }

  Future<dynamic> readSessionJson(String resource) async {
    final value = await _storage.read(key: _sessionKey(resource));
    return value == null ? null : jsonDecode(value);
  }

  Future<void> deleteScope(int userId, int organizationId) async {
    final all = await _storage.readAll();
    final prefix = 'yessal.cache.$userId.$organizationId.';
    await Future.wait(
      all.keys
          .where((key) => key.startsWith(prefix))
          .map((key) => _storage.delete(key: key)),
    );
  }
}
