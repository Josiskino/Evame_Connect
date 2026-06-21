import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_fonts.dart';
import '../../../core/constants/design_tokens.dart';
import '../../../domain/entity/intervention_entity.dart';
import '../../viewmodels/auth/auth_viewmodel.dart';
import '../../viewmodels/auth/state/auth_state.dart';
import '../../viewmodels/intervention/interventions_viewmodel.dart';
import '../../viewmodels/intervention/state/interventions_state.dart';
import '../../widgets/notification_icon_button.dart';

/// Écran d'accueil : en-tête (avatar + nom) et aperçu des interventions.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  int _count(List<InterventionEntity> items, String statut) =>
      items.where((e) => e.statut == statut).length;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authViewModelProvider);
    final name = auth is Authenticated ? auth.user.name : '';
    final initial = name.isNotEmpty ? name[0].toUpperCase() : '?';

    final state = ref.watch(interventionsViewModelProvider);
    final items = state is InterventionsLoaded ? state.items : <InterventionEntity>[];

    return Scaffold(
      appBar: AppBar(
        titleSpacing: DesignTokens.spaceL,
        title: Row(
          children: [
            CircleAvatar(
              radius: 18,
              backgroundColor: AppColors.primary.withValues(alpha: 0.12),
              child: Text(
                initial,
                style: const TextStyle(
                  fontFamily: AppFonts.bricolage,
                  fontWeight: FontWeight.w700,
                  color: AppColors.primary,
                ),
              ),
            ),
            const SizedBox(width: DesignTokens.spaceM),
            Expanded(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Bonjour 👋', style: Theme.of(context).textTheme.bodySmall),
                  Text(
                    name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontFamily: AppFonts.bricolage,
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: const [NotificationIconButton(), SizedBox(width: 4)],
      ),
      body: ListView(
        padding: DesignTokens.pagePadding,
        children: [
          Text('Aperçu de mes interventions', style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: DesignTokens.spaceM),
          Row(
            children: [
              _StatCard(label: 'Nouvelles', value: _count(items, InterventionStatut.nouvelle), icon: Iconsax.notification_status),
              const SizedBox(width: DesignTokens.spaceM),
              _StatCard(label: 'En traitement', value: _count(items, InterventionStatut.enTraitement), icon: Iconsax.timer_1),
            ],
          ),
          const SizedBox(height: DesignTokens.spaceM),
          Row(
            children: [
              _StatCard(label: 'Terminées', value: _count(items, InterventionStatut.terminee), icon: Iconsax.tick_circle),
              const SizedBox(width: DesignTokens.spaceM),
              _StatCard(label: 'Total', value: items.length, icon: Iconsax.task_square),
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
  final IconData icon;

  const _StatCard({required this.label, required this.value, required this.icon});

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    // Couleur uniforme gris clair (adaptée au thème).
    final bg = isDark ? AppColors.surfaceDark : const Color(0xFFF1F1F4);
    final iconColor = isDark ? AppColors.subTextDark : AppColors.subTextLight;
    final valueColor = isDark ? AppColors.textDark : AppColors.textLight;

    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(DesignTokens.spaceL),
        decoration: BoxDecoration(color: bg, borderRadius: DesignTokens.borderRadiusL),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: iconColor, size: DesignTokens.iconL),
            const SizedBox(height: DesignTokens.spaceM),
            Text(
              '$value',
              style: TextStyle(
                fontFamily: AppFonts.bricolage,
                fontSize: 26,
                fontWeight: FontWeight.w700,
                color: valueColor,
              ),
            ),
            Text(label, style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
      ),
    );
  }
}
