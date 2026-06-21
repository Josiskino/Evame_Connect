import 'package:fpdart/fpdart.dart';

import '../../core/abstract/failure.dart';
import '../entity/intervention_entity.dart';
import 'params/intervention_params.dart';

abstract class InterventionRepository {
  Future<Either<Failure, List<InterventionEntity>>> list(ListInterventionsParams params);
  Future<Either<Failure, InterventionEntity>> getById(int id);
  Future<Either<Failure, InterventionEntity>> updateStatut(UpdateStatutParams params);
  Future<Either<Failure, InterventionEntity>> addCommentaire(AddCommentaireParams params);
}
