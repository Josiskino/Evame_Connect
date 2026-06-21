import 'package:intl/intl.dart';
import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../../core/providers/intervention/intervention_provider.dart';
import '../../../domain/repository/params/intervention_params.dart';
import 'state/interventions_state.dart';

part 'interventions_viewmodel.g.dart';

/// Liste des interventions du technicien (filtrable par statut et par date).
@riverpod
class InterventionsViewModel extends _$InterventionsViewModel {
  String? _statut;
  String? _date;

  @override
  InterventionsState build() {
    _load();
    return const InterventionsLoading();
  }

  Future<void> _load() async {
    state = const InterventionsLoading();
    final res = await ref.read(listInterventionsUseCaseProvider).call(
          ListInterventionsParams(date: _date, statut: _statut),
        );
    res.fold(
      (failure) => state = InterventionsError(failure.message),
      (items) => state = InterventionsLoaded(items, statutFilter: _statut),
    );
  }

  /// Recharge la liste avec les filtres courants.
  Future<void> refresh() => _load();

  /// Filtre par statut (null = tous).
  Future<void> filterByStatut(String? statut) async {
    _statut = statut;
    await _load();
  }

  /// Restreint aux interventions d'une date (`YYYY-MM-DD`), ou null pour toutes.
  Future<void> filterByDate(String? date) async {
    _date = date;
    await _load();
  }

  /// Raccourci : interventions du jour.
  Future<void> showToday() =>
      filterByDate(DateFormat('yyyy-MM-dd').format(DateTime.now()));
}
