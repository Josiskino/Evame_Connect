import 'package:fpdart/fpdart.dart';

import '../../../core/abstract/failure.dart';
import '../../../core/abstract/usecase.dart';
import '../../entity/user_entity.dart';
import '../../repository/auth_repository.dart';
import '../../repository/params/auth_params.dart';

class LoginUseCase implements UseCase<UserEntity, LoginParams> {
  final AuthRepository _repository;
  LoginUseCase(this._repository);

  @override
  Future<Either<Failure, UserEntity>> call(LoginParams params) =>
      _repository.login(params);
}
