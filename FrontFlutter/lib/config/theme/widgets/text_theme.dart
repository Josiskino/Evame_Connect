import 'package:flutter/material.dart';

import '../../../core/constants/app_colors.dart';

/// Thèmes typographiques clair / sombre.
class TTextTheme {
  TTextTheme._();

  static TextTheme lightTextTheme = const TextTheme(
    titleLarge: TextStyle(fontSize: 20, fontWeight: FontWeight.w700, color: AppColors.textLight),
    titleMedium: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: AppColors.textLight),
    bodyLarge: TextStyle(fontSize: 15, color: AppColors.textLight),
    bodyMedium: TextStyle(fontSize: 14, color: AppColors.textLight),
    bodySmall: TextStyle(fontSize: 12, color: AppColors.subTextLight),
    labelLarge: TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
  );

  static TextTheme darkTextTheme = const TextTheme(
    titleLarge: TextStyle(fontSize: 20, fontWeight: FontWeight.w700, color: AppColors.textDark),
    titleMedium: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: AppColors.textDark),
    bodyLarge: TextStyle(fontSize: 15, color: AppColors.textDark),
    bodyMedium: TextStyle(fontSize: 14, color: AppColors.textDark),
    bodySmall: TextStyle(fontSize: 12, color: AppColors.subTextDark),
    labelLarge: TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
  );
}
