import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../../core/abstract/usecase.dart';
import '../../../core/providers/auth/auth_provider.dart';
import '../../../core/providers/request/api_provider.dart';
import '../../../domain/repository/params/auth_params.dart';
import 'params/login_ui_params.dart';
import 'state/auth_state.dart';

part 'auth_viewmodel.g.dart';

/// Gère l'authentification : vérification du jeton au démarrage, connexion,
/// déconnexion. La source de vérité de l'état de session de l'app.
@riverpod
class AuthViewModel extends _$AuthViewModel {
  @override
  AuthState build() {
    // Déconnexion automatique si le serveur renvoie 401.
    ref.read(apiClientProvider).onAuthFailure = () {
      state = const Unauthenticated();
    };

    _restoreSession();
    return const AuthInitial();
  }

  /// Restaure la session si un jeton valide est présent.
  Future<void> _restoreSession() async {
    final res = await ref.read(authRepositoryProvider).currentUser();
    res.fold(
      (_) => state = const Unauthenticated(),
      (user) => state = user == null ? const Unauthenticated() : Authenticated(user),
    );
  }

  Future<void> login(LoginUiParams params) async {
    state = const AuthLoading();
    final res = await ref.read(loginUseCaseProvider).call(
          LoginParams(email: params.email, password: params.password),
        );
    res.fold(
      (failure) => state = AuthError(failure.message),
      (user) => state = Authenticated(user),
    );
  }

  Future<void> logout() async {
    await ref.read(logoutUseCaseProvider).call(const NoParams());
    state = const Unauthenticated();
  }
}
