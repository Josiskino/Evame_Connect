import 'package:flutter/material.dart';

import '../../../core/constants/app_colors.dart';

class TAppBarTheme {
  TAppBarTheme._();

  static const AppBarTheme light = AppBarTheme(
    elevation: 0,
    centerTitle: false,
    backgroundColor: AppColors.surfaceLight,
    foregroundColor: AppColors.textLight,
    surfaceTintColor: Colors.transparent,
    titleTextStyle: TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textLight),
  );

  static const AppBarTheme dark = AppBarTheme(
    elevation: 0,
    centerTitle: false,
    backgroundColor: AppColors.surfaceDark,
    foregroundColor: AppColors.textDark,
    surfaceTintColor: Colors.transparent,
    titleTextStyle: TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textDark),
  );
}
