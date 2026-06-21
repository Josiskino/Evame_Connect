import '../../../core/utils/api_types.dart';
import '../../../domain/repository/params/intervention_params.dart';
import '../../models/intervention/intervention_model.dart';

abstract class InterventionDataSource {
  Future<ApiResult<List<InterventionModel>>> list(ListInterventionsParams params);
  Future<ApiResult<InterventionModel>> getById(int id);
  Future<ApiResult<InterventionModel>> updateStatut(UpdateStatutParams params);
  Future<ApiResult<InterventionModel>> addCommentaire(AddCommentaireParams params);
}
