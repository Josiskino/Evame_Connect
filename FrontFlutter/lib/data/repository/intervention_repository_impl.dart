import 'package:fpdart/fpdart.dart';

import '../../core/abstract/base_repository.dart';
import '../../core/abstract/failure.dart';
import '../../core/utils/api_types.dart';
import '../../domain/entity/intervention_entity.dart';
import '../../domain/repository/intervention_repository.dart';
import '../../domain/repository/params/intervention_params.dart';
import '../datasources/abstract/intervention_datasource.dart';
import '../models/intervention/intervention_model.dart';

class InterventionRepositoryImpl with BaseRepository implements InterventionRepository {
  final InterventionDataSource _dataSource;
  InterventionRepositoryImpl(this._dataSource);

  @override
  Future<Either<Failure, List<InterventionEntity>>> list(
      ListInterventionsParams params) async {
    final res = await _dataSource.list(params);
    if (res is ApiSuccess<List<InterventionModel>>) {
      return right(res.data.map((m) => m.toEntity()).toList());
    }
    return left(mapError(res as ApiError<List<InterventionModel>>));
  }

  @override
  Future<Either<Failure, InterventionEntity>> getById(int id) async {
    final res = await _dataSource.getById(id);
    return _single(res);
  }

  @override
  Future<Either<Failure, InterventionEntity>> updateStatut(
      UpdateStatutParams params) async {
    final res = await _dataSource.updateStatut(params);
    return _single(res);
  }

  @override
  Future<Either<Failure, InterventionEntity>> addCommentaire(
      AddCommentaireParams params) async {
    final res = await _dataSource.addCommentaire(params);
    return _single(res);
  }

  Either<Failure, InterventionEntity> _single(ApiResult<InterventionModel> res) {
    if (res is ApiSuccess<InterventionModel>) {
      return right(res.data.toEntity());
    }
    return left(mapError(res as ApiError<InterventionModel>));
  }
}
