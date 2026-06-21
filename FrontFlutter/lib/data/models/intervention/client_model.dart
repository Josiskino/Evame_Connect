import '../../../domain/entity/client_entity.dart';

class ClientModel {
  final int id;
  final String nom;
  final String? telephone;
  final String? email;
  final String? adresse;

  const ClientModel({
    required this.id,
    required this.nom,
    this.telephone,
    this.email,
    this.adresse,
  });

  factory ClientModel.fromJson(Map<String, dynamic> json) => ClientModel(
        id: json['id'] as int,
        nom: json['nom'] ?? '',
        telephone: json['telephone'],
        email: json['email'],
        adresse: json['adresse'],
      );

  ClientEntity toEntity() => ClientEntity(
        id: id,
        nom: nom,
        telephone: telephone,
        email: email,
        adresse: adresse,
      );
}
