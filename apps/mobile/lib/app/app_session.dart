import 'dart:math';

import 'package:flutter/foundation.dart';

import '../core/api/api_client.dart';
import '../core/auth/auth_repository.dart';
import '../core/bootstrap/bootstrap_repository.dart';
import '../core/errors/api_exception.dart';
import '../core/models/caisse_models.dart';
import '../core/storage/local_cache_store.dart';

class AppSession extends ChangeNotifier {
  AppSession(this._api, this._auth, this._bootstrap, this._cache);
  final ApiClient _api;
  final AuthRepository _auth;
  final BootstrapRepository _bootstrap;
  final LocalCacheStore _cache;

  UserProfile? user;
  List<Organization> organizations = [];
  Organization? organization;
  CaisseEntity? shop;
  CaisseEntity? terminal;
  CaisseEntity? device;
  BootstrapData? bootstrapData;
  bool isLoading = true;
  bool isOffline = false;
  String? error;

  void expireSession() {
    user = null;
    organizations = [];
    organization = null;
    shop = null;
    terminal = null;
    device = null;
    bootstrapData = null;
    _api.organizationId = null;
    notifyListeners();
  }

  Future<void> restore() async {
    try {
      user = await _auth.restore();
      await _cache.writeSessionJson('user', user!.toJson());
      organizations = await _auth.organizations();
    } on ApiException catch (exception) {
      if (!exception.isUnauthorized) await _restoreOfflineContext();
    } catch (_) {
      await _restoreOfflineContext();
    }
    if (user != null && organization == null && organizations.length == 1) {
      await selectOrganization(organizations.single);
    }
    isLoading = false;
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    user = await _auth.login(email, password);
    await _cache.writeSessionJson('user', user!.toJson());
    organizations = await _auth.organizations();
    if (organizations.length == 1) {
      await selectOrganization(organizations.single);
    }
    notifyListeners();
  }

  Future<void> selectOrganization(Organization value) async {
    organization = value;
    _api.organizationId = value.id;
    await _bootstrap.cacheOrganization(value);
    await _persistContext();
    final shops = await _bootstrap.shops();
    if (shops.length == 1) {
      await selectShop(shops.single);
    }
    notifyListeners();
  }

  Future<List<CaisseEntity>> availableShops() => _bootstrap.shops();

  Future<List<CaisseEntity>> availableTerminals() async {
    final terminals = await _bootstrap.terminals();
    return terminals.where((item) => item.raw['shop_id'] == shop?.id).toList();
  }

  Future<void> selectShop(CaisseEntity value) async {
    shop = value;
    await _persistContext();
    final terminals = await _bootstrap.terminals();
    final matching = terminals
        .where((item) => item.raw['shop_id'] == value.id)
        .toList();
    if (matching.length == 1) {
      await selectTerminal(matching.single);
    }
    notifyListeners();
  }

  Future<void> selectTerminal(CaisseEntity value) async {
    terminal = value;
    await _registerDevice();
    await _persistContext();
    await bootstrap();
    notifyListeners();
  }

  Future<void> _registerDevice() async {
    final devices = await _bootstrap.devices();
    final uuid = await _deviceUuid();
    final existing = devices
        .where((item) => item.raw['device_uuid'] == uuid)
        .toList();
    if (existing.isNotEmpty) {
      device = existing.first;
      await _persistContext();
      return;
    }
    final json = await _api.post(
      '/caisse/devices',
      body: {
        'shop_id': shop!.id,
        'terminal_id': terminal!.id,
        'device_uuid': uuid,
        'name': 'Yessal Caisse Mobile',
        'platform': defaultTargetPlatform.name,
      },
    );
    device = CaisseEntity.fromJson(json['device'] as Map<String, dynamic>);
    await _persistContext();
  }

  Future<String> _deviceUuid() async {
    final existing = await _cache.readDeviceUuid(user!.id, organization!.id);
    if (existing != null) return existing;
    final random = Random.secure();
    final bytes = List<int>.generate(16, (_) => random.nextInt(256));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    final hex = bytes
        .map((byte) => byte.toRadixString(16).padLeft(2, '0'))
        .join();
    final uuid =
        '${hex.substring(0, 8)}-${hex.substring(8, 12)}-${hex.substring(12, 16)}-${hex.substring(16, 20)}-${hex.substring(20)}';
    await _cache.writeDeviceUuid(user!.id, organization!.id, uuid);
    return uuid;
  }

  Future<void> bootstrap() async {
    try {
      bootstrapData = await _bootstrap.bootstrap(organization!.id, shop!.id);
      isOffline = false;
      error = null;
      await _persistContext();
    } catch (_) {
      bootstrapData = shop == null
          ? null
          : await _bootstrap.cached(organization!.id, shop!.id);
      isOffline = bootstrapData != null;
      error = bootstrapData == null
          ? 'Le bootstrap nécessite une connexion initiale.'
          : null;
    }
    notifyListeners();
  }

  Future<void> logout() async {
    await _auth.logout();
    expireSession();
  }

  Future<void> _restoreOfflineContext() async {
    final cachedUser = await _cache.readSessionJson('user');
    final context = await _cache.readSessionJson('context');
    if (cachedUser is! Map<String, dynamic> ||
        context is! Map<String, dynamic>) {
      return;
    }
    final cachedOrganization = context['organization'];
    if (cachedOrganization is! Map<String, dynamic>) return;

    user = UserProfile.fromJson(cachedUser);
    organization = Organization.fromJson(cachedOrganization);
    organizations = [organization!];
    _api.organizationId = organization!.id;
    shop = _entity(context['shop']);
    terminal = _entity(context['terminal']);
    device = _entity(context['device']);
    bootstrapData = shop == null
        ? null
        : await _bootstrap.cached(organization!.id, shop!.id);
    isOffline = bootstrapData != null;
  }

  CaisseEntity? _entity(Object? value) {
    return value is Map<String, dynamic> ? CaisseEntity.fromJson(value) : null;
  }

  Future<void> _persistContext() async {
    if (organization == null) return;
    await _cache.writeSessionJson('context', {
      'organization': organization!.toJson(),
      'shop': shop?.raw,
      'terminal': terminal?.raw,
      'device': device?.raw,
    });
  }
}
