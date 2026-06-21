import 'package:fpdart/fpdart.dart';

import '../../../core/abstract/failure.dart';
import '../../../core/abstract/usecase.dart';
import '../../entity/intervention_entity.dart';
import '../../repository/intervention_repository.dart';
import '../../repository/params/intervention_params.dart';

class UpdateInterventionStatusUseCase
    implements UseCase<InterventionEntity, UpdateStatutParams> {
  final InterventionRepository _repository;
  UpdateInterventionStatusUseCase(this._repository);

  @override
  Future<Either<Failure, InterventionEntity>> call(UpdateStatutParams params) =>
      _repository.updateStatut(params);
}
