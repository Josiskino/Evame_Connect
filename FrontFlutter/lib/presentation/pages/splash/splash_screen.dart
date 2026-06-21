import 'package:flutter/material.dart';

import '../../../core/constants/app_images.dart';
import '../../widgets/app_loader.dart';

/// Écran d'attente affiché pendant la vérification de session.
/// (Placeholder — peut être personnalisé.)
class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Image.asset(AppImages.logo, height: 88),
            const SizedBox(height: 32),
            const AppLoader(),
          ],
        ),
      ),
    );
  }
}
