import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

import '../utils/app_logger.dart';

/// Handler des messages reçus en arrière-plan / app terminée.
/// Doit être une fonction top-level annotée `@pragma('vm:entry-point')`
/// (exécutée dans un isolate séparé par l'OS).
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  AppLogger.i('Push (arrière-plan) : ${message.messageId} — ${message.notification?.title}');
}

/// Service de notifications push (FCM) : permission, jeton, écoute des messages.
class PushNotificationService {
  PushNotificationService._();
  static final PushNotificationService instance = PushNotificationService._();

  final FirebaseMessaging _fm = FirebaseMessaging.instance;

  Future<void> init() async {
    // Demande d'autorisation (iOS + Android 13+).
    final settings = await _fm.requestPermission(alert: true, badge: true, sound: true);
    AppLogger.i('Push — autorisation : ${settings.authorizationStatus}');

    // Jeton de l'appareil (à transmettre au backend pour cibler l'utilisateur).
    final token = await _fm.getToken();
    AppLogger.i('Push — token FCM : $token');
    _fm.onTokenRefresh.listen((t) => AppLogger.i('Push — token rafraîchi : $t'));

    // Message reçu app au premier plan.
    FirebaseMessaging.onMessage.listen((m) {
      AppLogger.i('Push (premier plan) : ${m.notification?.title} — ${m.notification?.body}');
    });

    // Tap sur une notification (app en arrière-plan).
    FirebaseMessaging.onMessageOpenedApp.listen((m) {
      AppLogger.i('Push ouverte : ${m.data}');
    });

    // App ouverte depuis une notification (démarrage à froid).
    final initial = await _fm.getInitialMessage();
    if (initial != null) {
      AppLogger.i('Push initiale : ${initial.data}');
    }
  }

  /// Jeton FCM courant (pour l'enregistrer côté serveur).
  Future<String?> getToken() => _fm.getToken();
}
