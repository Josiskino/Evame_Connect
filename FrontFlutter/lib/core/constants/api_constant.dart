/// Configuration de l'API EVAME.
class ApiConstant {
  ApiConstant._();

  /// URL de base de l'API (V1).
  static const String baseUrl = 'https://api-evame.josuelassey.pro/api/v1';

  static const Duration connectTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);

  /// Endpoint d'autorisation des canaux privés (hors préfixe /v1).
  static const String broadcastingAuthUrl =
      'https://api-evame.josuelassey.pro/api/broadcasting/auth';

  // Pusher Channels (clé publique — sans danger côté client).
  static const String pusherKey = '2ba9582ab06408a7784d';
  static const String pusherCluster = 'mt1';
}
