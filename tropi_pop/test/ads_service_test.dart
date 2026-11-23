import 'package:flutter_test/flutter_test.dart';
import 'package:material_ui/material_ui.dart';
import 'package:tropi_pop/services/ads/ad_config.dart';
import 'package:tropi_pop/services/ads/ad_gateway.dart';
import 'package:tropi_pop/services/ads/admob_ads_service.dart';

/// Annonce simulée : le test décide de ce que fait l'utilisateur.
class FakeAd implements LoadedFullScreenAd {
  final String kind;
  bool disposed = false;
  int shows = 0;
  void Function()? dismiss, reward, shown;
  void Function(String)? fail;
  FakeAd(this.kind);

  @override
  void show({required onShown, required onDismissed, required onFailedToShow, onUserEarnedReward}) {
    shows++;
    shown = onShown;
    dismiss = onDismissed;
    fail = onFailedToShow;
    reward = onUserEarnedReward;
    onShown();
  }

  @override
  void dispose() => disposed = true;
}

class FakeGateway implements AdGateway {
  final List<FakeAd> ads = [];
  bool failLoads = false;
  int rewardedLoads = 0, interstitialLoads = 0;
  bool initialized = false;

  @override
  Future<void> initialize({List<String> testDeviceIds = const []}) async => initialized = true;

  void _load(String kind, void Function(LoadedFullScreenAd) ok, void Function(String) ko) {
    if (failLoads) return ko('no fill');
    final ad = FakeAd(kind);
    ads.add(ad);
    ok(ad);
  }

  @override
  void loadRewarded(String unitId, {required onLoaded, required onFailed}) {
    rewardedLoads++;
    _load('rewarded', onLoaded, onFailed);
  }

  @override
  void loadInterstitial(String unitId, {required onLoaded, required onFailed}) {
    interstitialLoads++;
    _load('interstitial', onLoaded, onFailed);
  }

  @override
  Widget banner(String unitId) => const Text('BANNER');

  FakeAd lastOf(String kind) => ads.lastWhere((a) => a.kind == kind);
}

class FakeConsent implements ConsentGate {
  bool allowed;
  FakeConsent({this.allowed = true});
  @override
  Future<void> gatherConsent({List<String> testDeviceIds = const []}) async {}
  @override
  Future<bool> canRequestAds() async => allowed;
  @override
  Future<bool> privacyOptionsRequired() async => true;
  @override
  Future<void> showPrivacyOptions() async => allowed = true;
}

