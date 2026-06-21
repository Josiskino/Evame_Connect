import '../../../domain/entity/commentaire_entity.dart';
import '../auth/user_model.dart';

class CommentaireModel {
  final int id;
  final String contenu;
  final String? date;
  final UserModel? auteur;

  const CommentaireModel({
    required this.id,
    required this.contenu,
    this.date,
    this.auteur,
  });

  factory CommentaireModel.fromJson(Map<String, dynamic> json) => CommentaireModel(
        id: json['id'] as int,
        contenu: json['contenu'] ?? '',
        date: json['date'],
        auteur: json['auteur'] != null
            ? UserModel.fromJson(json['auteur'] as Map<String, dynamic>)
            : null,
      );

  CommentaireEntity toEntity() => CommentaireEntity(
        id: id,
        contenu: contenu,
        date: date,
        auteur: auteur?.toEntity(),
      );
}
