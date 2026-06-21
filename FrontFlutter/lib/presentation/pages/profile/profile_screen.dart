import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconsax/iconsax.dart';

import '../../../config/theme/theme.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_fonts.dart';
import '../../../core/constants/design_tokens.dart';
import '../../viewmodels/auth/auth_viewmodel.dart';
import '../../viewmodels/auth/state/auth_state.dart';
import '../../widgets/app_snackbar.dart';

/// Écran Profil : informations du technicien + sous-sections (compte,
/// préférences, autres) + déconnexion.
class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authViewModelProvider);
    final user = auth is Authenticated ? auth.user : null;
    final themeMode = ref.watch(themeProvider);
    final isDark = themeMode == ThemeMode.dark;

    void todo(String label) => AppSnackbar.info(context, '« $label » — bientôt disponible.');

    return Scaffold(
      appBar: AppBar(title: const Text('Profil')),
      body: ListView(
        padding: DesignTokens.pagePadding,
        children: [
          // En-tête utilisateur
          Row(
            children: [
              CircleAvatar(
                radius: 32,
                backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                child: Text(
                  (user?.name.isNotEmpty ?? false) ? user!.name[0].toUpperCase() : '?',
                  style: const TextStyle(
                    fontFamily: AppFonts.bricolage,
                    fontSize: 26,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary,
                  ),
                ),
              ),
              const SizedBox(width: DesignTokens.spaceL),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(user?.name ?? '', style: Theme.of(context).textTheme.titleLarge),
                    Text(user?.email ?? '', style: Theme.of(context).textTheme.bodySmall),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        'Technicien SAV',
                        style: const TextStyle(
                            color: AppColors.primary, fontSize: 11, fontWeight: FontWeight.w600),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: DesignTokens.spaceXl),

          // Compte
          _SectionTitle('Compte'),
          _Tile(icon: Iconsax.user_edit, title: 'Informations personnelles', onTap: () => todo('Informations personnelles')),
          _Tile(icon: Iconsax.security_user, title: 'Sécurité & mot de passe', onTap: () => todo('Sécurité')),
          _Tile(icon: Iconsax.notification, title: 'Notifications', onTap: () => todo('Notifications')),

          const SizedBox(height: DesignTokens.spaceL),

          // Préférences
          _SectionTitle('Préférences'),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            secondary: const Icon(Iconsax.moon),
            title: const Text('Mode sombre'),
            value: isDark,
            activeThumbColor: AppColors.primary,
            onChanged: (v) => ref
                .read(themeProvider.notifier)
                .setThemeMode(v ? ThemeMode.dark : ThemeMode.light),
          ),
          _Tile(icon: Iconsax.language_square, title: 'Langue', trailing: 'Français', onTap: () => todo('Langue')),

          const SizedBox(height: DesignTokens.spaceL),

          // Autres
          _SectionTitle('Autres'),
          _Tile(icon: Iconsax.info_circle, title: 'À propos', onTap: () => todo('À propos')),
          _Tile(icon: Iconsax.message_question, title: 'Aide & support', onTap: () => todo('Aide & support')),

          const SizedBox(height: DesignTokens.spaceXl),

          // Déconnexion
          OutlinedButton.icon(
            onPressed: () => ref.read(authViewModelProvider.notifier).logout(),
            icon: const Icon(Iconsax.logout),
            label: const Text('Se déconnecter'),
          ),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final String text;
  const _SectionTitle(this.text);

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: DesignTokens.spaceS),
        child: Text(
          text.toUpperCase(),
          style: TextStyle(
            color: AppColors.secondary,
            fontSize: 12,
            fontWeight: FontWeight.w700,
            letterSpacing: 0.5,
          ),
        ),
      );
}

class _Tile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? trailing;
  final VoidCallback onTap;

  const _Tile({required this.icon, required this.title, this.trailing, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(icon),
      title: Text(title),
      trailing: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (trailing != null)
            Text(trailing!, style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(width: 4),
          const Icon(Iconsax.arrow_right_3, size: 18),
        ],
      ),
      onTap: onTap,
    );
  }
}
