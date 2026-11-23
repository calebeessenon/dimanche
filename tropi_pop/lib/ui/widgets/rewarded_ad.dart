import 'package:material_ui/material_ui.dart';

import '../../core/l10n/strings.dart';
import '../../services/app_services.dart';

/// Affiche une publicité récompensée et retourne `true` seulement si la
/// récompense a été validée par la régie. Gère le plafond quotidien
/// ([capKind], voir Economy.adDailyCaps) et informe le joueur si aucune
/// annonce n'est disponible ou si elle a été interrompue.
///
/// L'appelant accorde la récompense UNIQUEMENT si le résultat est `true`.
Future<bool> watchRewardedAd(BuildContext context, {required String placement, String? capKind}) async {
  final s = AppServices.read(context);
  final messenger = ScaffoldMessenger.maybeOf(context);
  void say(String key) => messenger?.showSnackBar(SnackBar(content: Text(tr(key))));

  if (capKind != null && s.progress.adRewardsLeft(capKind) <= 0) return false;
  if (!s.ads.rewardedAvailable) {
    say('no_ad_available');
    return false;
  }
  final ok = await s.ads.showRewarded(placement);
  if (!ok) {
    say('ad_not_completed');
    return false;
  }
  if (capKind != null) await s.progress.countAdReward(capKind);
  return true;
}
