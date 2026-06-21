import 'user_entity.dart';

/// Commentaire posté sur une intervention.
class CommentaireEntity {
  final int id;
  final String contenu;
  final String? date;
  final UserEntity? auteur;

  const CommentaireEntity({
    required this.id,
    required this.contenu,
    this.date,
    this.auteur,
  });
}
