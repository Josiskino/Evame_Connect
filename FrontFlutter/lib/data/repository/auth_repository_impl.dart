import 'package:fpdart/fpdart.dart';

import '../../core/abstract/base_repository.dart';
import '../../core/abstract/failure.dart';
import '../../core/services/storage_service.dart';
import '../../core/utils/api_types.dart';
import '../../domain/entity/user_entity.dart';
import '../../domain/repository/auth_repository.dart';
import '../../domain/repository/params/auth_params.dart';
import '../datasources/abstract/auth_datasource.dart';
import '../models/auth/auth_response_model.dart';
import '../models/auth/user_model.dart';

class AuthRepositoryImpl with BaseRepository implements AuthRepository {
  final AuthDataSource _dataSource;
  AuthRepositoryImpl(this._dataSource);

  @override
  Future<Either<Failure, UserEntity>> login(LoginParams params) async {
    final res = await _dataSource.login(params);
    if (res is ApiSuccess<AuthResponseModel>) {
      await StorageService.instance.saveToken(res.data.token);
      return right(res.data.user.toEntity());
    }
    return left(mapError(res as ApiError<AuthResponseModel>));
  }

  @override
  Future<Either<Failure, UserEntity>> getMe() async {
    final res = await _dataSource.getMe();
    if (res is ApiSuccess<UserModel>) {
      return right(res.data.toEntity());
    }
    return left(mapError(res as ApiError<UserModel>));
  }

  @override
  Future<Either<Failure, Unit>> logout() async {
    // On tente l'appel serveur, mais on purge toujours le jeton local.
    await _dataSource.logout();
    await StorageService.instance.clearToken();
    return right(unit);
  }

  @override
  Future<Either<Failure, UserEntity?>> currentUser() async {
    final hasToken = await StorageService.instance.hasToken();
    if (!hasToken) return right(null);

    final res = await _dataSource.getMe();
    if (res is ApiSuccess<UserModel>) {
      return right(res.data.toEntity());
    }
    // Jeton invalide -> on le purge et on considère l'utilisateur déconnecté.
    await StorageService.instance.clearToken();
    return right(null);
  }
}
