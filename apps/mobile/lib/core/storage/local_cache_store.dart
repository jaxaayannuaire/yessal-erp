import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Conserve uniquement le contexte sensible et de faible volume.
/// Les référentiels métier sont persistés dans Drift.
class LocalCacheStore {
  LocalCacheStore({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  static const _prefix = 'yessal.session.';
  static const _deviceUuidPrefix = 'yessal.device_uuid.';
  final FlutterSecureStorage _storage;

  Future<void> writeSessionJson(String resource, Object value) {
    return _storage.write(key: '$_prefix$resource', value: jsonEncode(value));
  }

  Future<dynamic> readSessionJson(String resource) async {
    final value = await _storage.read(key: '$_prefix$resource');
    return value == null ? null : jsonDecode(value);
  }

  Future<void> writeDeviceUuid(int userId, int organizationId, String uuid) {
    return _storage.write(
      key: '$_deviceUuidPrefix$userId.$organizationId',
      value: uuid,
    );
  }

  Future<String?> readDeviceUuid(int userId, int organizationId) {
    return _storage.read(key: '$_deviceUuidPrefix$userId.$organizationId');
  }
}
