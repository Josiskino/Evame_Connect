import 'package:flutter/material.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/design_tokens.dart';

class TOutlinedButtonTheme {
  TOutlinedButtonTheme._();

  static final OutlinedButtonThemeData theme = OutlinedButtonThemeData(
    style: OutlinedButton.styleFrom(
      foregroundColor: AppColors.primary,
      side: const BorderSide(color: AppColors.primary),
      minimumSize: const Size(double.infinity, DesignTokens.buttonHeight),
      textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
      shape: RoundedRectangleBorder(borderRadius: DesignTokens.borderRadiusM),
    ),
  );
}
