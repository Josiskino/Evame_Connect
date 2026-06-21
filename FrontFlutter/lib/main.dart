import 'package:flutter/material.dart';
import 'package:flutter_native_splash/flutter_native_splash.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'config/router/app_router.dart';
import 'config/theme/theme.dart';

void main() {
  final binding = WidgetsFlutterBinding.ensureInitialized();
  FlutterNativeSplash.preserve(widgetsBinding: binding);
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
