import 'package:flutter/material.dart';

import '../../core/constants/app_colors.dart';
import '../../core/constants/design_tokens.dart';

/// Espace vide / erreur avec icône, message et action optionnelle.
class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  final String? actionLabel;
  final VoidCallback? onAction;

  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(DesignTokens.spaceXxl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 56, color: AppColors.secondary),
            const SizedBox(height: DesignTokens.spaceL),
            Text(title, textAlign: TextAlign.center, style: Theme.of(context).textTheme.titleMedium),
            if (subtitle != null) ...[
              const SizedBox(height: DesignTokens.spaceS),
              Text(subtitle!, textAlign: TextAlign.center, style: Theme.of(context).textTheme.bodySmall),
            ],
            if (actionLabel != null && onAction != null) ...[
              const SizedBox(height: DesignTokens.spaceL),
              OutlinedButton(onPressed: onAction, child: Text(actionLabel!)),
            ],
          ],
        ),
      ),
    );
  }
}
