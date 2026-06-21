import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../../data/datasources/abstract/auth_datasource.dart';
import '../../../data/datasources/remote/auth_remote_datasource.dart';
import '../../../data/repository/auth_repository_impl.dart';
import '../../../domain/repository/auth_repository.dart';
import '../../../domain/usecases/auth/get_me_usecase.dart';
import '../../../domain/usecases/auth/login_usecase.dart';
import '../../../domain/usecases/auth/logout_usecase.dart';
import '../request/api_provider.dart';

part 'auth_provider.g.dart';

@riverpod
AuthDataSource authDataSource(Ref ref) =>
    AuthRemoteDataSource(ref.watch(apiClientProvider));

@riverpod
AuthRepository authRepository(Ref ref) =>
    AuthRepositoryImpl(ref.watch(authDataSourceProvider));

@riverpod
LoginUseCase loginUseCase(Ref ref) =>
    LoginUseCase(ref.watch(authRepositoryProvider));

@riverpod
GetMeUseCase getMeUseCase(Ref ref) =>
    GetMeUseCase(ref.watch(authRepositoryProvider));

@riverpod
LogoutUseCase logoutUseCase(Ref ref) =>
    LogoutUseCase(ref.watch(authRepositoryProvider));
