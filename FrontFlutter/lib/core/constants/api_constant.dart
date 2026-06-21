/// Configuration de l'API EVAME.
class ApiConstant {
  ApiConstant._();

  /// URL de base de l'API (V1).
  static const String baseUrl = 'https://api-evame.josuelassey.pro/api/v1';

  static const Duration connectTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);
}
