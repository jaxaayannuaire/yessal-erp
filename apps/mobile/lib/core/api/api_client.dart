import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/app_config.dart';
import '../errors/api_exception.dart';
import '../storage/token_storage.dart';

class ApiClient {
  ApiClient({http.Client? client, TokenStorage? tokenStorage})
    : _client = client ?? http.Client(),
      _tokenStorage = tokenStorage ?? TokenStorage();

  final http.Client _client;
  final TokenStorage _tokenStorage;
  int? organizationId;
  void Function()? onUnauthorized;

  Future<Map<String, dynamic>> get(String path, {Map<String, String>? query}) =>
      _request('GET', path, query: query);

  Future<Map<String, dynamic>> post(String path, {Object? body}) =>
      _request('POST', path, body: body);

  Future<Map<String, dynamic>> _request(
    String method,
    String path, {
    Object? body,
    Map<String, String>? query,
  }) async {
    final token = await _tokenStorage.readToken();
    final uri = Uri.parse('${AppConfig.apiBaseUrl}$path')
        .replace(queryParameters: query);
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
      if (organizationId != null) 'X-Organization-Id': '$organizationId',
    };
    try {
      final request = http.Request(method, uri)..headers.addAll(headers);
      if (body != null) request.body = jsonEncode(body);
      final streamed = await _client
          .send(request)
          .timeout(AppConfig.connectTimeout);
      final response = await http.Response.fromStream(streamed)
          .timeout(AppConfig.receiveTimeout);
      final decoded = response.body.isEmpty
          ? <String, dynamic>{}
          : jsonDecode(response.body) as Map<String, dynamic>;
      if (response.statusCode < 200 || response.statusCode >= 300) {
        if (response.statusCode == 401) {
          await _tokenStorage.deleteToken();
          onUnauthorized?.call();
        }
        throw ApiException(
          response.statusCode,
          decoded['message'] as String? ?? 'La requête a échoué.',
          validationErrors: _errors(decoded['errors']),
        );
      }
      return decoded;
    } on TimeoutException {
      throw const ApiException(null, 'La connexion au serveur a expiré.');
    } on ApiException {
      rethrow;
    } catch (_) {
      throw const ApiException(null, 'Impossible de joindre le serveur.');
    }
  }

  Map<String, List<String>>? _errors(dynamic raw) {
    if (raw is! Map<String, dynamic>) return null;
    return raw.map(
      (key, value) => MapEntry(key, (value as List).cast<String>()),
    );
  }
}
