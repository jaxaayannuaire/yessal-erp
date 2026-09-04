import '../api/api_client.dart';
import '../models/caisse_models.dart';
import '../storage/token_storage.dart';

class AuthRepository {
  AuthRepository(this._api, this._tokens);
  final ApiClient _api;
  final TokenStorage _tokens;

  Future<UserProfile> login(String email, String password) async {
    final json = await _api.post(
      '/auth/login',
      body: {'email': email, 'password': password},
    );
    await _tokens.saveToken(json['token'] as String);
    return UserProfile.fromJson(json['user'] as Map<String, dynamic>);
  }

  Future<UserProfile?> restore() async {
    if (await _tokens.readToken() == null) return null;
    return UserProfile.fromJson(
      (await _api.get('/auth/me'))['user'] as Map<String, dynamic>,
    );
  }

  Future<List<Organization>> organizations() async {
    final json = await _api.get('/organizations');
    return (json['organizations'] as List)
        .cast<Map<String, dynamic>>()
        .map(Organization.fromJson)
        .toList();
  }

  Future<void> logout() async {
    try {
      await _api.post('/auth/logout');
    } finally {
      await _tokens.deleteToken();
    }
  }
}
