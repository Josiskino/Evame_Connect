import '../utils/api_types.dart';

/// Contrat du client HTTP : découple la couche data de l'implémentation (Dio).
abstract class ApiClientInterface {
  Future<ApiResult<T>> request<T>({
    required String path,
    required HttpMethod method,
    required JsonParser<T> fromJson,
    Object? data,
    Map<String, dynamic>? queryParameters,
  });
}
