import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';

import '../../core/constants/app_colors.dart';
import '../../core/constants/design_tokens.dart';

/// Notifications utilisateur (succès / erreur / info).
class AppSnackbar {
  AppSnackbar._();

  static void success(BuildContext context, String message) =>
      _show(context, message, AppColors.success, Iconsax.tick_circle);

  static void error(BuildContext context, String message) =>
      _show(context, message, AppColors.error, Iconsax.warning_2);

  static void info(BuildContext context, String message) =>
      _show(context, message, AppColors.info, Iconsax.info_circle);

  static void _show(BuildContext context, String message, Color color, IconData icon) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          behavior: SnackBarBehavior.floating,
          backgroundColor: color,
          margin: const EdgeInsets.all(DesignTokens.spaceL),
          shape: RoundedRectangleBorder(borderRadius: DesignTokens.borderRadiusM),
          content: Row(
            children: [
              Icon(icon, color: Colors.white, size: DesignTokens.iconS),
              const SizedBox(width: DesignTokens.spaceS),
              Expanded(
                child: Text(
                  message,
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w500),
                ),
              ),
            ],
          ),
        ),
      );
  }
}
