class ApiConfig {
  static const String baseUrl = 'http://10.0.2.2:8000/api/v1';

  static const String login = '$baseUrl/auth/login';
  static const String register = '$baseUrl/auth/register';
  static const String me = '$baseUrl/auth/me';
  static const String logout = '$baseUrl/auth/logout';
}