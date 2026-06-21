import 'package:flutter/material.dart';

/// Jetons de design (espacements, rayons, tailles) — grille de 4px.
class DesignTokens {
  DesignTokens._();

  // Espacements
  static const double spaceXs = 4.0;
  static const double spaceS = 8.0;
  static const double spaceM = 12.0;
  static const double spaceL = 16.0;
  static const double spaceXl = 20.0;
  static const double spaceXxl = 24.0;
  static const double spaceXxxl = 32.0;

  // Rayons
  static const double radiusS = 8.0;
  static const double radiusM = 12.0;
  static const double radiusL = 16.0;
  static const double radiusPill = 999.0;

  static BorderRadius get borderRadiusS => BorderRadius.circular(radiusS);
  static BorderRadius get borderRadiusM => BorderRadius.circular(radiusM);
  static BorderRadius get borderRadiusL => BorderRadius.circular(radiusL);

  // Tailles d'icônes
  static const double iconS = 18.0;
  static const double iconM = 24.0;
  static const double iconL = 32.0;

  // Hauteurs de composants
  static const double buttonHeight = 50.0;
  static const double fieldHeight = 50.0;

  // Marges de page
  static const EdgeInsets pagePadding = EdgeInsets.all(spaceL);
}
