/// Convertit la charge utile `data` (objet OU liste) en modèle typé.
typedef JsonParser<T> = T Function(dynamic data);

/// Méthodes HTTP supportées.
enum HttpMethod {
  get('GET'),
  post('POST'),
  put('PUT'),
  patch('PATCH'),
  delete('DELETE');

  final String value;
  const HttpMethod(this.value);
}

/// Résultat brut d'un appel API (couche data), avant conversion en [Either].
sealed class ApiResult<T> {
  const ApiResult();
  T? get dataOrNull;
}

class ApiSuccess<T> extends ApiResult<T> {
  final T data;
  final int statusCode;
  const ApiSuccess({required this.data, this.statusCode = 200});

  @override
  T? get dataOrNull => data;
}

class ApiError<T> extends ApiResult<T> {
  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;
  final Object? error;
  const ApiError({
    required this.message,
    this.statusCode,
    this.errors,
    this.error,
  });

  @override
  T? get dataOrNull => null;
}
