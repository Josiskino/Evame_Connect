import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

import '../constants/api_constant.dart';
import '../constants/routes_name.dart';
import '../utils/app_logger.dart';
import '../utils/app_navigation.dart';
import 'local_notification_service.dart';
import 'storage_service.dart';

/// Temps réel (Pusher) : écoute le canal privé du technicien et affiche un
/// popup + une notification locale dès qu'une intervention lui est assignée.
class RealtimeService {
  RealtimeService._();
  static final RealtimeService instance = RealtimeService._();

  final PusherChannelsFlutter _pusher = PusherChannelsFlutter.getInstance();
  final Dio _dio = Dio();

  String? _channel;
  bool _started = false;

  /// Callback déclenché à la réception d'une mission (ex. rafraîchir la liste).
  void Function()? onAssigned;

  Future<void> connect(int userId) async {
    if (_started) return;
    try {
      await _pusher.init(
        apiKey: ApiConstant.pusherKey,
        cluster: ApiConstant.pusherCluster,
        onAuthorizer: _authorize,
        onEvent: _onEvent,
      );
      _channel = 'private-user.$userId';
      await _pusher.subscribe(channelName: _channel!);
      await _pusher.connect();
      _started = true;
      AppLogger.i('Realtime connecté : $_channel');
    } catch (e) {
      AppLogger.e('Realtime: échec connexion', error: e);
    }
  }

  Future<void> disconnect() async {
    if (!_started) return;
    try {
      if (_channel != null) await _pusher.unsubscribe(channelName: _channel!);
      await _pusher.disconnect();
    } catch (_) {}
    _started = false;
    _channel = null;
  }

  /// Autorisation du canal privé via l'endpoint Laravel broadcasting/auth.
  Future<dynamic> _authorize(String channelName, String socketId, dynamic options) async {
    final token = await StorageService.instance.getToken();
    final res = await _dio.post(
      ApiConstant.broadcastingAuthUrl,
      data: {'socket_id': socketId, 'channel_name': channelName},
      options: Options(
        contentType: Headers.formUrlEncodedContentType,
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      ),
    );

    return res.data; // { auth: "key:signature" }
  }

  void _onEvent(PusherEvent event) {
    if (event.eventName != 'intervention.assigned') return;
    AppLogger.i('Realtime: mission reçue ${event.data}');

    Map<String, dynamic> data = {};
    try {
      data = event.data is String ? jsonDecode(event.data) as Map<String, dynamic> : Map<String, dynamic>.from(event.data);
    } catch (_) {}

    final message = (data['message'] as String?) ?? 'Une nouvelle intervention vous a été assignée.';

    LocalNotificationService.instance.show('Nouvelle mission', message);
    onAssigned?.call();
    _showPopup(message, data['id'] is int ? data['id'] as int : int.tryParse('${data['id']}'));
  }

  void _showPopup(String message, int? interventionId) {
    final ctx = rootNavigatorKey.currentContext;
    if (ctx == null) return;

    showDialog<void>(
      context: ctx,
      builder: (dialogCtx) => AlertDialog(
        icon: const Icon(Icons.notifications_active, color: Color(0xFFE53935), size: 36),
        title: const Text('Nouvelle mission'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogCtx).pop(),
            child: const Text('Fermer'),
          ),
          if (interventionId != null)
            FilledButton(
              onPressed: () {
                Navigator.of(dialogCtx).pop();
                ctx.push(AppRoute.interventionDetailPath(interventionId));
              },
              child: const Text('Voir'),
            ),
        ],
      ),
    );
  }
}
