/// Moto concernée par une intervention.
class MotoEntity {
  final int id;
  final String modele;
  final String? classeCc;
  final String? couleur;
  final String? imageUrl;

  const MotoEntity({
    required this.id,
    required this.modele,
    this.classeCc,
    this.couleur,
    this.imageUrl,
  });
}
