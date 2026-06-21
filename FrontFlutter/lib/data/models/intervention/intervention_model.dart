import '../../../domain/entity/intervention_entity.dart';
import '../auth/user_model.dart';
import 'client_model.dart';
import 'commentaire_model.dart';
import 'moto_model.dart';

class InterventionModel {
  final int id;
  final String probleme;
  final String statut;
  final String statutLabel;
  final String? dateIntervention;
  final ClientModel? client;
  final MotoModel? moto;
  final UserModel? technicien;
  final List<CommentaireModel> commentaires;

  const InterventionModel({
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

  factory InterventionModel.fromJson(Map<String, dynamic> json) {
    return InterventionModel(
      id: json['id'] as int,
      probleme: json['probleme'] ?? '',
      statut: json['statut'] ?? '',
      statutLabel: json['statut_label'] ?? '',
      dateIntervention: json['date_intervention'],
      client: json['client'] != null
          ? ClientModel.fromJson(json['client'] as Map<String, dynamic>)
          : null,
      moto: json['moto'] != null
          ? MotoModel.fromJson(json['moto'] as Map<String, dynamic>)
          : null,
      technicien: json['technicien'] != null
          ? UserModel.fromJson(json['technicien'] as Map<String, dynamic>)
          : null,
      commentaires: ((json['commentaires'] as List?) ?? const [])
          .map((e) => CommentaireModel.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  InterventionEntity toEntity() => InterventionEntity(
        id: id,
        probleme: probleme,
        statut: statut,
        statutLabel: statutLabel,
        dateIntervention: dateIntervention,
        client: client?.toEntity(),
        moto: moto?.toEntity(),
        technicien: technicien?.toEntity(),
        commentaires: commentaires.map((c) => c.toEntity()).toList(),
      );
}
