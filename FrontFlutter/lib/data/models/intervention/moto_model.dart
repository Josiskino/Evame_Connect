import '../../../domain/entity/moto_entity.dart';

class MotoModel {
  final int id;
  final String modele;
  final String? classeCc;
  final String? couleur;
  final String? imageUrl;

  const MotoModel({
    required this.id,
    required this.modele,
    this.classeCc,
    this.couleur,
    this.imageUrl,
  });

  factory MotoModel.fromJson(Map<String, dynamic> json) => MotoModel(
        id: json['id'] as int,
        modele: json['modele'] ?? '',
        classeCc: json['classe_cc'],
        couleur: json['couleur'],
        imageUrl: json['image_url'],
      );

  MotoEntity toEntity() => MotoEntity(
        id: id,
        modele: modele,
        classeCc: classeCc,
        couleur: couleur,
        imageUrl: imageUrl,
      );
}
