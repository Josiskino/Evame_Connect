import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';

import '../../../core/constants/design_tokens.dart';
import '../../../domain/entity/intervention_entity.dart';
import '../../viewmodels/intervention/intervention_detail_viewmodel.dart';
import '../../viewmodels/intervention/state/intervention_detail_state.dart';
import '../../widgets/app_loader.dart';
import '../../widgets/app_snackbar.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/intervention_status_chip.dart';

/// Fiche intervention — PLACEHOLDER fonctionnel.
/// À remplacer/redesigner ; consomme [InterventionDetailViewModel].
class InterventionDetailScreen extends ConsumerWidget {
  final int id;
  const InterventionDetailScreen({super.key, required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(interventionDetailViewModelProvider(id));
    final vm = ref.read(interventionDetailViewModelProvider(id).notifier);

    return Scaffold(
      appBar: AppBar(title: const Text('Intervention')),
      body: switch (state) {
        InterventionDetailLoading() => const AppLoader(),
        InterventionDetailError(:final message) =>
          EmptyState(icon: Iconsax.warning_2, title: 'Erreur', subtitle: message),
        InterventionDetailLoaded(:final intervention, :final isSubmitting) =>
          _Detail(intervention: intervention, isSubmitting: isSubmitting, vm: vm),
      },
    );
  }
}

class _Detail extends StatelessWidget {
  final InterventionEntity intervention;
  final bool isSubmitting;
  final InterventionDetailViewModel vm;
  const _Detail({required this.intervention, required this.isSubmitting, required this.vm});

  Future<void> _changeStatut(BuildContext context, String statut) async {
    final err = await vm.changeStatut(statut);
    if (!context.mounted) return;
    err == null
        ? AppSnackbar.success(context, 'Statut mis à jour.')
        : AppSnackbar.error(context, err);
  }

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: DesignTokens.pagePadding,
      children: [
        Row(
          children: [
            Expanded(child: Text(intervention.client?.nom ?? 'Client',
                style: Theme.of(context).textTheme.titleLarge)),
            InterventionStatusChip(statut: intervention.statut, label: intervention.statutLabel),
          ],
        ),
        const SizedBox(height: DesignTokens.spaceS),
        if (intervention.moto != null) Text('Moto : ${intervention.moto!.modele}'),
        const SizedBox(height: DesignTokens.spaceM),
        Text('Problème', style: Theme.of(context).textTheme.titleMedium),
        Text(intervention.probleme),
        const SizedBox(height: DesignTokens.spaceL),
        Text('Changer le statut', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: DesignTokens.spaceS),
        Wrap(
          spacing: DesignTokens.spaceS,
          children: InterventionStatut.all
              .map((s) => ChoiceChip(
                    label: Text(InterventionStatut.label(s)),
                    selected: intervention.statut == s,
                    onSelected: isSubmitting ? null : (_) => _changeStatut(context, s),
                  ))
              .toList(),
        ),
        const SizedBox(height: DesignTokens.spaceL),
        Text('Commentaires (${intervention.commentaires.length})',
            style: Theme.of(context).textTheme.titleMedium),
        ...intervention.commentaires.map(
          (c) => ListTile(
            leading: const Icon(Iconsax.message_text),
            title: Text(c.contenu),
            subtitle: Text('${c.auteur?.name ?? ''} · ${c.date ?? ''}'),
          ),
        ),
      ],
    );
  }
}
