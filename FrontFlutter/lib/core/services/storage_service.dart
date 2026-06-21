import 'package:shared_preferences/shared_preferences.dart';

/// Persistance locale (jeton Sanctum + préférence de thème).
class StorageService {
  StorageService._();
  static final StorageService instance = StorageService._();

  static const String _kToken = 'auth_token';
  static const String _kThemeMode = 'theme_mode';

  Future<SharedPreferences> get _prefs => SharedPreferences.getInstance();

  // --- Jeton d'authentification ---
  Future<void> saveToken(String token) async =>
      (await _prefs).setString(_kToken, token);

  Future<String?> getToken() async => (await _prefs).getString(_kToken);

  Future<void> clearToken() async => (await _prefs).remove(_kToken);

  Future<bool> hasToken() async {
    final t = await getToken();
    return t != null && t.isNotEmpty;
  }

  // --- Thème ---
  Future<void> saveThemeMode(String mode) async =>
      (await _prefs).setString(_kThemeMode, mode);

  Future<String?> getThemeMode() async => (await _prefs).getString(_kThemeMode);
}
