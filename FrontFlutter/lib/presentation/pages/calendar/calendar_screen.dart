import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:iconsax/iconsax.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_fonts.dart';
import '../../../core/constants/design_tokens.dart';
import '../../../core/constants/routes_name.dart';
import '../../../domain/entity/intervention_entity.dart';
import '../../viewmodels/intervention/interventions_viewmodel.dart';
import '../../viewmodels/intervention/state/interventions_state.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/intervention_status_chip.dart';

const _months = [
  'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
];
const _weekdays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

String _dateKey(DateTime d) => '${d.year}-${d.month}-${d.day}';

/// Calendrier mensuel des rendez-vous (interventions du technicien).
/// Design inspiré du calendrier de l'app yaadha (sélecteur de mois + grille).
class CalendarScreen extends ConsumerStatefulWidget {
  const CalendarScreen({super.key});

  @override
  ConsumerState<CalendarScreen> createState() => _CalendarScreenState();
}

class _CalendarScreenState extends ConsumerState<CalendarScreen> {
  late DateTime _month; // 1er jour du mois affiché
  late DateTime _selected;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _month = DateTime(now.year, now.month);
    _selected = DateTime(now.year, now.month, now.day);
  }

  void _shiftMonth(int delta) {
    setState(() => _month = DateTime(_month.year, _month.month + delta));
  }

  Map<String, List<InterventionEntity>> _byDay(List<InterventionEntity> items) {
    final map = <String, List<InterventionEntity>>{};
    for (final it in items) {
      final d = DateTime.tryParse(it.dateIntervention ?? '');
      if (d == null) continue;
      map.putIfAbsent(_dateKey(d), () => []).add(it);
    }
    return map;
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(interventionsViewModelProvider);
    final items = state is InterventionsLoaded ? state.items : <InterventionEntity>[];
    final byDay = _byDay(items);
    final selectedItems = byDay[_dateKey(_selected)] ?? [];

    return Scaffold(
      appBar: AppBar(title: const Text('Calendrier')),
      body: ListView(
        padding: DesignTokens.pagePadding,
        children: [
          _MonthHeader(
            label: '${_months[_month.month - 1]} ${_month.year}',
            onPrev: () => _shiftMonth(-1),
            onNext: () => _shiftMonth(1),
          ),
          const SizedBox(height: DesignTokens.spaceM),
          _WeekdayRow(),
          const SizedBox(height: DesignTokens.spaceS),
          _MonthGrid(
            month: _month,
            selected: _selected,
            byDay: byDay,
            onSelect: (d) => setState(() => _selected = d),
          ),
          const SizedBox(height: DesignTokens.spaceXl),
          Text(
            'Rendez-vous du ${_selected.day} ${_months[_selected.month - 1].toLowerCase()}',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: DesignTokens.spaceS),
          if (selectedItems.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: DesignTokens.spaceXl),
              child: EmptyState(
                icon: Iconsax.calendar_remove,
                title: 'Aucun rendez-vous',
                subtitle: 'Aucune intervention prévue ce jour.',
              ),
            )
          else
            ...selectedItems.map(
              (it) => Card(
                margin: const EdgeInsets.only(bottom: DesignTokens.spaceM),
                child: ListTile(
                  leading: const Icon(Iconsax.calendar_tick, color: AppColors.primary),
                  title: Text(it.client?.nom ?? 'Client'),
                  subtitle: Text('${it.moto?.modele ?? ''} • ${it.probleme}'),
                  trailing: InterventionStatusChip(statut: it.statut, label: it.statutLabel),
                  onTap: () => context.push(AppRoute.interventionDetailPath(it.id)),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _MonthHeader extends StatelessWidget {
  final String label;
  final VoidCallback onPrev;
  final VoidCallback onNext;
  const _MonthHeader({required this.label, required this.onPrev, required this.onNext});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        IconButton(icon: const Icon(Iconsax.arrow_left_2), onPressed: onPrev),
        Text(
          label,
          style: const TextStyle(fontFamily: AppFonts.bricolage, fontSize: 18, fontWeight: FontWeight.w700),
        ),
        IconButton(icon: const Icon(Iconsax.arrow_right_3), onPressed: onNext),
      ],
    );
  }
}

class _WeekdayRow extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Row(
      children: _weekdays
          .map((d) => Expanded(
                child: Text(
                  d,
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ))
          .toList(),
    );
  }
}

class _MonthGrid extends StatelessWidget {
  final DateTime month;
  final DateTime selected;
  final Map<String, List<InterventionEntity>> byDay;
  final ValueChanged<DateTime> onSelect;

  const _MonthGrid({
    required this.month,
    required this.selected,
    required this.byDay,
    required this.onSelect,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final daysInMonth = DateTime(month.year, month.month + 1, 0).day;
    var firstWeekday = DateTime(month.year, month.month).weekday - 1; // 0 = lundi
    if (firstWeekday == -1) firstWeekday = 6;
    final now = DateTime.now();
    final textColor = isDark ? AppColors.textDark : AppColors.textLight;

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 7,
        childAspectRatio: 1,
        mainAxisSpacing: 2,
        crossAxisSpacing: 2,
      ),
      itemCount: 42,
      itemBuilder: (context, index) {
        final dayIndex = index - firstWeekday;
        if (dayIndex < 0 || dayIndex >= daysInMonth) {
          return const SizedBox.shrink();
        }

        final day = dayIndex + 1;
        final date = DateTime(month.year, month.month, day);
        final isSelected = selected.year == date.year &&
            selected.month == date.month &&
            selected.day == date.day;
        final isToday = now.year == date.year && now.month == date.month && now.day == date.day;
        final hasEvents = (byDay[_dateKey(date)] ?? const []).isNotEmpty;

        return GestureDetector(
          onTap: () => onSelect(date),
          child: Container(
            margin: const EdgeInsets.all(2),
            decoration: BoxDecoration(
              color: isSelected ? AppColors.primary : Colors.transparent,
              borderRadius: BorderRadius.circular(10),
              border: isToday && !isSelected
                  ? Border.all(color: AppColors.primary, width: 1.4)
                  : null,
            ),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  '$day',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: isToday || isSelected ? FontWeight.w700 : FontWeight.w400,
                    color: isSelected
                        ? Colors.white
                        : isToday
                            ? AppColors.primary
                            : textColor,
                  ),
                ),
                const SizedBox(height: 3),
                Container(
                  width: 5,
                  height: 5,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: hasEvents
                        ? (isSelected ? Colors.white : AppColors.primary)
                        : Colors.transparent,
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
