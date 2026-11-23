import 'dart:async';
import 'dart:math';

import 'package:material_ui/material_ui.dart';

import '../monetization.dart';
import 'ad_config.dart';
import 'ad_gateway.dart';

/// Porte de consentement (implémentée par [ConsentManager] ; simulée en test).
abstract class ConsentGate {
  Future<void> gatherConsent({List<String> testDeviceIds});
  Future<bool> canRequestAds();
  Future<bool> privacyOptionsRequired();
  Future<void> showPrivacyOptions();
}

/// Service publicitaire AdMob réutilisable.
///
/// Garanties :
/// * **Consentement d'abord** : rien n'est chargé tant que l'UMP n'autorise
///   pas les requêtes publicitaires.
/// * **Préchargement** d'une récompensée et d'un interstitiel, rechargés après
///   chaque affichage ; nouvelle tentative avec délai croissant en cas d'échec ;
///   annonces de plus de 55 min jetées (elles expirent au bout d'une heure).
/// * **Un seul affichage plein écran à la fois** (verrou partagé) : un double
///   appui ne peut pas lancer deux annonces.
/// * **Récompense validée uniquement** : `showRewarded` ne renvoie `true` que si
///   le SDK a appelé `onUserEarnedReward`, et seulement après la fermeture de
///   l'annonce (le joueur revient au jeu avant de recevoir sa récompense).
/// * **Pas de fuite mémoire** : chaque annonce est libérée à sa fermeture ou en
///   cas d'échec ; `dispose()` libère celles encore en cache.
/// * **Interstitiels plafonnés** (voir [AdConfig]) et jamais pendant une partie.
class AdMobAdsService extends AdsService {
  final AdGateway gateway;
  final ConsentGate consent;
  final AdUnitIds ids;

  /// Nombre de niveaux terminés (aucun interstitiel pour les débutants).
  final int Function() levelsCompleted;

  /// Appelés autour d'un affichage plein écran (ex. couper la musique).
  final VoidCallback? onFullScreenOpened;
  final VoidCallback? onFullScreenClosed;

  final DateTime Function() clock;

  AdMobAdsService({
    required this.gateway,
    required this.consent,
    required this.ids,
    required this.levelsCompleted,
    this.onFullScreenOpened,
    this.onFullScreenClosed,
    DateTime Function()? clock,
  }) : clock = clock ?? DateTime.now;

  bool _ready = false;
  bool _disposed = false;
  bool _removed = false;
  bool _showing = false;

  _Slot? _rewarded;
  _Slot? _interstitial;
  bool _loadingRewarded = false;
  bool _loadingInterstitial = false;
  int _rewardedFailures = 0;
  int _interstitialFailures = 0;
  Timer? _rewardedRetry;
  Timer? _interstitialRetry;

  int _levelEnds = 0;
  DateTime? _lastInterstitial;
  DateTime? _lastRewarded;

  /// Pour diagnostic / tests.
  bool get isShowing => _showing;
  bool get canServeAds => _ready;

  @override
  Future<void> init() async {
    final devices = AdConfig.testDeviceIds.isEmpty
        ? const <String>[]
        : AdConfig.testDeviceIds.split(',').map((e) => e.trim()).toList();
    await consent.gatherConsent(testDeviceIds: devices);
    if (!await consent.canRequestAds()) {
      debugPrint('AdMob : consentement non obtenu, aucune annonce ne sera demandée.');
      return;
    }
    await _startSdk(devices);
  }

  Future<void> _startSdk(List<String> devices) async {
    if (_ready || _disposed) return;
    await gateway.initialize(testDeviceIds: devices);
    _ready = true;
    if (AdConfig.releaseWithTestIds) {
      debugPrint('ATTENTION : version release avec identifiants AdMob de TEST.');
    }
    _loadRewarded();
    _loadInterstitial();
    notifyListeners();
  }

  // --- Chargement ---------------------------------------------------------------

  Duration _backoff(int failures) =>
      Duration(seconds: min(AdConfig.maxRetryDelay.inSeconds, 5 * pow(2, failures - 1).toInt()));

  void _loadRewarded() {
    if (!_ready || _disposed || _loadingRewarded || _rewarded != null) return;
    _loadingRewarded = true;
    gateway.loadRewarded(
      ids.rewarded,
      onLoaded: (ad) {
        _loadingRewarded = false;
        if (_disposed) return ad.dispose();
        _rewardedFailures = 0;
        _rewarded = _Slot(ad, clock());
        notifyListeners();
      },
      onFailed: (e) {
        _loadingRewarded = false;
        _rewardedFailures++;
        debugPrint('AdMob récompensée non chargée ($e), nouvel essai dans ${_backoff(_rewardedFailures)}');
        _rewardedRetry?.cancel();
        if (!_disposed) _rewardedRetry = Timer(_backoff(_rewardedFailures), _loadRewarded);
      },
    );
  }

