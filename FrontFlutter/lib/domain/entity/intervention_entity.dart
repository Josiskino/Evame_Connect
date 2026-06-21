import 'client_entity.dart';
import 'commentaire_entity.dart';
import 'moto_entity.dart';
import 'user_entity.dart';

/// Statuts possibles d'une intervention SAV.
class InterventionStatut {
  InterventionStatut._();

  static const String nouvelle = 'nouvelle';
  static const String enTraitement = 'en_traitement';
  static const String terminee = 'terminee';

  static const List<String> all = [nouvelle, enTraitement, terminee];

  static String label(String statut) => switch (statut) {
        nouvelle => 'Nouvelle',
        enTraitement => 'En traitement',
        terminee => 'Terminée',
        _ => statut,
      };
}

/// Intervention SAV (cœur du module mobile).
class InterventionEntity {
  final int id;
  final String probleme;
  final String statut;
  final String statutLabel;
  final String? dateIntervention;
  final ClientEntity? client;
  final MotoEntity? moto;
  final UserEntity? technicien;
  final List<CommentaireEntity> commentaires;

  const InterventionEntity({
    required this.id,
    required this.probleme,
    required this.statut,
    required this.statutLabel,
    this.dateIntervention,
    this.client,
    this.moto,
    this.technicien,
    this.commentaires = const [],
  });
}
