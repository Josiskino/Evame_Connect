import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../core/constants/app_colors.dart';

/// Thèmes typographiques — titres en Bricolage Grotesque, corps en Inter
/// (mêmes polices que l'application de référence).
class TTextTheme {
  TTextTheme._();

  static TextTheme _build(Color text, Color sub) => TextTheme(
        titleLarge: GoogleFonts.bricolageGrotesque(
            fontSize: 20, fontWeight: FontWeight.w700, color: text),
        titleMedium: GoogleFonts.bricolageGrotesque(
            fontSize: 16, fontWeight: FontWeight.w600, color: text),
        titleSmall: GoogleFonts.bricolageGrotesque(
            fontSize: 14, fontWeight: FontWeight.w600, color: text),
        bodyLarge: GoogleFonts.inter(fontSize: 15, color: text),
        bodyMedium: GoogleFonts.inter(fontSize: 14, color: text),
        bodySmall: GoogleFonts.inter(fontSize: 12, color: sub),
        labelLarge: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.w600),
      );

  static TextTheme get lightTextTheme =>
      _build(AppColors.textLight, AppColors.subTextLight);

  static TextTheme get darkTextTheme =>
      _build(AppColors.textDark, AppColors.subTextDark);
}
