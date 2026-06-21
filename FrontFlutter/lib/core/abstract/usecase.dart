import 'package:fpdart/fpdart.dart';

import 'failure.dart';

/// Cas d'usage générique : prend [Params] et renvoie soit un [Failure],
/// soit un résultat [Output].
abstract class UseCase<Output, Params> {
  Future<Either<Failure, Output>> call(Params params);
}

/// Marqueur pour les cas d'usage sans paramètre.
class NoParams {
  const NoParams();
}
