import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../../core/abstract/usecase.dart';
import '../../../core/providers/auth/auth_provider.dart';
import '../../../core/providers/request/api_provider.dart';
import '../../../core/services/push_notification_service.dart';
import '../../../core/services/realtime_service.dart';
import '../../../domain/entity/user_entity.dart';
import '../../../domain/repository/params/auth_params.dart';
import '../intervention/interventions_viewmodel.dart';
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
      RealtimeService.instance.disconnect();
      state = const Unauthenticated();
    };

    _restoreSession();
    return const AuthInitial();
  }

  /// Active le temps réel pour le technicien connecté (popup mission assignée).
  void _onAuthenticated(UserEntity user) {
    state = Authenticated(user);
    RealtimeService.instance.onAssigned =
        () => ref.invalidate(interventionsViewModelProvider);
    RealtimeService.instance.connect(user.id);
  }

  /// Restaure la session si un jeton valide est présent.
  Future<void> _restoreSession() async {
    final res = await ref.read(authRepositoryProvider).currentUser();
    res.fold(
      (_) => state = const Unauthenticated(),
      (user) => user == null ? state = const Unauthenticated() : _onAuthenticated(user),
    );
  }

  Future<void> login(LoginUiParams params) async {
    state = const AuthLoading();

    // Jeton FCM de l'appareil -> enregistré côté serveur (multi-appareils).
    String? fcmToken;
    try {
      fcmToken = await PushNotificationService.instance.getToken();
    } catch (_) {
      fcmToken = null;
    }

    final res = await ref.read(loginUseCaseProvider).call(
          LoginParams(email: params.email, password: params.password, fcmToken: fcmToken),
        );
    res.fold(
      (failure) => state = AuthError(failure.message),
      (user) => _onAuthenticated(user),
    );
  }

  Future<void> logout() async {
    await RealtimeService.instance.disconnect();
    await ref.read(logoutUseCaseProvider).call(const NoParams());
    state = const Unauthenticated();
  }
}
