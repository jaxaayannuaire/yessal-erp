class ApiException implements Exception {
  const ApiException(this.statusCode, this.message, {this.validationErrors});

  final int? statusCode;
  final String message;
  final Map<String, List<String>>? validationErrors;

  bool get isUnauthorized => statusCode == 401;
  bool get isForbidden => statusCode == 403;
  bool get isValidationError => statusCode == 422;

  @override
  String toString() => message;
}
