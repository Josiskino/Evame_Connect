import 'package:flutter/material.dart';
import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../core/constants/app_colors.dart';
import '../../core/services/storage_service.dart';
import 'widgets/app_bar_theme.dart';
import 'widgets/elevated_button_theme.dart';
import 'widgets/icon_theme.dart';
import 'widgets/outlined_button_theme.dart';
import 'widgets/text_field_theme.dart';
import 'widgets/text_theme.dart';

part 'theme.g.dart';

/// Gère le mode de thème (clair / sombre / système), persisté localement.
@riverpod
class ThemeNotifier extends _$ThemeNotifier {
  @override
  ThemeMode build() {
    _load();
    return ThemeMode.system;
  }

  Future<void> _load() async {
    final saved = await StorageService.instance.getThemeMode();
    state = switch (saved) {
      'dark' => ThemeMode.dark,
      'light' => ThemeMode.light,
      _ => ThemeMode.system,
    };
  }

  void setThemeMode(ThemeMode mode) {
    state = mode;
    StorageService.instance.saveThemeMode(mode.name);
  }

  void toggle() {
    setThemeMode(state == ThemeMode.dark ? ThemeMode.light : ThemeMode.dark);
  }
}

/// Définition des thèmes clair / sombre de l'application EVAME.
class TAppTheme {
  TAppTheme._();

  static ThemeData get light => ThemeData(
        useMaterial3: true,
        brightness: Brightness.light,
        primaryColor: AppColors.primary,
        scaffoldBackgroundColor: AppColors.backgroundLight,
        colorScheme: const ColorScheme.light(
          primary: AppColors.primary,
          onPrimary: Colors.white,
          secondary: AppColors.secondary,
          surface: AppColors.surfaceLight,
          onSurface: AppColors.textLight,
          error: AppColors.error,
        ),
        appBarTheme: TAppBarTheme.light,
        textTheme: TTextTheme.lightTextTheme,
        iconTheme: TIconTheme.light,
        elevatedButtonTheme: TElevatedButtonTheme.theme,
        outlinedButtonTheme: TOutlinedButtonTheme.theme,
        inputDecorationTheme: TTextFieldTheme.light,
        dividerColor: AppColors.borderLight,
      );

  static ThemeData get dark => ThemeData(
        useMaterial3: true,
        brightness: Brightness.dark,
        primaryColor: AppColors.primary,
        scaffoldBackgroundColor: AppColors.backgroundDark,
        colorScheme: const ColorScheme.dark(
          primary: AppColors.primary,
          onPrimary: Colors.white,
          secondary: AppColors.secondary,
          surface: AppColors.surfaceDark,
          onSurface: AppColors.textDark,
          error: AppColors.error,
        ),
        appBarTheme: TAppBarTheme.dark,
        textTheme: TTextTheme.darkTextTheme,
        iconTheme: TIconTheme.dark,
        elevatedButtonTheme: TElevatedButtonTheme.theme,
        outlinedButtonTheme: TOutlinedButtonTheme.theme,
        inputDecorationTheme: TTextFieldTheme.dark,
        dividerColor: AppColors.borderDark,
      );
}
