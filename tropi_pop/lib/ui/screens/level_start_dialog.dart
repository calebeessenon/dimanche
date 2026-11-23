import 'package:material_ui/material_ui.dart';

import '../../core/l10n/strings.dart';
import '../../core/theme/app_theme.dart';
import '../../game/levels/level_catalog.dart';
import '../../game/levels/level_definition.dart';
import '../../services/app_services.dart';
import '../widgets/common.dart';
import '../widgets/objective_chip.dart';
import '../widgets/rewarded_ad.dart';
import 'game_screen.dart';
import 'shop_screen.dart';

/// Fenêtre de lancement d'un niveau : objectifs, étoiles, bouton Jouer.
Future<void> showLevelStart(BuildContext context, int level) => showPopDialog<void>(
      context,
      (ctx) => _LevelStartPanel(level: level, rootContext: context),
    );

class _LevelStartPanel extends StatelessWidget {
  final int level;
  final BuildContext rootContext;
  const _LevelStartPanel({required this.level, required this.rootContext});

  @override
  Widget build(BuildContext context) {
    final s = AppServices.of(context);
    final def = LevelCatalog.level(level);
    final stars = s.progress.starsFor(level);
    return PopPanel(
      title: tr('level', {'n': level}),
      onClose: () => Navigator.pop(context),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        StarRow(stars: stars, size: 40),
        const SizedBox(height: 12),
        Text(tr('objectives'), style: AppTheme.title(18, color: Colors.white70)),
        const SizedBox(height: 8),
        for (final o in def.objectives) Padding(padding: const EdgeInsets.symmetric(vertical: 4), child: _ObjectiveLine(o)),
        const SizedBox(height: 8),
        Text('${def.moves} ${tr('moves').toLowerCase()}', style: AppTheme.title(16, color: AppColors.mango)),
        const SizedBox(height: 18),
        PopButton(
          label: tr('start'),
          icon: Icons.play_arrow_rounded,
          onTap: () async {
            final nav = Navigator.of(context);
            if (!await s.progress.consumeLife()) {
              nav.pop();
              if (rootContext.mounted) showNoLives(rootContext);
              return;
            }
            nav.pop();
            nav.push(MaterialPageRoute<void>(builder: (_) => GameScreen(levelNumber: level)));
          },
        ),
      ]),
    );
  }
}

class _ObjectiveLine extends StatelessWidget {
  final Objective o;
  const _ObjectiveLine(this.o);

  @override
  Widget build(BuildContext context) {
    final text = switch (o.type) {
      ObjectiveType.score => tr('obj_score', {'n': o.target}),
      ObjectiveType.collect => tr('obj_collect', {'n': o.target}),
      ObjectiveType.clearIce => tr('obj_ice'),
      ObjectiveType.clearCrates => tr('obj_crates'),
    };
    return Row(mainAxisSize: MainAxisSize.min, children: [
      ObjectiveIcon(o, size: 34),
      const SizedBox(width: 10),
      Flexible(child: Text(text, style: AppTheme.title(17))),
    ]);
  }
}

/// Plus de vies : proposer pub / boutique.
void showNoLives(BuildContext context) {
  final s = AppServices.of(context);
  showPopDialog<void>(
    context,
    (ctx) => PopPanel(
      title: tr('no_lives'),
      onClose: () => Navigator.pop(ctx),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        const Icon(Icons.heart_broken_rounded, color: AppColors.pink, size: 72),
        const SizedBox(height: 16),
        if (s.progress.adRewardsLeft('life') > 0)
          PopButton(
            label: tr('free_life_ad'),
            icon: Icons.ondemand_video_rounded,
            color: AppColors.lagoon,
            onTap: () async {
              Navigator.pop(ctx);
              // Récompense accordée uniquement si la pub a été validée.
              if (await watchRewardedAd(context, placement: 'free_life', capKind: 'life')) {
                await s.progress.addLives(1);
              }
            },
          ),
        const SizedBox(height: 12),
        PopButton(
          label: tr('refill_lives'),
          icon: Icons.storefront_rounded,
          color: AppColors.mango,
          onTap: () {
            Navigator.pop(ctx);
            openShop(context);
          },
        ),
      ]),
    ),
  );
}
