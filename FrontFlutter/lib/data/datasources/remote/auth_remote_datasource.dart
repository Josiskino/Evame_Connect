import '../../../core/abstract/api_client_interface.dart';
import '../../../core/constants/api_endpoints.dart';
import '../../../core/utils/api_types.dart';
import '../../../domain/repository/params/auth_params.dart';
import '../../models/auth/auth_response_model.dart';
import '../../models/auth/user_model.dart';
import '../abstract/auth_datasource.dart';

class AuthRemoteDataSource implements AuthDataSource {
  final ApiClientInterface _client;
  AuthRemoteDataSource(this._client);

  @override
  Future<ApiResult<AuthResponseModel>> login(LoginParams params) {
    return _client.request<AuthResponseModel>(
      path: ApiEndpoints.login,
      method: HttpMethod.post,
      data: params.toJson(),
      fromJson: (d) => AuthResponseModel.fromJson(d as Map<String, dynamic>),
    );
  }

  @override
  Future<ApiResult<UserModel>> getMe() {
    return _client.request<UserModel>(
      path: ApiEndpoints.me,
      method: HttpMethod.get,
      fromJson: (d) => UserModel.fromJson(d as Map<String, dynamic>),
    );
  }

  @override
  Future<ApiResult<bool>> logout() {
    return _client.request<bool>(
      path: ApiEndpoints.logout,
      method: HttpMethod.post,
      fromJson: (_) => true,
    );
  }
}
