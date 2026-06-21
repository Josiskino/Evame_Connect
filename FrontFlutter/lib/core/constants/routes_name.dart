/// Noms / chemins des routes de l'application (GoRouter).
class AppRoute {
  AppRoute._();

  static const String splash = '/';
  static const String login = '/login';
  static const String main = '/main';
  static const String interventions = '/interventions';
  static const String interventionDetail = '/interventions/:id';
  static const String notifications = '/notifications';

  /// Construit le chemin de détail d'une intervention.
  static String interventionDetailPath(int id) => '/interventions/$id';
}
