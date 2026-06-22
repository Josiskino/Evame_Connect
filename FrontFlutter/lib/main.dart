import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_native_splash/flutter_native_splash.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'config/router/app_router.dart';
import 'config/theme/theme.dart';
import 'core/services/local_notification_service.dart';
import 'core/services/push_notification_service.dart';
import 'core/utils/app_logger.dart';

Future<void> main() async {
  final binding = WidgetsFlutterBinding.ensureInitialized();
  FlutterNativeSplash.preserve(widgetsBinding: binding);

  // Notifications locales (affichage des missions / push premier plan).
  await LocalNotificationService.instance.init();

  // Firebase + notifications push (échec silencieux si non configuré pour la plateforme).
  try {
    await Firebase.initializeApp();
    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
    await PushNotificationService.instance.init();
  } catch (e) {
    AppLogger.e('Initialisation Firebase échouée', error: e);
  }

  runApp(const ProviderScope(child: EvameApp()));
}

class EvameApp extends ConsumerWidget {
  const EvameApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(goRouterProvider);
    final themeMode = ref.watch(themeProvider);

    // Retire l'écran de lancement natif une fois la première frame rendue.
    WidgetsBinding.instance.addPostFrameCallback((_) => FlutterNativeSplash.remove());

    return MaterialApp.router(
      title: 'EVAME',
      debugShowCheckedModeBanner: false,
      theme: TAppTheme.light,
      darkTheme: TAppTheme.dark,
      themeMode: themeMode,
      routerConfig: router,
    );
  }
}
