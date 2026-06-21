import 'package:fpdart/fpdart.dart';

import '../../../core/abstract/failure.dart';
import '../../../core/abstract/usecase.dart';
import '../../repository/auth_repository.dart';

class LogoutUseCase implements UseCase<Unit, NoParams> {
  final AuthRepository _repository;
  LogoutUseCase(this._repository);

  @override
  Future<Either<Failure, Unit>> call(NoParams params) => _repository.logout();
}
