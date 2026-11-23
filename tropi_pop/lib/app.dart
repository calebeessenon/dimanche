import 'dart:async';

import 'package:material_ui/material_ui.dart';

import 'core/l10n/strings.dart';
import 'core/theme/app_theme.dart';
import 'data/progress_store.dart';
import 'services/app_services.dart';
import 'services/audio_service.dart';
import 'services/cloud_sync_service.dart';
import 'services/monetization.dart';
import 'ui/screens/splash_screen.dart';
import 'ui/widgets/common.dart';

final navigatorKey = GlobalKey<NavigatorState>();

class TropiPopApp extends StatefulWidget {
  final ProgressStore progress;
  final AudioService audio;
  final AdsService ads;
  final PurchaseService purchases;
  final CloudSyncService? cloud;
  final Widget home;

  const TropiPopApp({
    super.key,
    required this.progress,
    required this.audio,
    required this.ads,
    required this.purchases,
    this.cloud,
    this.home = const SplashScreen(),
  });

  @override
  State<TropiPopApp> createState() => _TropiPopAppState();
}

class _TropiPopAppState extends State<TropiPopApp> with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    final ads = widget.ads;
    if (ads is SimulatedAdsService) ads.presenter = _showTestAd;
    _noAds = widget.progress.data.noAds;
    ads.adsRemoved = _noAds;
    widget.progress.addListener(_syncNoAds);
    // Consentement + SDK après la première image (le formulaire UMP a besoin
    // d'une interface affichée).
    WidgetsBinding.instance.addPostFrameCallback((_) => unawaited(widget.ads.init()));
  }

  bool _noAds = false;
  void _syncNoAds() {
    final v = widget.progress.data.noAds;
    if (v != _noAds) {
      _noAds = v;
      widget.ads.adsRemoved = v;
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    widget.progress.removeListener(_syncNoAds);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Coupe la musique en arrière-plan.
    if (state == AppLifecycleState.paused) unawaited(widget.audio.pauseAll());
    if (state == AppLifecycleState.resumed) {
      unawaited(widget.audio.resumeAll());
      unawaited(widget.cloud?.syncNow());
    }
  }

  /// Fausse publicité de test (remplacée par AdMob à l'étape 6).
  Future<void> _showTestAd() async {
    final ctx = navigatorKey.currentContext;
    if (ctx == null) return;
    await showPopDialog<void>(ctx, dismissible: false, (c) {
      Future<void>.delayed(const Duration(seconds: 2), () {
        if (c.mounted) Navigator.of(c).pop();
      });
      return PopPanel(
        title: tr('ad_test'),
        child: const SizedBox(height: 120, child: Center(child: CircularProgressIndicator())),
      );
    });
  }

  @override
  Widget build(BuildContext context) => AppServices(
        progress: widget.progress,
        audio: widget.audio,
        ads: widget.ads,
        purchases: widget.purchases,
        cloud: widget.cloud,
        child: MaterialApp(
          navigatorKey: navigatorKey,
          title: 'Tropi Pop',
          debugShowCheckedModeBanner: false,
          theme: AppTheme.build(),
          home: widget.home,
        ),
      );
}
