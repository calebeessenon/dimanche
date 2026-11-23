import 'package:material_ui/material_ui.dart';

import '../../core/theme/app_theme.dart';
import '../../game/levels/level_definition.dart';
import 'logo.dart';

/// Icône représentant un objectif.
class ObjectiveIcon extends StatelessWidget {
  final Objective o;
  final double size;
  const ObjectiveIcon(this.o, {super.key, this.size = 28});

  @override
  Widget build(BuildContext context) => switch (o.type) {
        ObjectiveType.collect => FruitIcon(o.color!, size: size),
        ObjectiveType.score => Icon(Icons.emoji_events_rounded, size: size, color: AppColors.mango),
        ObjectiveType.clearIce => Icon(Icons.ac_unit_rounded, size: size, color: const Color(0xFF9DE7FF)),
        ObjectiveType.clearCrates => Icon(Icons.inventory_2_rounded, size: size, color: const Color(0xFFD9A066)),
      };
}

/// Objectif en cours : icône + restant (ou coche).
class ObjectiveChip extends StatelessWidget {
  final Objective o;
  final int progress;
  const ObjectiveChip({super.key, required this.o, required this.progress});

  @override
  Widget build(BuildContext context) {
    final done = progress >= o.target;
    final remaining = (o.target - progress).clamp(0, o.target);
    return Column(mainAxisSize: MainAxisSize.min, children: [
      Stack(clipBehavior: Clip.none, children: [
        ObjectiveIcon(o, size: 34),
        if (done)
          const Positioned(right: -6, bottom: -6, child: Icon(Icons.check_circle_rounded, color: AppColors.lagoon, size: 20)),
      ]),
      Text(done ? '✓' : (o.type == ObjectiveType.score ? '${o.target}' : '$remaining'), style: AppTheme.title(15)),
    ]);
  }
}
