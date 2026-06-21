import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../../core/providers/intervention/intervention_provider.dart';
import '../../../domain/repository/params/intervention_params.dart';
import 'state/intervention_detail_state.dart';

part 'intervention_detail_viewmodel.g.dart';

/// Détail d'une intervention + actions (changer le statut, ajouter un commentaire).
@riverpod
class InterventionDetailViewModel extends _$InterventionDetailViewModel {
  @override
  InterventionDetailState build(int id) {
    _load();
    return const InterventionDetailLoading();
  }

  Future<void> _load() async {
    state = const InterventionDetailLoading();
    final res = await ref.read(getInterventionUseCaseProvider).call(id);
    res.fold(
      (failure) => state = InterventionDetailError(failure.message),
      (intervention) => state = InterventionDetailLoaded(intervention),
    );
  }

  Future<void> refresh() => _load();

  /// Change le statut. Renvoie `null` si succès, sinon le message d'erreur.
  Future<String?> changeStatut(String statut) async {
    final current = state;
    if (current is! InterventionDetailLoaded) return 'Intervention non chargée.';

    state = current.copyWith(isSubmitting: true);
    final res = await ref.read(updateInterventionStatusUseCaseProvider).call(
          UpdateStatutParams(id: id, statut: statut),
        );

    return res.fold(
      (failure) {
        state = current.copyWith(isSubmitting: false);
        return failure.message;
      },
      (intervention) {
        state = InterventionDetailLoaded(intervention);
        return null;
      },
    );
  }

  /// Ajoute un commentaire. Renvoie `null` si succès, sinon le message d'erreur.
  Future<String?> addCommentaire(String contenu) async {
    final current = state;
    if (current is! InterventionDetailLoaded) return 'Intervention non chargée.';

    state = current.copyWith(isSubmitting: true);
    final res = await ref.read(addCommentaireUseCaseProvider).call(
          AddCommentaireParams(id: id, contenu: contenu),
        );

    return res.fold(
      (failure) {
        state = current.copyWith(isSubmitting: false);
        return failure.message;
      },
      (intervention) {
        state = InterventionDetailLoaded(intervention);
        return null;
      },
    );
  }
}
