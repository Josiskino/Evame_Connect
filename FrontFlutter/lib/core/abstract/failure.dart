/// Échecs métier (couche domaine). Convertis depuis les erreurs réseau/API.
sealed class Failure {
  final String message;
  final int? statusCode;

  /// Erreurs de validation par champ (clé = champ, valeur = liste de messages).
  final Map<String, dynamic>? errors;

  const Failure(this.message, {this.statusCode, this.errors});
}

/// Erreur serveur (5xx / réponse inattendue).
class ServerFailure extends Failure {
  const ServerFailure(super.message, {super.statusCode, super.errors});
}

/// Problème de connexion réseau (pas d'accès, timeout).
class NetworkFailure extends Failure {
  const NetworkFailure(super.message, {super.statusCode});
}

/// Authentification requise / invalide (401).
class AuthFailure extends Failure {
  const AuthFailure(super.message, {super.statusCode});
}

/// Données invalides (422) — voir [errors] pour le détail par champ.
class ValidationFailure extends Failure {
  const ValidationFailure(super.message, {super.statusCode, super.errors});
}

/// Échec non catégorisé.
class UnknownFailure extends Failure {
  const UnknownFailure(super.message, {super.statusCode});
}
