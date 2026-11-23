import 'dart:async';

import 'package:material_ui/material_ui.dart';

import '../../core/l10n/strings.dart';
import '../../core/theme/app_theme.dart';
import '../../data/economy.dart';
import '../../data/progress_store.dart';
import '../../game/controller/game_controller.dart';
import '../../game/levels/level_catalog.dart';
import '../../game/widgets/board_view.dart';
import '../../services/app_services.dart';
import '../widgets/common.dart';
import '../widgets/objective_chip.dart';
import '../widgets/rewarded_ad.dart';
import 'level_start_dialog.dart';

class GameScreen extends StatefulWidget {
  final int levelNumber;
  final int? seed;
  const GameScreen({super.key, required this.levelNumber, this.seed});

  @override
  State<GameScreen> createState() => _GameScreenState();
}

class _GameScreenState extends State<GameScreen> {
  GameController? _c;
  bool _adContinueUsed = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_c != null) return;
    final s = AppServices.of(context);
    _c = GameController(LevelCatalog.level(widget.levelNumber), audio: s.audio, seed: widget.seed)
      ..consumeBooster = s.progress.useBooster
      ..onGameOver = _onGameOver;
  }

  GameController get c => _c!;

  @override
  void dispose() {
    _c?.dispose();
    super.dispose();
  }

  void _onGameOver(Phase result) {
    if (!mounted) return;
    if (result == Phase.won) {
      _showWin();
    } else {
      _showLose();
    }
  }

  Future<void> _showWin() async {
    final s = AppServices.of(context);
    final e = c.engine;
    final reward = await s.progress.completeLevel(widget.levelNumber, e.score, e.stars);
    unawaited(s.cloud?.submitLevelScore(widget.levelNumber, e.score, e.stars));
    if (!mounted) return;
    await Future<void>.delayed(const Duration(milliseconds: 300));
    if (!mounted) return;
    final next = widget.levelNumber + 1;
    final action = await showPopDialog<String>(
      context,
      dismissible: false,
      (ctx) => _WinPanel(score: e.score, stars: e.stars, reward: reward, hasNext: next <= LevelCatalog.count),
    );
    if (!mounted) return;
    await s.ads.maybeShowInterstitial('level_end');
    if (!mounted) return;
    final nav = Navigator.of(context);
    nav.pop();
    if (action == 'next' && next <= LevelCatalog.count) {
      unawaited(showLevelStart(nav.context, next));
    } else if (action == 'retry') {
      unawaited(showLevelStart(nav.context, widget.levelNumber));
    }
  }

  Future<void> _showLose() async {
    final s = AppServices.of(context);
    final action = await showPopDialog<String>(
      context,
      dismissible: false,
      (ctx) => PopPanel(
        title: tr('lose_title'),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Wrap(
              spacing: 14,
              children: [for (final o in c.level.objectives) ObjectiveChip(o: o, progress: c.engine.progressOf(o))],
            ),
            const SizedBox(height: 16),
            if (s.progress.booster('moves') > 0)
              PopButton(
                label: '${tr('extra_moves')} (×${s.progress.booster('moves')})',
                color: AppColors.lagoon,
                onTap: () => Navigator.pop(ctx, 'booster'),
              )
            else
              PopButton(
                label: '${tr('extra_moves')}  ${Economy.extraMovesCost} 🪙',
                color: AppColors.lagoon,
                onTap: s.progress.coins >= Economy.extraMovesCost ? () => Navigator.pop(ctx, 'coins') : null,
              ),
            const SizedBox(height: 10),
            if (!_adContinueUsed && s.ads.rewardedAvailable)
              PopButton(
                label: '${tr('extra_moves')} · ${tr('watch_ad')}',
                icon: Icons.ondemand_video_rounded,
                color: AppColors.mango,
                fontSize: 17,
                onTap: () => Navigator.pop(ctx, 'ad'),
              ),
            const SizedBox(height: 10),
            PopButton(label: tr('quit'), color: AppColors.coral, onTap: () => Navigator.pop(ctx, 'quit')),
          ],
        ),
      ),
    );
    if (!mounted) return;
    var granted = false;
    switch (action) {
      case 'booster':
        granted = await s.progress.useBooster('moves');
      case 'coins':
        granted = await s.progress.spendCoins(Economy.extraMovesCost);
      case 'ad':
        // +5 coups uniquement si la pub récompensée a été validée.
        granted = await watchRewardedAd(context, placement: 'continue');
        if (granted) _adContinueUsed = true;
    }
    if (!mounted) return;
    if (granted) {
      c.continueWithMoves(Economy.extraMoves);
    } else if (action != 'quit') {
      // Paiement / pub non abouti : on repropose les options.
      await _showLose();
    } else {
      // Fin de partie : transition naturelle pour un interstitiel (plafonné).
      await s.ads.maybeShowInterstitial('level_lost');
      if (mounted) Navigator.of(context).pop();
    }
  }

  Future<void> _pause() async {
    final quit = await showPopDialog<bool>(
      context,
      (ctx) => PopPanel(
        title: tr('pause'),
        onClose: () => Navigator.pop(ctx, false),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            PopButton(label: tr('resume'), color: AppColors.lagoon, onTap: () => Navigator.pop(ctx, false)),
            const SizedBox(height: 12),
            PopButton(label: tr('quit'), color: AppColors.coral, onTap: () => Navigator.pop(ctx, true)),
          ],
        ),
      ),
    );
    if (quit == true && mounted) Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) {
    final s = AppServices.of(context);
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _pause();
      },
      child: Scaffold(
        body: SunsetBackground(
          child: SafeArea(
            child: ListenableBuilder(
              listenable: Listenable.merge([c, s.progress]),
              builder: (context, _) => Column(
                children: [
                  _Hud(c: c, onPause: _pause),
                  Expanded(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                      child: Center(child: BoardView(controller: c)),
                    ),
                  ),
                  if (c.hammerMode)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 6),
                      child: Text(tr('hammer_hint'), style: AppTheme.title(16, color: AppColors.mango)),
                    ),
                  _BoosterBar(c: c),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _Hud extends StatelessWidget {
  final GameController c;
  final VoidCallback onPause;
  const _Hud({required this.c, required this.onPause});

  @override
  Widget build(BuildContext context) {
    final e = c.engine;
    final l = c.level;
    final starMax = l.starScores[2];
    final progress = (e.score / starMax).clamp(0.0, 1.0);
    return Padding(
      padding: const EdgeInsets.fromLTRB(10, 8, 10, 4),
      child: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: const Color(0x88000000),
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: Colors.white24, width: 2),
        ),
        child: Row(
          children: [
            IconButton(
              onPressed: onPause,
              icon: const Icon(Icons.pause_circle_filled_rounded, size: 36, color: Colors.white),
            ),
            // Coups restants.
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: const RadialGradient(colors: [Color(0xFFFF8FB3), AppColors.pink]),
                border: Border.all(color: Colors.white, width: 3),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text('${e.movesLeft}', style: AppTheme.title(24)),
                  Text(tr('moves'), style: AppTheme.title(10)),
                ],
              ),
            ),
            const SizedBox(width: 10),
            // Score + jauge d'étoiles.
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(tr('level', {'n': l.number}), style: AppTheme.title(13, color: Colors.white70)),
                  Text('${e.score}', style: AppTheme.title(22)),
                  const SizedBox(height: 4),
                  LayoutBuilder(
                    builder: (context, box) {
                      final w = box.maxWidth;
                      return SizedBox(
                        height: 18,
                        child: Stack(
                          clipBehavior: Clip.none,
                          children: [
                            Container(
                              height: 12,
                              margin: const EdgeInsets.only(top: 3),
                              decoration: BoxDecoration(color: Colors.black38, borderRadius: BorderRadius.circular(6)),
                            ),
                            AnimatedContainer(
                              duration: const Duration(milliseconds: 300),
                              height: 12,
                              width: w * progress,
                              margin: const EdgeInsets.only(top: 3),
                              decoration: BoxDecoration(
                                gradient: const LinearGradient(colors: [AppColors.mango, AppColors.pink]),
                                borderRadius: BorderRadius.circular(6),
                              ),
                            ),
                            for (var i = 0; i < 3; i++)
                              Positioned(
                                left: (w * l.starScores[i] / starMax - 9).clamp(0, w - 18),
                                top: 0,
                                child: Icon(
                                  Icons.star_rounded,
                                  size: 18,
                                  color: e.score >= l.starScores[i] ? AppColors.mango : Colors.white38,
                                ),
                              ),
                          ],
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            // Objectifs.
            Wrap(
              spacing: 8,
              children: [for (final o in l.objectives) ObjectiveChip(o: o, progress: e.progressOf(o))],
            ),
          ],
        ),
      ),
    );
  }
}

class _BoosterBar extends StatelessWidget {
  final GameController c;
  const _BoosterBar({required this.c});

  @override
  Widget build(BuildContext context) {
    final p = AppServices.of(context).progress;
    Widget btn(IconData icon, String id, String label, VoidCallback onTap, {bool active = false}) {
      final n = p.booster(id);
      return GestureDetector(
        onTap: c.canPlay && n > 0 ? onTap : null,
        child: Opacity(
          opacity: n > 0 ? 1 : 0.5,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Stack(
                clipBehavior: Clip.none,
                children: [
                  Container(
                    width: 58,
                    height: 58,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: active ? AppColors.mango : const Color(0xAA4A2380),
                      border: Border.all(color: Colors.white, width: 3),
                    ),
                    child: Icon(icon, color: Colors.white, size: 30),
                  ),
                  Positioned(
                    right: -4,
                    bottom: -2,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 1),
                      decoration: BoxDecoration(color: AppColors.lagoon, borderRadius: BorderRadius.circular(10)),
                      child: Text('$n', style: AppTheme.title(13)),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 2),
              Text(label, style: AppTheme.title(12)),
            ],
          ),
        ),
      );
    }

    return Padding(
      padding: const EdgeInsets.only(bottom: 10, top: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          btn(Icons.gavel_rounded, 'hammer', tr('hammer'), c.toggleHammer, active: c.hammerMode),
          btn(Icons.shuffle_rounded, 'shuffle', tr('shuffle'), c.useShuffle),
        ],
      ),
    );
  }
}

