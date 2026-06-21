/// Chemins des endpoints de l'API (relatifs à [ApiConstant.baseUrl]).
class ApiEndpoints {
  ApiEndpoints._();

  // Auth
  static const String login = '/login';
  static const String logout = '/logout';
  static const String me = '/me';

  // Interventions (SAV)
  static const String interventions = '/interventions';
  static String interventionById(int id) => '/interventions/$id';
  static String interventionCommentaires(int id) => '/interventions/$id/commentaires';
}
