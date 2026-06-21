import 'package:flutter/material.dart';

import '../../core/constants/app_colors.dart';
import '../../domain/entity/intervention_entity.dart';

/// Pastille colorée représentant le statut d'une intervention.
class InterventionStatusChip extends StatelessWidget {
  final String statut;
  final String? label;

  const InterventionStatusChip({super.key, required this.statut, this.label});

  @override
  Widget build(BuildContext context) {
    final color = AppColors.forStatut(statut);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label ?? InterventionStatut.label(statut),
        style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w600),
      ),
    );
  }
}
