// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'auth_viewmodel.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, type=warning
/// Gère l'authentification : vérification du jeton au démarrage, connexion,
/// déconnexion. La source de vérité de l'état de session de l'app.

@ProviderFor(AuthViewModel)
final authViewModelProvider = AuthViewModelProvider._();

/// Gère l'authentification : vérification du jeton au démarrage, connexion,
/// déconnexion. La source de vérité de l'état de session de l'app.
final class AuthViewModelProvider
    extends $NotifierProvider<AuthViewModel, AuthState> {
  /// Gère l'authentification : vérification du jeton au démarrage, connexion,
  /// déconnexion. La source de vérité de l'état de session de l'app.
  AuthViewModelProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'authViewModelProvider',
        isAutoDispose: true,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$authViewModelHash();

  @$internal
  @override
  AuthViewModel create() => AuthViewModel();

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(AuthState value) {
    return $ProviderOverride(
      origin: this,
      providerOverride: $SyncValueProvider<AuthState>(value),
    );
  }
}

String _$authViewModelHash() => r'506f3dd35f4b3f48c4ff549d83717518727e3f63';

/// Gère l'authentification : vérification du jeton au démarrage, connexion,
/// déconnexion. La source de vérité de l'état de session de l'app.

abstract class _$AuthViewModel extends $Notifier<AuthState> {
  AuthState build();
  @$mustCallSuper
  @override
  WhenComplete runBuild() {
    final ref = this.ref as $Ref<AuthState, AuthState>;
    final element =
        ref.element
            as $ClassProviderElement<
              AnyNotifier<AuthState, AuthState>,
              AuthState,
              Object?,
              Object?
            >;
    return element.handleCreate(ref, build);
  }
}
