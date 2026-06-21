import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/design_tokens.dart';
import 'custom_bottom_nav_bar_item.dart';

/// Barre de navigation inférieure — design plat, pastille sur l'onglet actif.
class CustomBottomAppBar extends StatelessWidget {
  final int selectedTab;
  final List<CustomBottomAppBarItem> children;
  final ValueChanged<int> onTap;

  const CustomBottomAppBar({
    super.key,
    required this.selectedTab,
    required this.children,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final inactive = isDark ? AppColors.subTextDark : AppColors.subTextLight;

    return Container(
      decoration: BoxDecoration(
        color: isDark ? AppColors.surfaceDark : AppColors.surfaceLight,
        border: Border(
          top: BorderSide(
            color: isDark ? AppColors.borderDark : AppColors.borderLight,
            width: 1,
          ),
        ),
      ),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: 62,
          child: Row(
            children: List.generate(children.length, (index) {
              final isSelected = selectedTab == index;
              final color = isSelected ? AppColors.primary : inactive;

              return Expanded(
                child: GestureDetector(
                  behavior: HitTestBehavior.opaque,
                  onTap: () => onTap(index),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      AnimatedContainer(
                        duration: const Duration(milliseconds: 250),
                        curve: Curves.easeOut,
                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
                        decoration: BoxDecoration(
                          color: isSelected
                              ? AppColors.primary.withValues(alpha: 0.12)
                              : Colors.transparent,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Icon(children[index].icon, color: color, size: DesignTokens.iconM),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        children[index].text,
                        style: TextStyle(
                          color: color,
                          fontSize: 11,
                          fontWeight: isSelected ? FontWeight.w600 : FontWeight.w400,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }),
          ),
        ),
      ),
    );
  }
}
