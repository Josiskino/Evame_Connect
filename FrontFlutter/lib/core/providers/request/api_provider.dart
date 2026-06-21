import 'package:dio/dio.dart';
import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../constants/api_constant.dart';
import '../../services/api_client.dart';

part 'api_provider.g.dart';

@riverpod
Dio dio(Ref ref) {
  return Dio(
    BaseOptions(
      baseUrl: ApiConstant.baseUrl,
      connectTimeout: ApiConstant.connectTimeout,
      receiveTimeout: ApiConstant.receiveTimeout,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ),
  );
}

@riverpod
DioClient apiClient(Ref ref) {
  return DioClient(ref.watch(dioProvider));
}
