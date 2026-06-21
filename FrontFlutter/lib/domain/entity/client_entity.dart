/// Client lié à une intervention.
class ClientEntity {
  final int id;
  final String nom;
  final String? telephone;
  final String? email;
  final String? adresse;

  const ClientEntity({
    required this.id,
    required this.nom,
    this.telephone,
    this.email,
    this.adresse,
  });
}
