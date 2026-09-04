class AppConfig {
  const AppConfig._();

  static const apiBaseUrl = String.fromEnvironment(
    'YESSAL_API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );

  static const connectTimeout = Duration(seconds: 15);
  static const receiveTimeout = Duration(seconds: 20);
}
