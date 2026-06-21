import 'package:flutter/material.dart';

import '../../core/constants/app_colors.dart';

/// Indicateur de chargement centré.
class AppLoader extends StatelessWidget {
  final double size;
  const AppLoader({super.key, this.size = 32});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: SizedBox(
        width: size,
        height: size,
        child: const CircularProgressIndicator(
          strokeWidth: 3,
          valueColor: AlwaysStoppedAnimation(AppColors.primary),
        ),
      ),
    );
  }
}
