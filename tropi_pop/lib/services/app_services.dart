import 'package:material_ui/material_ui.dart';

import '../data/progress_store.dart';
import 'audio_service.dart';
import 'monetization.dart';

/// Conteneur des services, accessible partout via `AppServices.of(context)`.
class AppServices extends InheritedWidget {
  final ProgressStore progress;
  final AudioService audio;
  final AdsService ads;
  final PurchaseService purchases;

  const AppServices({
    super.key,
    required this.progress,
    required this.audio,
    required this.ads,
    required this.purchases,
    required super.child,
  });

  static AppServices of(BuildContext context) => context.dependOnInheritedWidgetOfExactType<AppServices>()!;

  /// Accès sans abonnement (utilisable dans initState).
  static AppServices read(BuildContext context) => context.getInheritedWidgetOfExactType<AppServices>()!;

  @override
  bool updateShouldNotify(AppServices old) => false;
}
