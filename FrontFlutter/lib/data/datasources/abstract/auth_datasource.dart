import '../../../core/utils/api_types.dart';
import '../../../domain/repository/params/auth_params.dart';
import '../../models/auth/auth_response_model.dart';
import '../../models/auth/user_model.dart';

abstract class AuthDataSource {
  Future<ApiResult<AuthResponseModel>> login(LoginParams params);
  Future<ApiResult<UserModel>> getMe();
  Future<ApiResult<bool>> logout();
}
