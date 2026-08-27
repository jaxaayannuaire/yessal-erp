import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

import '../../models/user.dart';
import '../config/api_config.dart';

class AuthService {
  static const _storage = FlutterSecureStorage();
  static const _tokenKey = 'auth_token';

  Future<User> login({
    required String email,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse(ApiConfig.login),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'email': email,
        'password': password,
      }),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode != 200) {
      throw Exception(
        data['message'] ?? 'Erreur de connexion',
      );
    }

    await _storage.write(
      key: _tokenKey,
      value: data['token'],
    );

    return User.fromJson(data['user']);
  }

  Future<User> register({
    required String name,
    required String email,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse(ApiConfig.register),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': password,
      }),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode != 201) {
      throw Exception(
        data['message'] ?? 'Erreur lors de l’inscription',
      );
    }

    if (data['token'] != null) {
      await _storage.write(
        key: _tokenKey,
        value: data['token'],
      );
    }

    return User.fromJson(data['user']);
  }

  Future<User?> me() async {
    final token = await getToken();

    if (token == null) {
      return null;
    }

    final response = await http.get(
      Uri.parse(ApiConfig.me),
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode != 200) {
      return null;
    }

    final data = jsonDecode(response.body);

    return User.fromJson(data['user']);
  }

  Future<void> logout() async {
    final token = await getToken();

    if (token != null) {
      await http.post(
        Uri.parse(ApiConfig.logout),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );
    }

    await _storage.delete(key: _tokenKey);
  }

  Future<String?> getToken() async {
    return _storage.read(key: _tokenKey);
  }

  Future<bool> isAuthenticated() async {
    return (await getToken()) != null;
  }
}