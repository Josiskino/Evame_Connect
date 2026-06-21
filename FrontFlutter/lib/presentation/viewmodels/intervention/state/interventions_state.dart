import '../../../../domain/entity/intervention_entity.dart';

sealed class InterventionsState {
  const InterventionsState();
}

class InterventionsLoading extends InterventionsState {
  const InterventionsLoading();
}

class InterventionsLoaded extends InterventionsState {
  final List<InterventionEntity> items;

  /// Filtre de statut actif (null = tous).
  final String? statutFilter;
  const InterventionsLoaded(this.items, {this.statutFilter});
}

class InterventionsError extends InterventionsState {
  final String message;
  const InterventionsError(this.message);
}
