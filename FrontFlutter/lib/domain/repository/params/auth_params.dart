/// Identifiants de connexion.
class LoginParams {
  final String email;
  final String password;
  const LoginParams({required this.email, required this.password});

  Map<String, dynamic> toJson() => {'email': email, 'password': password};
}
