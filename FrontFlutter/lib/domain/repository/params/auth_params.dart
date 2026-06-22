/// Identifiants de connexion.
class LoginParams {
  final String email;
  final String password;

  /// Jeton FCM de l'appareil (enregistré au login pour les push, multi-appareils).
  final String? fcmToken;

  const LoginParams({required this.email, required this.password, this.fcmToken});

  Map<String, dynamic> toJson() => {
        'email': email,
        'password': password,
        if (fcmToken != null && fcmToken!.isNotEmpty) 'fcm_token': fcmToken,
      };
}
