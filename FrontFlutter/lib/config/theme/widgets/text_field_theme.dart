import 'package:flutter/material.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/design_tokens.dart';

class TTextFieldTheme {
  TTextFieldTheme._();

  static InputDecorationTheme _base({
    required Color fill,
    required Color border,
    required Color hint,
  }) =>
      InputDecorationTheme(
        filled: true,
        fillColor: fill,
        hintStyle: TextStyle(color: hint, fontSize: 14),
        labelStyle: TextStyle(color: hint, fontSize: 14),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: DesignTokens.spaceL,
          vertical: DesignTokens.spaceM,
        ),
        border: OutlineInputBorder(
          borderRadius: DesignTokens.borderRadiusM,
          borderSide: BorderSide(color: border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: DesignTokens.borderRadiusM,
          borderSide: BorderSide(color: border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: DesignTokens.borderRadiusM,
          borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: DesignTokens.borderRadiusM,
          borderSide: const BorderSide(color: AppColors.error),
        ),
      );

  static InputDecorationTheme get light => _base(
        fill: AppColors.surfaceLight,
        border: AppColors.borderLight,
        hint: AppColors.subTextLight,
      );

  static InputDecorationTheme get dark => _base(
        fill: AppColors.surfaceDark,
        border: AppColors.borderDark,
        hint: AppColors.subTextDark,
      );
}
