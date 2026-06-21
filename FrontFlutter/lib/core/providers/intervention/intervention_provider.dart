import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../../data/datasources/abstract/intervention_datasource.dart';
import '../../../data/datasources/remote/intervention_remote_datasource.dart';
import '../../../data/repository/intervention_repository_impl.dart';
import '../../../domain/repository/intervention_repository.dart';
import '../../../domain/usecases/intervention/add_commentaire_usecase.dart';
import '../../../domain/usecases/intervention/get_intervention_usecase.dart';
import '../../../domain/usecases/intervention/list_interventions_usecase.dart';
import '../../../domain/usecases/intervention/update_intervention_status_usecase.dart';
import '../request/api_provider.dart';

part 'intervention_provider.g.dart';

@riverpod
InterventionDataSource interventionDataSource(Ref ref) =>
    InterventionRemoteDataSource(ref.watch(apiClientProvider));

@riverpod
InterventionRepository interventionRepository(Ref ref) =>
    InterventionRepositoryImpl(ref.watch(interventionDataSourceProvider));

@riverpod
ListInterventionsUseCase listInterventionsUseCase(Ref ref) =>
    ListInterventionsUseCase(ref.watch(interventionRepositoryProvider));

@riverpod
GetInterventionUseCase getInterventionUseCase(Ref ref) =>
    GetInterventionUseCase(ref.watch(interventionRepositoryProvider));

@riverpod
UpdateInterventionStatusUseCase updateInterventionStatusUseCase(Ref ref) =>
    UpdateInterventionStatusUseCase(ref.watch(interventionRepositoryProvider));

@riverpod
AddCommentaireUseCase addCommentaireUseCase(Ref ref) =>
    AddCommentaireUseCase(ref.watch(interventionRepositoryProvider));
