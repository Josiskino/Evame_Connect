import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';

import '../home/home_screen.dart';
import '../interventions/interventions_screen.dart';
import '../profile/profile_screen.dart';
import '../widgets/custom_bottom_nav_bar/custom_bottom_nav_bar.dart';
import '../widgets/custom_bottom_nav_bar/custom_bottom_nav_bar_item.dart';

/// Page principale avec barre de navigation inférieure.
/// Affiche un écran selon l'onglet sélectionné.
class MainPage extends ConsumerStatefulWidget {
  const MainPage({super.key});

  @override
  ConsumerState<MainPage> createState() => _MainPageState();
}

class _MainPageState extends ConsumerState<MainPage> {
  int _selectedIndex = 0;

  final List<Widget> _screens = const [
    HomeScreen(),
    InterventionsScreen(),
    ProfileScreen(),
  ];

  final List<CustomBottomAppBarItem> _items = const [
    CustomBottomAppBarItem(icon: Iconsax.home_2, text: 'Accueil'),
    CustomBottomAppBarItem(icon: Iconsax.task_square, text: 'Interventions'),
    CustomBottomAppBarItem(icon: Iconsax.user, text: 'Profil'),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(index: _selectedIndex, children: _screens),
      bottomNavigationBar: CustomBottomAppBar(
        selectedTab: _selectedIndex,
        children: _items,
        onTap: (index) => setState(() => _selectedIndex = index),
      ),
    );
  }
}
