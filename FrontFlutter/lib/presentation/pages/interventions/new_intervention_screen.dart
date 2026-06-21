import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';

import '../../widgets/empty_state.dart';

/// Création d'une intervention — PLACEHOLDER (formulaire à implémenter).
class NewInterventionScreen extends StatelessWidget {
  const NewInterventionScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Nouvelle intervention')),
      body: const EmptyState(
        icon: Iconsax.add_square,
        title: 'Formulaire à implémenter',
        subtitle: 'Sélection du client, de la moto et description du problème.',
      ),
    );
  }
}
