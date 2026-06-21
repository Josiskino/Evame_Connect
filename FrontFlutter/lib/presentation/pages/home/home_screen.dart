import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_fonts.dart';
import '../../../core/constants/design_tokens.dart';
import '../../../core/constants/routes_name.dart';
import '../../../domain/entity/intervention_entity.dart';
import '../../viewmodels/auth/auth_viewmodel.dart';
import '../../viewmodels/auth/state/auth_state.dart';
import '../../viewmodels/intervention/interventions_viewmodel.dart';
import '../../viewmodels/intervention/state/interventions_state.dart';

/// Écran d'accueil : salutation, indicateurs rapides, action « Nouvelle intervention ».
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  int _count(List<InterventionEntity> items, String statut) =>
      items.where((e) => e.statut == statut).length;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authViewModelProvider);
    final name = auth is Authenticated ? auth.user.name : '';
    final state = ref.watch(interventionsViewModelProvider);
    final items = state is InterventionsLoaded ? state.items : <InterventionEntity>[];

    return Scaffold(
      appBar: AppBar(title: const Text('Accueil')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push(AppRoute.newIntervention),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        icon: const Icon(Iconsax.add),
        label: const Text('Nouvelle intervention'),
      ),
      body: ListView(
        padding: DesignTokens.pagePadding,
        children: [
          Text('Bonjour 👋', style: Theme.of(context).textTheme.bodyMedium),
          const SizedBox(height: 2),
          Text(
            name,
            style: const TextStyle(
              fontFamily: AppFonts.bricolage,
              fontSize: 24,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: DesignTokens.spaceXl),
          Text('Aperçu de mes interventions', style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: DesignTokens.spaceM),
          Row(
            children: [
              _StatCard(
                label: 'Nouvelles',
                value: _count(items, InterventionStatut.nouvelle),
                color: AppColors.statutNouvelle,
                icon: Iconsax.notification_status,
              ),
              const SizedBox(width: DesignTokens.spaceM),
              _StatCard(
                label: 'En traitement',
                value: _count(items, InterventionStatut.enTraitement),
                color: AppColors.statutEnTraitement,
                icon: Iconsax.timer_1,
              ),
            ],
          ),
          const SizedBox(height: DesignTokens.spaceM),
          Row(
            children: [
              _StatCard(
                label: 'Terminées',
                value: _count(items, InterventionStatut.terminee),
                color: AppColors.statutTerminee,
                icon: Iconsax.tick_circle,
              ),
              const SizedBox(width: DesignTokens.spaceM),
              _StatCard(
                label: 'Total',
                value: items.length,
                color: AppColors.secondary,
                icon: Iconsax.task_square,
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final int value;
  final Color color;
  final IconData icon;

  const _StatCard({
    required this.label,
    required this.value,
    required this.color,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(DesignTokens.spaceL),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.10),
          borderRadius: DesignTokens.borderRadiusL,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: color, size: DesignTokens.iconL),
            const SizedBox(height: DesignTokens.spaceM),
            Text(
              '$value',
              style: TextStyle(
                fontFamily: AppFonts.bricolage,
                fontSize: 26,
                fontWeight: FontWeight.w700,
                color: color,
              ),
            ),
            Text(label, style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
      ),
    );
  }
}
