import '../../../../domain/entity/user_entity.dart';

/// État d'authentification (consommé par le routeur et les écrans).
sealed class AuthState {
  const AuthState();
}

/// État initial (vérification du jeton en cours).
class AuthInitial extends AuthState {
  const AuthInitial();
}

class AuthLoading extends AuthState {
  const AuthLoading();
}

class Authenticated extends AuthState {
  final UserEntity user;
  const Authenticated(this.user);
}

class Unauthenticated extends AuthState {
  const Unauthenticated();
}

class AuthError extends AuthState {
  final String message;
  const AuthError(this.message);
}
