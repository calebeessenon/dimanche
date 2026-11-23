import 'dart:async';

import 'package:material_ui/material_ui.dart';

import '../../core/l10n/strings.dart';
import '../../core/theme/app_theme.dart';
import '../../services/app_services.dart';
import '../screens/shop_screen.dart';
import 'common.dart';

String formatDuration(Duration d) =>
    '${d.inMinutes.remainder(60).toString().padLeft(2, '0')}:${d.inSeconds.remainder(60).toString().padLeft(2, '0')}';

/// Barre du haut : vies (avec minuteur de recharge) et pièces.
class TopBar extends StatefulWidget {
  final Widget? leading;
  const TopBar({super.key, this.leading});

  @override
  State<TopBar> createState() => _TopBarState();
}

class _TopBarState extends State<TopBar> {
  late final Timer _timer;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) => setState(() {}));
  }

  @override
  void dispose() {
    _timer.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final p = AppServices.of(context).progress;
    return ListenableBuilder(
      listenable: p,
      builder: (context, _) {
        final next = p.nextLifeIn;
        return Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          child: Row(children: [
            if (widget.leading != null) ...[widget.leading!, const SizedBox(width: 8)],
            StatPill(
              icon: Icons.favorite_rounded,
              iconColor: AppColors.pink,
              value: '${p.lives}',
              sub: next == null ? tr('full') : formatDuration(next),
              onPlus: () => openShop(context),
            ),
            const Spacer(),
            StatPill(
              icon: Icons.monetization_on_rounded,
              iconColor: AppColors.mango,
              value: '${p.coins}',
              onPlus: () => openShop(context),
            ),
          ]),
        );
      },
    );
  }
}

