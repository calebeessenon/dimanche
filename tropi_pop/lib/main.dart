import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:material_ui/material_ui.dart';

import 'app.dart';
import 'core/l10n/strings.dart';
import 'data/progress_store.dart';
import 'firebase_options.dart';
import 'services/audio_service.dart';
import 'services/ads/ad_config.dart';
import 'services/ads/admob_ads_service.dart';
import 'services/ads/consent_manager.dart';
import 'services/ads/google_ad_gateway.dart';
import 'services/cloud_sync_service.dart';
import 'services/iap/iap_config.dart';
import 'services/iap/purchase_verifier.dart';
import 'services/iap/store_purchase_service.dart';
import 'services/monetization.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
  SystemChrome.setSystemUIOverlayStyle(SystemUiOverlayStyle.light);

  final progress = await ProgressStore.open();
  S.setLanguage(progress.data.language);

  final audio = AudioService();
  // L'audio se charge en arrière-plan pour ne pas retarder l'affichage.
  audio.init(sound: progress.data.sound, music: progress.data.music);

  final mobile = !kIsWeb && (defaultTargetPlatform == TargetPlatform.android || defaultTargetPlatform == TargetPlatform.iOS);

  // Publicité : AdMob sur téléphone (identifiants de TEST par défaut, voir
  // AdConfig) ; simulation sur le web en mode debug uniquement.
  // L'initialisation (consentement UMP puis SDK) est lancée par TropiPopApp
  // après la première image, pour que le formulaire de consentement s'affiche
  // au-dessus du jeu.
  final AdsService ads = mobile
      ? AdMobAdsService(
          gateway: GoogleAdGateway(),
          consent: ConsentManager(),
          ids: AdConfig.forPlatform(),
          levelsCompleted: () => progress.data.levelsCompleted,
          onFullScreenOpened: () => audio.pauseAll(),
          onFullScreenClosed: () => audio.resumeAll(),
        )
      : SimulatedAdsService(enabled: kDebugMode);

  // Achats intégrés : architecture prête, DÉSACTIVÉE tant que IAP_ENABLED
  // n'est pas défini (et la vérification serveur refuse tout par défaut).
  final PurchaseService purchases = mobile && IapConfig.enabled
      ? StorePurchaseService(
          verifier: RejectAllVerifier(),
          deliveredIds: progress.deliveredPurchaseIds,
          markDelivered: progress.markPurchaseDelivered,
        )
      : UnavailablePurchaseService();
  purchases.onDelivered = (id) => progress.deliverPurchase(id);
  await purchases.init();

  // Firebase (Android / iOS) : sauvegarde cloud et scores. En cas d'échec
  // (pas de réseau au premier lancement, plateforme non configurée), le jeu
  // démarre quand même hors ligne.
  CloudSyncService? cloud;
  final options = DefaultFirebaseOptions.currentPlatform;
  if (options != null) {
    try {
      await Firebase.initializeApp(options: options);
      cloud = CloudSyncService(progress);
      cloud.start(); // en arrière-plan
    } catch (e) {
      debugPrint('Firebase indisponible : $e');
    }
  }

  runApp(TropiPopApp(progress: progress, audio: audio, ads: ads, purchases: purchases, cloud: cloud));
}