  void _loadInterstitial() {
    if (!_ready || _disposed || _removed || _loadingInterstitial || _interstitial != null) return;
    _loadingInterstitial = true;
    gateway.loadInterstitial(
      ids.interstitial,
      onLoaded: (ad) {
        _loadingInterstitial = false;
        if (_disposed || _removed) return ad.dispose();
        _interstitialFailures = 0;
        _interstitial = _Slot(ad, clock());
      },
      onFailed: (e) {
        _loadingInterstitial = false;
        _interstitialFailures++;
        _interstitialRetry?.cancel();
        if (!_disposed) _interstitialRetry = Timer(_backoff(_interstitialFailures), _loadInterstitial);
      },
    );
  }

  /// Retire une annonce du cache si elle a expiré.
  _Slot? _fresh(_Slot? s, void Function() clear, void Function() reload) {
    if (s == null) return null;
    if (clock().difference(s.loadedAt) > const Duration(minutes: 55)) {
      s.ad.dispose();
      clear();
      reload();
      return null;
    }
    return s;
  }

  // --- Récompensées ---------------------------------------------------------------

  @override
  bool get rewardedAvailable => _ready && _rewarded != null && !_showing;

  @override
  Future<bool> showRewarded(String placement) async {
    if (!_ready || _showing) return false;
    final slot = _fresh(_rewarded, () => _rewarded = null, _loadRewarded);
    if (slot == null) {
      _loadRewarded();
      return false;
    }
    _rewarded = null; // une annonce ne s'affiche qu'une fois
    _showing = true;
    notifyListeners();

    var earned = false;
    final result = Completer<bool>();
    void finish(bool ok) {
      if (result.isCompleted) return;
      slot.ad.dispose();
      _showing = false;
      onFullScreenClosed?.call();
      if (ok) _lastRewarded = clock();
      _loadRewarded();
      notifyListeners();
      result.complete(ok);
    }

    slot.ad.show(
      onShown: () => onFullScreenOpened?.call(),
      onUserEarnedReward: () => earned = true,
      onDismissed: () => finish(earned),
      onFailedToShow: (e) {
        debugPrint('AdMob récompensée non affichée ($placement) : $e');
        finish(false);
      },
    );
    return result.future;
  }

  // --- Interstitiels ----------------------------------------------------------------

  /// Les conditions de fréquence sont-elles réunies (sans compter cet appel) ?
  bool _interstitialAllowed() {
    if (_removed || !_ready || _showing) return false;
    if (levelsCompleted() < AdConfig.interstitialMinLevelsCompleted) return false;
    if (_levelEnds % AdConfig.interstitialEveryNLevelEnds != 0) return false;
    final now = clock();
    if (_lastInterstitial != null && now.difference(_lastInterstitial!) < AdConfig.interstitialMinInterval) {
      return false;
    }
    if (_lastRewarded != null && now.difference(_lastRewarded!) < AdConfig.interstitialAfterRewardedGrace) {
      return false;
    }
    return true;
  }

  @override
  Future<void> maybeShowInterstitial(String placement) async {
    _levelEnds++;
    if (!_interstitialAllowed()) return;
    final slot = _fresh(_interstitial, () => _interstitial = null, _loadInterstitial);
    if (slot == null) {
      _loadInterstitial();
      return;
    }
    _interstitial = null;
    _showing = true;
    final done = Completer<void>();
    void finish() {
      if (done.isCompleted) return;
      slot.ad.dispose();
      _showing = false;
      onFullScreenClosed?.call();
      _loadInterstitial();
      notifyListeners();
      done.complete();
    }

    slot.ad.show(
      onShown: () {
        _lastInterstitial = clock();
        onFullScreenOpened?.call();
      },
      onDismissed: finish,
      onFailedToShow: (e) {
        debugPrint('AdMob interstitiel non affiché ($placement) : $e');
        finish();
      },
    );
    return done.future;
  }

  // --- Bannières ----------------------------------------------------------------------

  @override
  Widget banner(String placement) {
    if (!_ready || _removed || !AdConfig.bannersEnabled) return const SizedBox.shrink();
    return gateway.banner(ids.banner);
  }

  @override
  set adsRemoved(bool v) {
    _removed = v;
    if (v) {
      _interstitial?.ad.dispose();
      _interstitial = null;
      _interstitialRetry?.cancel();
    } else {
      _loadInterstitial();
    }
    notifyListeners();
  }

  // --- Confidentialité -------------------------------------------------------------------

  @override
  Future<bool> privacyOptionsRequired() => consent.privacyOptionsRequired();

  @override
  Future<void> showPrivacyOptions() async {
    await consent.showPrivacyOptions();
    // Le joueur a pu accepter les annonces depuis ce formulaire.
    if (!_ready && await consent.canRequestAds()) await _startSdk(const []);
  }

  @override
  void dispose() {
    _disposed = true;
    _rewardedRetry?.cancel();
    _interstitialRetry?.cancel();
    _rewarded?.ad.dispose();
    _interstitial?.ad.dispose();
    _rewarded = null;
    _interstitial = null;
    super.dispose();
  }
}

class _Slot {
  final LoadedFullScreenAd ad;
  final DateTime loadedAt;
  _Slot(this.ad, this.loadedAt);
}
