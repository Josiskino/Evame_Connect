/// Filtres de la liste d'interventions.
class ListInterventionsParams {
  /// Date au format `YYYY-MM-DD` (ex. interventions du jour).
  final String? date;
  final String? statut;
  const ListInterventionsParams({this.date, this.statut});

  Map<String, dynamic> toQuery() => {
        if (date != null) 'date': date,
        if (statut != null) 'statut': statut,
      };
}

/// Changement de statut d'une intervention.
class UpdateStatutParams {
  final int id;
  final String statut;
  const UpdateStatutParams({required this.id, required this.statut});

  Map<String, dynamic> toJson() => {'statut': statut};
}

/// Ajout d'un commentaire à une intervention.
class AddCommentaireParams {
  final int id;
  final String contenu;
  const AddCommentaireParams({required this.id, required this.contenu});

  Map<String, dynamic> toJson() => {'contenu': contenu};
}
