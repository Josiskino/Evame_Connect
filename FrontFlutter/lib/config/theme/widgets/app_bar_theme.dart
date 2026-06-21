import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../core/constants/app_colors.dart';

class TAppBarTheme {
  TAppBarTheme._();

  static AppBarTheme get light => AppBarTheme(
        elevation: 0,
        centerTitle: false,
        backgroundColor: AppColors.surfaceLight,
        foregroundColor: AppColors.textLight,
        surfaceTintColor: Colors.transparent,
        titleTextStyle: GoogleFonts.bricolageGrotesque(
            fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textLight),
      );

  static AppBarTheme get dark => AppBarTheme(
        elevation: 0,
        centerTitle: false,
        backgroundColor: AppColors.surfaceDark,
        foregroundColor: AppColors.textDark,
        surfaceTintColor: Colors.transparent,
        titleTextStyle: GoogleFonts.bricolageGrotesque(
            fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textDark),
      );
}
