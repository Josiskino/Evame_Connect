import 'package:flutter/material.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_fonts.dart';

/// Thèmes typographiques — titres en Bricolage Grotesque, corps en Inter
/// (polices embarquées, mêmes que l'application de référence).
class TTextTheme {
  TTextTheme._();

  static TextTheme _build(Color text, Color sub) => TextTheme(
        titleLarge: TextStyle(
            fontFamily: AppFonts.bricolage, fontSize: 20, fontWeight: FontWeight.w700, color: text),
        titleMedium: TextStyle(
            fontFamily: AppFonts.bricolage, fontSize: 16, fontWeight: FontWeight.w600, color: text),
        titleSmall: TextStyle(
            fontFamily: AppFonts.bricolage, fontSize: 14, fontWeight: FontWeight.w600, color: text),
        bodyLarge: TextStyle(fontFamily: AppFonts.inter, fontSize: 15, color: text),
        bodyMedium: TextStyle(fontFamily: AppFonts.inter, fontSize: 14, color: text),
        bodySmall: TextStyle(fontFamily: AppFonts.inter, fontSize: 12, color: sub),
        labelLarge: TextStyle(fontFamily: AppFonts.inter, fontSize: 14, fontWeight: FontWeight.w600),
      );

  static TextTheme get lightTextTheme =>
      _build(AppColors.textLight, AppColors.subTextLight);

  static TextTheme get darkTextTheme =>
      _build(AppColors.textDark, AppColors.subTextDark);
}