void main() {
  late FakeGateway gw;
  late DateTime now;
  var levels = 10;

  AdMobAdsService make({bool consent = true}) => AdMobAdsService(
        gateway: gw,
        consent: FakeConsent(allowed: consent),
        ids: AdUnitIds.testAndroid,
        levelsCompleted: () => levels,
        clock: () => now,
      );

  setUp(() {
    gw = FakeGateway();
    now = DateTime(2026, 11, 24, 12);
    levels = 10;
  });

  test('identifiants de test officiels Google par défaut', () {
    expect(AdConfig.usingTestIds, isTrue);
    expect(AdUnitIds.testAndroid.rewarded, 'ca-app-pub-3940256099942544/5224354917');
    expect(AdUnitIds.testAndroid.interstitial, 'ca-app-pub-3940256099942544/1033173712');
    expect(AdUnitIds.testIos.rewarded, 'ca-app-pub-3940256099942544/1712485313');
    expect(AdUnitIds.testIos.interstitial, 'ca-app-pub-3940256099942544/4411468910');
  });

  test('sans consentement : SDK non initialisé, aucune annonce demandée', () async {
    final s = make(consent: false);
    await s.init();
    expect(gw.initialized, isFalse);
    expect(gw.rewardedLoads, 0);
    expect(s.rewardedAvailable, isFalse);
    expect(await s.showRewarded('x'), isFalse);
  });

  test('consentement donné plus tard via les options de confidentialité', () async {
    final s = make(consent: false);
    await s.init();
    await s.showPrivacyOptions();
    expect(gw.initialized, isTrue);
    expect(s.rewardedAvailable, isTrue);
  });

  test('préchargement après consentement', () async {
    final s = make();
    await s.init();
    expect(gw.initialized, isTrue);
    expect(s.rewardedAvailable, isTrue);
    expect(gw.interstitialLoads, 1);
  });

  test('récompense accordée seulement si validée par la régie, après fermeture', () async {
    final s = make();
    await s.init();
    var result = s.showRewarded('life');
    final ad = gw.lastOf('rewarded');
    ad.reward!();
    var settled = false;
    result.then((_) => settled = true);
    await Future<void>.delayed(Duration.zero);
    expect(settled, isFalse, reason: 'la récompense attend la fermeture');
    ad.dismiss!();
    expect(await result, isTrue);
    expect(ad.disposed, isTrue);
  });

  test('pub fermée avant la fin : aucune récompense', () async {
    final s = make();
    await s.init();
    final result = s.showRewarded('life');
    gw.lastOf('rewarded').dismiss!();
    expect(await result, isFalse);
  });

  test("échec d'affichage : aucune récompense, annonce libérée, rechargement", () async {
    final s = make();
    await s.init();
    final loads = gw.rewardedLoads;
    final result = s.showRewarded('life');
    final ad = gw.lastOf('rewarded');
    ad.fail!('boom');
    expect(await result, isFalse);
    expect(ad.disposed, isTrue);
    expect(gw.rewardedLoads, loads + 1);
  });

  test('double appui : une seule annonce affichée', () async {
    final s = make();
    await s.init();
    final first = s.showRewarded('a');
    final second = await s.showRewarded('b');
    expect(second, isFalse);
    expect(gw.ads.where((a) => a.shows > 0).length, 1);
    // Interstitiel bloqué pendant qu'une récompensée est à l'écran.
    await s.maybeShowInterstitial('end');
    await s.maybeShowInterstitial('end');
    expect(gw.lastOf('interstitial').shows, 0);
    gw.ads.firstWhere((a) => a.shows > 0)
      ..reward!()
      ..dismiss!();
    expect(await first, isTrue);
  });

  test('une annonce ne peut pas être réaffichée', () async {
    final s = make();
    await s.init();
    final r = s.showRewarded('a');
    final ad = gw.lastOf('rewarded');
    ad
      ..reward!()
      ..dismiss!();
    await r;
    final r2 = s.showRewarded('b');
    expect(gw.lastOf('rewarded'), isNot(same(ad)));
    expect(ad.shows, 1);
    gw.lastOf('rewarded').dismiss!();
    await r2;
  });

  group('interstitiels', () {
    Future<int> playLevelEnds(AdMobAdsService s, int n, {Duration gap = const Duration(seconds: 200)}) async {
      var shown = 0;
      for (var i = 0; i < n; i++) {
        final before = gw.ads.where((a) => a.kind == 'interstitial' && a.shows > 0).length;
        final f = s.maybeShowInterstitial('level_end');
        final showing = gw.ads.where((a) => a.kind == 'interstitial' && a.shows > 0).length;
        if (showing > before) {
          shown++;
          gw.ads.lastWhere((a) => a.kind == 'interstitial' && a.shows > 0).dismiss!();
        }
        await f;
        now = now.add(gap);
      }
      return shown;
    }

    test('une fin de partie sur deux, au plus', () async {
      final s = make();
      await s.init();
      expect(await playLevelEnds(s, 6), 3);
    });

    test('jamais pour un joueur débutant', () async {
      levels = 1;
      final s = make();
      await s.init();
      expect(await playLevelEnds(s, 6), 0);
    });

    test('intervalle minimal de 2 minutes', () async {
      final s = make();
      await s.init();
      expect(await playLevelEnds(s, 8, gap: const Duration(seconds: 30)), 2);
    });

    test('pas juste après une pub récompensée', () async {
      final s = make();
      await s.init();
      final r = s.showRewarded('x');
      gw.lastOf('rewarded')
        ..reward!()
        ..dismiss!();
      await r;
      expect(await playLevelEnds(s, 2, gap: const Duration(seconds: 10)), 0);
    });

    test('« Sans pub » : ni interstitiel ni bannière, récompensées conservées', () async {
      final s = make();
      await s.init();
      s.adsRemoved = true;
      expect(await playLevelEnds(s, 6), 0);
      expect(s.banner('map'), isA<SizedBox>());
      expect(s.rewardedAvailable, isTrue);
    });
  });

  test('échec de chargement : nouvel essai différé, pas de boucle immédiate', () async {
    gw.failLoads = true;
    final s = make();
    await s.init();
    expect(gw.rewardedLoads, 1);
    expect(s.rewardedAvailable, isFalse);
    s.dispose(); // annule les minuteries de nouvel essai
  });

  test('dispose libère les annonces préchargées', () async {
    final s = make();
    await s.init();
    s.dispose();
    expect(gw.ads.every((a) => a.disposed), isTrue);
  });
}
