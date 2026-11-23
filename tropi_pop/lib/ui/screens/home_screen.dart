import 'package:material_ui/material_ui.dart';

import '../../core/l10n/strings.dart';
import '../../core/theme/app_theme.dart';
import '../../game/levels/level_catalog.dart';
import '../../services/app_services.dart';
import '../widgets/common.dart';
import '../widgets/logo.dart';
import '../widgets/top_bar.dart';
import 'daily_reward_dialog.dart';
import 'level_start_dialog.dart';
import 'map_screen.dart';
import 'settings_screen.dart';
import 'shop_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  bool _dailyShown = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_dailyShown) {
      _dailyShown = true;
      final p = AppServices.of(context).progress;
      if (p.canClaimDaily) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) showDailyReward(context);
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = AppServices.of(context);
    final p = s.progress;
    return Scaffold(
      body: SunsetBackground(
        child: SafeArea(
          child: ListenableBuilder(
            listenable: p,
            builder: (context, _) {
              final current = p.data.unlockedLevel.clamp(1, LevelCatalog.count);
              final world = LevelCatalog.worldOf(current);
              return Column(
                children: [
                  const TopBar(),
                  const Spacer(flex: 2),
                  const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 24),
                    child: FittedBox(fit: BoxFit.scaleDown, child: TropiLogo(size: 72)),
                  ),
                  const Spacer(),
                  Text(tr('world_$world'), style: AppTheme.title(18, color: Colors.white70)),
                  Text(tr('level', {'n': current}), style: AppTheme.title(30)),
                  const SizedBox(height: 20),
                  PopButton(
                    label: tr('play'),
                    icon: Icons.play_arrow_rounded,
                    fontSize: 30,
                    padding: const EdgeInsets.symmetric(horizontal: 56, vertical: 16),
                    onTap: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => const MapScreen())),
                  ),
                  const SizedBox(height: 14),
                  PopButton(
                    label: tr('continue'),
                    color: AppColors.lagoon,
                    icon: Icons.fast_forward_rounded,
                    onTap: () => showLevelStart(context, current),
                  ),
                  const Spacer(flex: 2),
                  Padding(
                    padding: const EdgeInsets.only(bottom: 16, left: 8, right: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                      children: [
                        _RoundAction(
                          icon: Icons.storefront_rounded,
                          label: tr('shop'),
                          color: AppColors.mango,
                          onTap: () => openShop(context),
                        ),
                        _RoundAction(
                          icon: Icons.card_giftcard_rounded,
                          label: tr('reward'),
                          color: AppColors.pink,
                          badge: p.canClaimDaily,
                          onTap: () => showDailyReward(context),
                        ),
                        _RoundAction(
                          icon: Icons.map_rounded,
                          label: tr('map'),
                          color: AppColors.lagoon,
                          onTap: () {
                            Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => const MapScreen()));
                          },
                        ),
                        _RoundAction(
                          icon: Icons.person_rounded,
                          label: tr('profile'),
                          color: AppColors.coral,
                          onTap: () => _showProfile(context),
                        ),
                        _RoundAction(
                          icon: Icons.settings_rounded,
                          label: tr('settings'),
                          color: AppColors.plum,
                          onTap: () {
                            Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => const SettingsScreen()));
                          },
                        ),
                      ],
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }

  void _showProfile(BuildContext context) {
    final p = AppServices.of(context).progress;
    showPopDialog<void>(
      context,
      (ctx) => PopPanel(
        title: tr('profile'),
        onClose: () => Navigator.pop(ctx),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.face_retouching_natural, size: 64, color: AppColors.mango),
            const SizedBox(height: 12),
            _statLine(Icons.star_rounded, tr('stars_total'), '${p.data.totalStars} / ${LevelCatalog.count * 3}'),
            _statLine(Icons.flag_rounded, tr('levels_done'), '${p.data.levelsCompleted} / ${LevelCatalog.count}'),
            _statLine(Icons.monetization_on_rounded, tr('coins'), '${p.coins}'),
          ],
        ),
      ),
    );
  }

  Widget _statLine(IconData i, String label, String value) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 6),
    child: Row(
      children: [
        Icon(i, color: AppColors.mango),
        const SizedBox(width: 10),
        Expanded(child: Text(label, style: AppTheme.title(16))),
        Text(value, style: AppTheme.title(18, color: AppColors.mango)),
      ],
    ),
  );
}

class _RoundAction extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;
  final bool badge;
  const _RoundAction({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
    this.badge = false,
  });

  @override
  Widget build(BuildContext context) => Expanded(
    child: GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () {
        AppServices.of(context).audio.play('tap');
        onTap();
      },
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Stack(
            clipBehavior: Clip.none,
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(colors: [Color.lerp(color, Colors.white, 0.3)!, color]),
                  border: Border.all(color: Colors.white, width: 3),
                  boxShadow: const [BoxShadow(color: Color(0x55000000), offset: Offset(0, 4), blurRadius: 4)],
                ),
                child: Icon(icon, color: Colors.white, size: 30),
              ),
              if (badge)
                Positioned(
                  right: -2,
                  top: -2,
                  child: Container(
                    width: 18,
                    height: 18,
                    decoration: BoxDecoration(
                      color: Colors.red,
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 2),
                    ),
                    child: const Center(
                      child: Text('!', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 4),
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Text(label, style: AppTheme.title(12), maxLines: 1),
          ),
        ],
      ),
    ),
  );
}
