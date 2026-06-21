import 'package:fpdart/fpdart.dart';

import '../../../core/abstract/failure.dart';
import '../../../core/abstract/usecase.dart';
import '../../entity/intervention_entity.dart';
import '../../repository/intervention_repository.dart';
import '../../repository/params/intervention_params.dart';

class ListInterventionsUseCase
    implements UseCase<List<InterventionEntity>, ListInterventionsParams> {
  final InterventionRepository _repository;
  ListInterventionsUseCase(this._repository);

  @override
  Future<Either<Failure, List<InterventionEntity>>> call(
          ListInterventionsParams params) =>
      _repository.list(params);
}
