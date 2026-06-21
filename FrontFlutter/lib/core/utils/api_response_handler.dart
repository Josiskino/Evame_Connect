import 'package:dio/dio.dart';

import 'api_types.dart';

/// Interprète l'enveloppe unifiée de l'API EVAME :
/// `{ status, message, data, meta, errors }`.
class ApiResponseHandler {
  ApiResponseHandler._();

  static ApiResult<T> handle<T>(Response response, JsonParser<T> parser) {
    final body = response.data;
    final code = response.statusCode ?? 0;

    if (body is Map<String, dynamic>) {
      final isSuccess = body['status'] == 'success' || (code >= 200 && code < 300);
      if (isSuccess) {
        return ApiSuccess<T>(data: parser(body['data']), statusCode: code);
      }

      return ApiError<T>(
        message: (body['message'] as String?) ?? 'Une erreur est survenue.',
        statusCode: code,
        errors: (body['errors'] as Map?)?.cast<String, dynamic>(),
      );
    }

    // Réponse non conforme à l'enveloppe attendue.
    return ApiError<T>(message: 'Réponse inattendue du serveur.', statusCode: code);
  }
}
