import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/routes_name.dart';
import '../../../domain/entity/intervention_entity.dart';
import '../../viewmodels/intervention/interventions_viewmodel.dart';
import '../../viewmodels/intervention/state/interventions_state.dart';
import '../../widgets/app_loader.dart';
import '../../widgets/empty_state.dart';

/// Écran des notifications — alimenté par les interventions du technicien
/// (les « nouvelles » apparaissent comme non lues). Reprend le design de
/// l'application de référence (tuiles, surlignage des non-lues).
class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  IconData _icon(String statut) => switch (statut) {
        InterventionStatut.nouvelle => Iconsax.notification_status,
        InterventionStatut.enTraitement => Iconsax.timer_1,
        InterventionStatut.terminee => Iconsax.tick_circle,
        _ => Iconsax.task_square,
      };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(interventionsViewModelProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Notifications')),
      body: switch (state) {
        InterventionsLoading() => const AppLoader(),
        InterventionsError(:final message) => EmptyState(
            icon: Iconsax.warning_2,
            title: 'Erreur',
            subtitle: message,
            actionLabel: 'Réessayer',
            onAction: () => ref.read(interventionsViewModelProvider.notifier).refresh(),
          ),
        InterventionsLoaded(:final items) when items.isEmpty => const EmptyState(
            icon: Iconsax.notification,
            title: 'Aucune notification',
            subtitle: 'Vous serez notifié des nouvelles interventions ici.',
          ),
        InterventionsLoaded(:final items) => RefreshIndicator(
            onRefresh: ref.read(interventionsViewModelProvider.notifier).refresh,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(vertical: 8),
              itemCount: items.length,
              separatorBuilder: (_, _) => const Divider(height: 1),
              itemBuilder: (context, i) => _NotificationTile(
                intervention: items[i],
                icon: _icon(items[i].statut),
                onTap: () => context.push(AppRoute.interventionDetailPath(items[i].id)),
              ),
            ),
          ),
      },
    );
  }
}

class _NotificationTile extends StatelessWidget {
  final InterventionEntity intervention;
  final IconData icon;
  final VoidCallback onTap;

  const _NotificationTile({
    required this.intervention,
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final isUnread = intervention.statut == InterventionStatut.nouvelle;
    final color = AppColors.forStatut(intervention.statut);

    return Material(
      color: isUnread ? AppColors.primary.withValues(alpha: 0.05) : Colors.transparent,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleAvatar(
                radius: 22,
                backgroundColor: color.withValues(alpha: 0.12),
                child: Icon(icon, color: color, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      intervention.client?.nom ?? 'Intervention',
                      style: Theme.of(context).textTheme.titleSmall,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${intervention.moto?.modele ?? ''} • ${intervention.statutLabel}',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ],
                ),
              ),
              if (intervention.dateIntervention != null)
                Text(intervention.dateIntervention!, style: Theme.of(context).textTheme.bodySmall),
              if (isUnread)
                Container(
                  margin: const EdgeInsets.only(left: 8, top: 6),
                  width: 8,
                  height: 8,
                  decoration: const BoxDecoration(color: AppColors.primary, shape: BoxShape.circle),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
