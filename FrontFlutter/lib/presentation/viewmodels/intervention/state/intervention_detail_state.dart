import '../../../../domain/entity/intervention_entity.dart';

sealed class InterventionDetailState {
  const InterventionDetailState();
}

class InterventionDetailLoading extends InterventionDetailState {
  const InterventionDetailLoading();
}

class InterventionDetailLoaded extends InterventionDetailState {
  final InterventionEntity intervention;

  /// Vrai pendant un changement de statut ou l'ajout d'un commentaire.
  final bool isSubmitting;
  const InterventionDetailLoaded(this.intervention, {this.isSubmitting = false});

  InterventionDetailLoaded copyWith({
    InterventionEntity? intervention,
    bool? isSubmitting,
  }) =>
      InterventionDetailLoaded(
        intervention ?? this.intervention,
        isSubmitting: isSubmitting ?? this.isSubmitting,
      );
}

class InterventionDetailError extends InterventionDetailState {
  final String message;
  const InterventionDetailError(this.message);
}
