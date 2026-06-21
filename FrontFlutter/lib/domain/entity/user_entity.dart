/// Règle d'autorisation (CASL) renvoyée par l'API.
class AbilityEntity {
  final String action;
  final String subject;
  const AbilityEntity({required this.action, required this.subject});
}

/// Utilisateur authentifié (technicien SAV, etc.).
class UserEntity {
  final int id;
  final String name;
  final String email;
  final String? telephone;
  final List<String> roles;
  final List<String> permissions;
  final List<String> views;
  final List<AbilityEntity> abilities;

  const UserEntity({
    required this.id,
    required this.name,
    required this.email,
    this.telephone,
    this.roles = const [],
    this.permissions = const [],
    this.views = const [],
    this.abilities = const [],
  });

  bool get isSav => roles.contains('sav');
  bool get isSuperAdmin => roles.contains('super-admin');

  bool can(String action, String subject) => abilities.any((a) =>
      (a.action == 'manage' && a.subject == 'all') ||
      (a.action == action && a.subject == subject));
}
