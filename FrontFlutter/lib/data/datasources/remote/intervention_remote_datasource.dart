import '../../../core/abstract/api_client_interface.dart';
import '../../../core/constants/api_endpoints.dart';
import '../../../core/utils/api_types.dart';
import '../../../domain/repository/params/intervention_params.dart';
import '../../models/intervention/intervention_model.dart';
import '../abstract/intervention_datasource.dart';

class InterventionRemoteDataSource implements InterventionDataSource {
  final ApiClientInterface _client;
  InterventionRemoteDataSource(this._client);

  @override
  Future<ApiResult<List<InterventionModel>>> list(ListInterventionsParams params) {
    return _client.request<List<InterventionModel>>(
      path: ApiEndpoints.interventions,
      method: HttpMethod.get,
      queryParameters: params.toQuery(),
      fromJson: (d) => (d as List)
          .map((e) => InterventionModel.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  @override
  Future<ApiResult<InterventionModel>> getById(int id) {
    return _client.request<InterventionModel>(
      path: ApiEndpoints.interventionById(id),
      method: HttpMethod.get,
      fromJson: (d) => InterventionModel.fromJson(d as Map<String, dynamic>),
    );
  }

  @override
  Future<ApiResult<InterventionModel>> updateStatut(UpdateStatutParams params) {
    return _client.request<InterventionModel>(
      path: ApiEndpoints.interventionById(params.id),
      method: HttpMethod.put,
      data: params.toJson(),
      fromJson: (d) => InterventionModel.fromJson(d as Map<String, dynamic>),
    );
  }

  @override
  Future<ApiResult<InterventionModel>> addCommentaire(AddCommentaireParams params) {
    return _client.request<InterventionModel>(
      path: ApiEndpoints.interventionCommentaires(params.id),
      method: HttpMethod.post,
      data: params.toJson(),
      fromJson: (d) => InterventionModel.fromJson(d as Map<String, dynamic>),
    );
  }
}
