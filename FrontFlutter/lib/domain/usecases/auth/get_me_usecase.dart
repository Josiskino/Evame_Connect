import 'package:fpdart/fpdart.dart';

import '../../../core/abstract/failure.dart';
import '../../../core/abstract/usecase.dart';
import '../../entity/user_entity.dart';
import '../../repository/auth_repository.dart';

class GetMeUseCase implements UseCase<UserEntity, NoParams> {
  final AuthRepository _repository;
  GetMeUseCase(this._repository);

  @override
  Future<Either<Failure, UserEntity>> call(NoParams params) => _repository.getMe();
}
