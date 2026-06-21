import 'package:fpdart/fpdart.dart';

import '../../../core/abstract/failure.dart';
import '../../../core/abstract/usecase.dart';
import '../../entity/intervention_entity.dart';
import '../../repository/intervention_repository.dart';

class GetInterventionUseCase implements UseCase<InterventionEntity, int> {
  final InterventionRepository _repository;
  GetInterventionUseCase(this._repository);

  @override
  Future<Either<Failure, InterventionEntity>> call(int id) =>
      _repository.getById(id);
}
