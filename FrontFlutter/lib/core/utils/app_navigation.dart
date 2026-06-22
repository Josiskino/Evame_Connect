import 'package:flutter/widgets.dart';

/// Clé du navigateur racine — permet d'afficher des popups depuis des services
/// (hors arbre de widgets), ex. réception d'une mission en temps réel.
final GlobalKey<NavigatorState> rootNavigatorKey = GlobalKey<NavigatorState>();
