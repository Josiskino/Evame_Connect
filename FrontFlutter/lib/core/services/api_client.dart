import 'dart:ui';

import 'package:dio/dio.dart';

import '../abstract/api_client_interface.dart';
import '../constants/api_endpoints.dart';
import '../utils/api_response_handler.dart';
import '../utils/api_types.dart';
import '../utils/app_logger.dart';
import 'storage_service.dart';

/// Client HTTP basé sur Dio, avec intercepteur d'authentification Sanctum.
class DioClient implements ApiClientInterface {
  final Dio _dio;

  DioClient(this._dio) {
    _dio.interceptors.add(_AuthInterceptor());
  }

  /// Hook appelé quand le serveur renvoie 401 (jeton invalide / expiré).
  set onAuthFailure(VoidCallback? cb) => _AuthInterceptor.onAuthFailure = cb;

  @override
  Future<ApiResult<T>> request<T>({
    required String path,
    required HttpMethod method,
    required JsonParser<T> fromJson,
    Object? data,
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final response = await _dio.request(
        path,
        data: data,
        queryParameters: queryParameters,
        options: Options(method: method.value),
      );

      return ApiResponseHandler.handle<T>(response, fromJson);
    } on DioException catch (e) {
      final body = e.response?.data;
      AppLogger.e('API ${method.value} $path', error: e);

      if (body is Map<String, dynamic>) {
        return ApiError<T>(
          message: (body['message'] as String?) ?? e.message ?? 'Erreur réseau.',
          statusCode: e.response?.statusCode,
          errors: (body['errors'] as Map?)?.cast<String, dynamic>(),
          error: e,
        );
      }

      return ApiError<T>(
        message: e.message ?? 'Impossible de joindre le serveur.',
        statusCode: e.response?.statusCode,
        error: e,
      );
    }
  }
}

/// Intercepteur Sanctum : attache le jeton Bearer ; sur 401, purge + notifie.
class _AuthInterceptor extends Interceptor {
  static VoidCallback? onAuthFailure;

  bool _isAuthEndpoint(String path) => path.contains(ApiEndpoints.login);

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    options.headers['Accept'] = 'application/json';

    if (!_isAuthEndpoint(options.path)) {
      final token = await StorageService.instance.getToken();
      if (token != null && token.isNotEmpty) {
        options.headers['Authorization'] = 'Bearer $token';
      }
    }

    handler.next(options);
  }

  @override
  Future<void> onError(
    DioException err,
    ErrorInterceptorHandler handler,
  ) async {
    final isUnauthorized = err.response?.statusCode == 401;
    if (isUnauthorized && !_isAuthEndpoint(err.requestOptions.path)) {
      await StorageService.instance.clearToken();
      onAuthFailure?.call();
    }

    handler.next(err);
  }
}
