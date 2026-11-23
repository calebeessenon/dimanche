import 'package:flutter/services.dart';
import 'package:material_ui/material_ui.dart';

import 'app.dart';
import 'core/l10n/strings.dart';
import 'data/progress_store.dart';
import 'services/audio_service.dart';
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

  // Étapes 1-4 : implémentations simulées. Étape 6 : AdMob + Billing.
  final ads = SimulatedAdsService(enabled: true);
  final purchases = UnavailablePurchaseService();
  await ads.init();
  await purchases.init();

  runApp(TropiPopApp(progress: progress, audio: audio, ads: ads, purchases: purchases));
}
