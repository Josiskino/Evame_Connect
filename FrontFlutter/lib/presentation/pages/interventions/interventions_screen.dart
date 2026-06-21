import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';

import '../../../core/constants/design_tokens.dart';
import '../../../core/constants/routes_name.dart';
import '../../viewmodels/auth/auth_viewmodel.dart';
import '../../viewmodels/intervention/interventions_viewmodel.dart';
import '../../viewmodels/intervention/state/interventions_state.dart';
import '../../widgets/app_loader.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/intervention_status_chip.dart';

/// Liste des interventions — PLACEHOLDER fonctionnel.
/// À remplacer/redesigner ; consomme [InterventionsViewModel].
class InterventionsScreen extends ConsumerWidget {
  const InterventionsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(interventionsViewModelProvider);
    final vm = ref.read(interventionsViewModelProvider.notifier);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes interventions'),
        actions: [
          IconButton(
            icon: const Icon(Iconsax.logout),
            onPressed: () => ref.read(authViewModelProvider.notifier).logout(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: vm.refresh,
        child: switch (state) {
          InterventionsLoading() => const AppLoader(),
          InterventionsError(:final message) => EmptyState(
              icon: Iconsax.warning_2,
              title: 'Erreur',
              subtitle: message,
              actionLabel: 'Réessayer',
              onAction: vm.refresh,
            ),
          InterventionsLoaded(:final items) when items.isEmpty => const EmptyState(
              icon: Iconsax.task_square,
              title: 'Aucune intervention',
              subtitle: 'Aucune intervention à afficher pour le moment.',
            ),
          InterventionsLoaded(:final items) => ListView.separated(
              padding: DesignTokens.pagePadding,
              itemCount: items.length,
              separatorBuilder: (_, _) => const SizedBox(height: DesignTokens.spaceM),
              itemBuilder: (context, i) {
                final it = items[i];
                return Card(
                  child: ListTile(
                    title: Text(it.client?.nom ?? 'Client'),
                    subtitle: Text('${it.moto?.modele ?? ''}\n${it.probleme}'),
                    isThreeLine: true,
                    trailing: InterventionStatusChip(statut: it.statut, label: it.statutLabel),
                    onTap: () => context.push(AppRoute.interventionDetailPath(it.id)),
                  ),
                );
              },
            ),
        },
      ),
    );
  }
}
