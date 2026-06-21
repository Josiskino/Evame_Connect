import 'package:flutter/material.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/design_tokens.dart';

class TElevatedButtonTheme {
  TElevatedButtonTheme._();

  static final ElevatedButtonThemeData theme = ElevatedButtonThemeData(
    style: ElevatedButton.styleFrom(
      elevation: 0,
      backgroundColor: AppColors.primary,
      foregroundColor: Colors.white,
      disabledBackgroundColor: AppColors.primary.withValues(alpha: 0.5),
      disabledForegroundColor: Colors.white70,
      minimumSize: const Size(double.infinity, DesignTokens.buttonHeight),
      textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
      shape: RoundedRectangleBorder(borderRadius: DesignTokens.borderRadiusM),
    ),
  );
}
