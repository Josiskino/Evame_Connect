import '../utils/api_types.dart';
import 'failure.dart';

/// Convertit une [ApiError] (couche data) en [Failure] (couche domaine).
mixin BaseRepository {
  Failure mapError<T>(ApiError<T> error) {
    final code = error.statusCode;
    return switch (code) {
      401 => AuthFailure(error.message, statusCode: code),
      422 => ValidationFailure(error.message, statusCode: code, errors: error.errors),
      null => NetworkFailure(error.message),
      _ when code >= 500 => ServerFailure(error.message, statusCode: code),
      _ => UnknownFailure(error.message, statusCode: code),
    };
  }
}
