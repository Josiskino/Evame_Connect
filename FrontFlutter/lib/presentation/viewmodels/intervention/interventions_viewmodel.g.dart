// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'interventions_viewmodel.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, type=warning
/// Liste des interventions du technicien (filtrable par statut et par date).

@ProviderFor(InterventionsViewModel)
final interventionsViewModelProvider = InterventionsViewModelProvider._();

/// Liste des interventions du technicien (filtrable par statut et par date).
final class InterventionsViewModelProvider
    extends $NotifierProvider<InterventionsViewModel, InterventionsState> {
  /// Liste des interventions du technicien (filtrable par statut et par date).
  InterventionsViewModelProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'interventionsViewModelProvider',
        isAutoDispose: true,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$interventionsViewModelHash();

  @$internal
  @override
  InterventionsViewModel create() => InterventionsViewModel();

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(InterventionsState value) {
    return $ProviderOverride(
      origin: this,
      providerOverride: $SyncValueProvider<InterventionsState>(value),
    );
  }
}

String _$interventionsViewModelHash() =>
    r'e1960e68149765d724a22fd1c9abf1acc67b31da';

/// Liste des interventions du technicien (filtrable par statut et par date).

abstract class _$InterventionsViewModel extends $Notifier<InterventionsState> {
  InterventionsState build();
  @$mustCallSuper
  @override
  WhenComplete runBuild() {
    final ref = this.ref as $Ref<InterventionsState, InterventionsState>;
    final element =
        ref.element
            as $ClassProviderElement<
              AnyNotifier<InterventionsState, InterventionsState>,
              InterventionsState,
              Object?,
              Object?
            >;
    return element.handleCreate(ref, build);
  }
}
