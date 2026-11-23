import 'package:google_mobile_ads/google_mobile_ads.dart';
import 'package:material_ui/material_ui.dart';

import 'ad_gateway.dart';

/// Implémentation de [AdGateway] avec le plugin officiel `google_mobile_ads`.
class GoogleAdGateway implements AdGateway {
  @override
  Future<void> initialize({List<String> testDeviceIds = const []}) async {
    await MobileAds.instance.updateRequestConfiguration(RequestConfiguration(
      testDeviceIds: testDeviceIds,
      // Jeu tout public mais non destiné en priorité aux enfants : à revoir si
      // l'audience cible déclarée sur Google Play inclut les moins de 13 ans.
      ageRestrictedTreatment: AgeRestrictedTreatment.unspecified,
      maxAdContentRating: MaxAdContentRating.pg,
    ));
    await MobileAds.instance.initialize();
  }

  @override
  void loadRewarded(String unitId,
      {required void Function(LoadedFullScreenAd ad) onLoaded, required void Function(String error) onFailed}) {
    RewardedAd.load(
      adUnitId: unitId,
      request: const AdRequest(),
      rewardedAdLoadCallback: RewardedAdLoadCallback(
        onAdLoaded: (ad) => onLoaded(_RewardedHandle(ad)),
        onAdFailedToLoad: (e) => onFailed('${e.code} ${e.message}'),
      ),
    );
  }

  @override
  void loadInterstitial(String unitId,
      {required void Function(LoadedFullScreenAd ad) onLoaded, required void Function(String error) onFailed}) {
    InterstitialAd.load(
      adUnitId: unitId,
      request: const AdRequest(),
      adLoadCallback: InterstitialAdLoadCallback(
        onAdLoaded: (ad) => onLoaded(_InterstitialHandle(ad)),
        onAdFailedToLoad: (e) => onFailed('${e.code} ${e.message}'),
      ),
    );
  }

  @override
  Widget banner(String unitId) => AdaptiveBanner(unitId: unitId);
}

class _RewardedHandle implements LoadedFullScreenAd {
  final RewardedAd _ad;
  bool _disposed = false;
  _RewardedHandle(this._ad);

  @override
  void show({
    required void Function() onShown,
    required void Function() onDismissed,
    required void Function(String error) onFailedToShow,
    void Function()? onUserEarnedReward,
  }) {
    _ad.fullScreenContentCallback = FullScreenContentCallback(
      onAdShowedFullScreenContent: (_) => onShown(),
      onAdDismissedFullScreenContent: (_) => onDismissed(),
      onAdFailedToShowFullScreenContent: (_, e) => onFailedToShow('${e.code} ${e.message}'),
    );
    _ad.show(onUserEarnedReward: (_, _) => onUserEarnedReward?.call());
  }

  @override
  void dispose() {
    if (_disposed) return;
    _disposed = true;
    _ad.dispose();
  }
}

class _InterstitialHandle implements LoadedFullScreenAd {
  final InterstitialAd _ad;
  bool _disposed = false;
  _InterstitialHandle(this._ad);

  @override
  void show({
    required void Function() onShown,
    required void Function() onDismissed,
    required void Function(String error) onFailedToShow,
    void Function()? onUserEarnedReward,
  }) {
    _ad.fullScreenContentCallback = FullScreenContentCallback(
      onAdShowedFullScreenContent: (_) => onShown(),
      onAdDismissedFullScreenContent: (_) => onDismissed(),
      onAdFailedToShowFullScreenContent: (_, e) => onFailedToShow('${e.code} ${e.message}'),
    );
    _ad.show();
  }

  @override
  void dispose() {
    if (_disposed) return;
    _disposed = true;
    _ad.dispose();
  }
}

/// Bannière adaptative ancrée (largeur de l'écran). Ne prend aucune place tant
/// qu'aucune annonce n'est chargée ; libère l'annonce quand elle quitte l'écran.
class AdaptiveBanner extends StatefulWidget {
  final String unitId;
  const AdaptiveBanner({super.key, required this.unitId});

  @override
  State<AdaptiveBanner> createState() => _AdaptiveBannerState();
}

class _AdaptiveBannerState extends State<AdaptiveBanner> {
  BannerAd? _ad;
  bool _loaded = false;
  int? _width;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final width = MediaQuery.sizeOf(context).width.truncate();
    if (width != _width) {
      _width = width;
      _load(width);
    }
  }

  Future<void> _load(int width) async {
    final size = await AdSize.getLargeAnchoredAdaptiveBannerAdSize(width);
    if (!mounted || size == null) return;
    await _ad?.dispose();
    _loaded = false;
    final ad = BannerAd(
      adUnitId: widget.unitId,
      size: size,
      request: const AdRequest(),
      listener: BannerAdListener(
        onAdLoaded: (_) {
          if (mounted) setState(() => _loaded = true);
        },
        onAdFailedToLoad: (ad, e) {
          ad.dispose();
          if (mounted) setState(() => _ad = null);
        },
      ),
    );
    _ad = ad;
    await ad.load();
  }

  @override
  void dispose() {
    _ad?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final ad = _ad;
    if (ad == null || !_loaded) return const SizedBox.shrink();
    return SafeArea(
      top: false,
      child: SizedBox(width: ad.size.width.toDouble(), height: ad.size.height.toDouble(), child: AdWidget(ad: ad)),
    );
  }
}
