import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/constants/routes_name.dart';
import '../../presentation/pages/auth/login_screen.dart';
import '../../presentation/pages/interventions/intervention_detail_screen.dart';
import '../../presentation/pages/interventions/new_intervention_screen.dart';
import '../../presentation/pages/main_page/main_page.dart';
import '../../presentation/pages/splash/splash_screen.dart';
import '../../presentation/viewmodels/auth/auth_viewmodel.dart';
import '../../presentation/viewmodels/auth/state/auth_state.dart';

/// Routeur de l'application, piloté par l'état d'authentification.
final goRouterProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: AppRoute.splash,
    refreshListenable: _AuthListenable(ref),
    redirect: (context, state) {
      final auth = ref.read(authViewModelProvider);
      final loc = state.matchedLocation;
      final onLogin = loc == AppRoute.login;
      final onSplash = loc == AppRoute.splash;

      // Vérification de session en cours -> rester sur le splash.
      if (auth is AuthInitial) return onSplash ? null : AppRoute.splash;

      // Non connecté -> écran de connexion.
      if (auth is Unauthenticated || auth is AuthError) {
        return onLogin ? null : AppRoute.login;
      }

      // Connecté -> on quitte splash/login vers la page principale.
      if (auth is Authenticated && (onLogin || onSplash)) {
        return AppRoute.main;
      }

      return null;
    },
    routes: [
      GoRoute(path: AppRoute.splash, builder: (_, _) => const SplashScreen()),
      GoRoute(path: AppRoute.login, builder: (_, _) => const LoginScreen()),
      GoRoute(path: AppRoute.main, builder: (_, _) => const MainPage()),
      GoRoute(
        path: AppRoute.newIntervention,
        builder: (_, _) => const NewInterventionScreen(),
      ),
      GoRoute(
        path: AppRoute.interventionDetail,
        builder: (_, state) => InterventionDetailScreen(
          id: int.parse(state.pathParameters['id']!),
        ),
      ),
    ],
  );
});

/// Pont Riverpod -> Listenable pour rafraîchir GoRouter sur changement d'auth.
class _AuthListenable extends ChangeNotifier {
  _AuthListenable(Ref ref) {
    ref.listen(authViewModelProvider, (_, _) => notifyListeners());
  }
}
