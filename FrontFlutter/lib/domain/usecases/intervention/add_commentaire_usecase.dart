import 'package:fpdart/fpdart.dart';

import '../../../core/abstract/failure.dart';
import '../../../core/abstract/usecase.dart';
import '../../entity/intervention_entity.dart';
import '../../repository/intervention_repository.dart';
import '../../repository/params/intervention_params.dart';

class AddCommentaireUseCase
    implements UseCase<InterventionEntity, AddCommentaireParams> {
  final InterventionRepository _repository;
  AddCommentaireUseCase(this._repository);

  @override
  Future<Either<Failure, InterventionEntity>> call(AddCommentaireParams params) =>
      _repository.addCommentaire(params);
}
