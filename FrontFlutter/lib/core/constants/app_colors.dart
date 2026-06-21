import 'package:flutter/material.dart';

/// Palette de l'application EVAME (alignée sur le front web : rouge #E53935).
class AppColors {
  AppColors._();

  // Marque
  static const Color primary = Color(0xFFE53935);
  static const Color primaryDark = Color(0xFFC62828);
  static const Color secondary = Color(0xFF455A64);

  // Thème clair
  static const Color backgroundLight = Color(0xFFF8F7FA);
  static const Color surfaceLight = Colors.white;
  static const Color textLight = Color(0xFF2E2E3A);
  static const Color subTextLight = Color(0xFF6E6B7B);
  static const Color borderLight = Color(0xFFE6E6EF);

  // Thème sombre
  static const Color backgroundDark = Color(0xFF25293C);
  static const Color surfaceDark = Color(0xFF2F3349);
  static const Color textDark = Color(0xFFE1E1E6);
  static const Color subTextDark = Color(0xFFA8AAB7);
  static const Color borderDark = Color(0xFF44485E);

  // États / statuts d'intervention
  static const Color statutNouvelle = Color(0xFF2196F3); // info
  static const Color statutEnTraitement = Color(0xFFFB8C00); // warning
  static const Color statutTerminee = Color(0xFF43A047); // success

  static const Color success = Color(0xFF43A047);
  static const Color warning = Color(0xFFFB8C00);
  static const Color error = Color(0xFFE53935);
  static const Color info = Color(0xFF2196F3);

  /// Couleur associée à un statut d'intervention.
  static Color forStatut(String statut) => switch (statut) {
        'nouvelle' => statutNouvelle,
        'en_traitement' => statutEnTraitement,
        'terminee' => statutTerminee,
        _ => secondary,
      };
}
