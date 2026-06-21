import 'package:fpdart/fpdart.dart';

import '../../core/abstract/failure.dart';
import '../entity/user_entity.dart';
import 'params/auth_params.dart';

abstract class AuthRepository {
  Future<Either<Failure, UserEntity>> login(LoginParams params);
  Future<Either<Failure, UserEntity>> getMe();
  Future<Either<Failure, Unit>> logout();

  /// Utilisateur déjà connecté (jeton présent) ? null sinon.
  Future<Either<Failure, UserEntity?>> currentUser();
}