class _WinPanel extends StatefulWidget {
  final int score;
  final int stars;
  final LevelReward reward;
  final bool hasNext;
  const _WinPanel({required this.score, required this.stars, required this.reward, required this.hasNext});

  @override
  State<_WinPanel> createState() => _WinPanelState();
}

class _WinPanelState extends State<_WinPanel> with SingleTickerProviderStateMixin {
  late final AnimationController _a = AnimationController(vsync: this, duration: const Duration(milliseconds: 1400))
    ..forward();

  @override
  void initState() {
    super.initState();
    final audio = AppServices.read(context).audio;
    for (var i = 0; i < widget.stars; i++) {
      Future<void>.delayed(Duration(milliseconds: 300 + i * 350), () => audio.play('star'));
    }
  }

  @override
  void dispose() {
    _a.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => PopPanel(
    title: tr('win_title'),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        AnimatedBuilder(
          animation: _a,
          builder: (context, _) => Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              for (var i = 0; i < 3; i++)
                Transform.scale(
                  scale: Curves.elasticOut.transform(((_a.value * 1.4 - 0.2 - i * 0.25)).clamp(0.0, 1.0)),
                  child: Icon(
                    Icons.star_rounded,
                    size: i == 1 ? 76 : 60,
                    color: i < widget.stars ? AppColors.mango : Colors.black38,
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 6),
        Text('${tr('score')} : ${widget.score}', style: AppTheme.title(22)),
        const SizedBox(height: 8),
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.monetization_on_rounded, color: AppColors.mango),
            const SizedBox(width: 6),
            Text('+${widget.reward.coins}', style: AppTheme.title(20, color: AppColors.mango)),
          ],
        ),
        if (widget.reward.milestone) ...[
          const SizedBox(height: 6),
          Text('🎁 ${tr('milestone')}', style: AppTheme.title(16, color: AppColors.lagoon)),
        ],
        const SizedBox(height: 18),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            PopButton(
              label: tr('retry'),
              color: AppColors.coral,
              fontSize: 18,
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
              onTap: () => Navigator.pop(context, 'retry'),
            ),
            const SizedBox(width: 10),
            if (widget.hasNext)
              PopButton(label: tr('next'), color: AppColors.lagoon, onTap: () => Navigator.pop(context, 'next'))
            else
              PopButton(label: tr('ok'), color: AppColors.lagoon, onTap: () => Navigator.pop(context, 'map')),
          ],
        ),
      ],
    ),
  );
}
