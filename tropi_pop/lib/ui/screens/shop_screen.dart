import 'package:material_ui/material_ui.dart';

import '../../core/l10n/strings.dart';
import '../../core/theme/app_theme.dart';
import '../../data/economy.dart';
import '../../services/app_services.dart';
import '../../services/monetization.dart';
import '../widgets/common.dart';
import '../widgets/top_bar.dart';

void openShop(BuildContext context) =>
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => const ShopScreen()));

/// Boutique : boosters (pièces), packs de pièces et « Sans pub » (achats
/// intégrés, activés à l'étape 6), pièces gratuites via pub récompensée.
class ShopScreen extends StatelessWidget {
  const ShopScreen({super.key});

  static IconData iconFor(String id) => switch (id) {
        'hammer' => Icons.gavel_rounded,
        'shuffle' => Icons.shuffle_rounded,
        'moves' => Icons.add_circle_rounded,
        'lives' => Icons.favorite_rounded,
        _ => Icons.star_rounded,
      };

  static String labelFor(String id) => switch (id) {
        'hammer' => tr('hammer'),
        'shuffle' => tr('shuffle'),
        'moves' => tr('extra_moves'),
        'lives' => tr('refill_lives'),
        _ => id,
      };

  @override
  Widget build(BuildContext context) {
    final s = AppServices.of(context);
    final p = s.progress;
    return Scaffold(
      body: SunsetBackground(
        child: SafeArea(
          child: Column(children: [
            TopBar(
              leading: GestureDetector(
                onTap: () => Navigator.pop(context),
                child: Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(color: AppColors.pink, shape: BoxShape.circle, border: Border.all(color: Colors.white, width: 3)),
                  child: const Icon(Icons.arrow_back_rounded, color: Colors.white),
                ),
              ),
            ),
            Text(tr('shop'), style: AppTheme.title(32)),
            Expanded(
              child: ListenableBuilder(
                listenable: p,
                builder: (context, _) => ListView(padding: const EdgeInsets.all(16), children: [
                  if (s.ads.rewardedAvailable)
                    _ShopCard(
                      icon: Icons.ondemand_video_rounded,
                      title: '+${Economy.adRewardCoins} 🪙',
                      subtitle: tr('watch_ad'),
                      action: tr('play'),
                      color: AppColors.lagoon,
                      onBuy: () async {
                        if (await s.ads.showRewarded('shop_coins')) {
                          await p.addCoins(Economy.adRewardCoins);
                          s.audio.play('coin');
                        }
                      },
                    ),
                  _section(tr('boosters')),
                  for (final o in Economy.coinOffers)
                    _ShopCard(
                      icon: iconFor(o.itemId),
                      title: '${labelFor(o.itemId)}${o.kind == CoinOfferKind.booster ? ' ×${o.quantity}' : ''}',
                      subtitle: o.kind == CoinOfferKind.booster ? '${tr('boosters')} : ${p.booster(o.itemId)}' : '${tr('lives')} : ${p.lives}',
                      action: '${o.price} 🪙',
                      color: AppColors.mango,
                      onBuy: () async {
                        if (await p.buyWithCoins(o)) {
                          s.audio.play('coin');
                        } else if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(tr('not_enough_coins'))));
                        }
                      },
                    ),
                  _section(tr('coins_packs')),
                  for (final prod in s.purchases.products)
                    _ShopCard(
                      icon: prod.id == Products.noAds
                          ? Icons.block_rounded
                          : prod.id == Products.starterPack
                              ? Icons.redeem_rounded
                              : Icons.monetization_on_rounded,
                      title: switch (prod.id) {
                        Products.noAds => tr('remove_ads'),
                        Products.starterPack => 'Starter pack',
                        _ => '${Products.coinAmounts[prod.id]} 🪙',
                      },
                      subtitle: s.purchases.available ? '' : tr('coming_soon'),
                      action: prod.price,
                      color: AppColors.pink,
                      onBuy: s.purchases.available ? () => s.purchases.buy(prod.id) : null,
                    ),
                ]),
              ),
            ),
          ]),
        ),
      ),
    );
  }

  Widget _section(String t) => Padding(
        padding: const EdgeInsets.fromLTRB(4, 18, 4, 8),
        child: Text(t, style: AppTheme.title(20, color: AppColors.cream)),
      );
}

class _ShopCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final String action;
  final Color color;
  final VoidCallback? onBuy;
  const _ShopCard({required this.icon, required this.title, required this.subtitle, required this.action, required this.color, this.onBuy});

  @override
  Widget build(BuildContext context) => Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: const Color(0x66000000),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.white24, width: 2),
        ),
        child: Row(children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(shape: BoxShape.circle, color: color.withValues(alpha: 0.3)),
            child: Icon(icon, color: color, size: 30),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(title, style: AppTheme.title(18)),
              if (subtitle.isNotEmpty) Text(subtitle, style: AppTheme.title(13, color: Colors.white70)),
            ]),
          ),
          PopButton(
            label: action,
            color: color,
            fontSize: 16,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            onTap: onBuy,
          ),
        ]),
      );
}
