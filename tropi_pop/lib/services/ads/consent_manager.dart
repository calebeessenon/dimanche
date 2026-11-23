import 'dart:async';

import 'package:google_mobile_ads/google_mobile_ads.dart';
import 'package:material_ui/material_ui.dart' show debugPrint;

import 'admob_ads_service.dart' show ConsentGate;

/// Consentement publicitaire via la User Messaging Platform (UMP) de Google.
///
/// * Espace économique européen / Royaume-Uni / Suisse : affiche le message
///   RGPD configuré dans la console AdMob (Confidentialité et messages).
/// * iOS : le même message peut précéder la demande ATT (App Tracking
///   Transparency) si elle est activée dans AdMob.
/// * Aucune annonce n'est demandée tant que `canRequestAds` est faux.
///
/// Doc : https://developers.google.com/admob/flutter/privacy
class ConsentManager implements ConsentGate {
  bool _gathered = false;

  /// Forcer une géographie de test (UE) en développement :
  /// `--dart-define=UMP_DEBUG_EEA=true` (+ ADMOB_TEST_DEVICES pour l'appareil).
  static const _debugEea = bool.fromEnvironment('UMP_DEBUG_EEA');

  /// Met à jour les informations de consentement puis affiche le formulaire
  /// si nécessaire. Ne lève jamais d'exception : en cas d'erreur, on se fie à
  /// `canRequestAds` (consentement éventuellement obtenu lors d'une session
  /// précédente).
  @override
  Future<void> gatherConsent({List<String> testDeviceIds = const []}) async {
    final params = ConsentRequestParameters(
      consentDebugSettings: _debugEea
          ? ConsentDebugSettings(debugGeography: DebugGeography.debugGeographyEea, testIdentifiers: testDeviceIds)
          : null,
    );
    final done = Completer<void>();
    ConsentInformation.instance.requestConsentInfoUpdate(
      params,
      () async {
        await ConsentForm.loadAndShowConsentFormIfRequired((FormError? error) {
          if (error != null) debugPrint('UMP formulaire : ${error.errorCode} ${error.message}');
          if (!done.isCompleted) done.complete();
        });
      },
      (FormError error) {
        debugPrint('UMP mise à jour : ${error.errorCode} ${error.message}');
        if (!done.isCompleted) done.complete();
      },
    );
    await done.future.timeout(const Duration(seconds: 30), onTimeout: () {});
    _gathered = true;
  }

  bool get gathered => _gathered;

  @override
  Future<bool> canRequestAds() async {
    try {
      return await ConsentInformation.instance.canRequestAds();
    } catch (_) {
      return false;
    }
  }

  /// Le joueur doit-il disposer d'un bouton « Options de confidentialité » ?
  @override
  Future<bool> privacyOptionsRequired() async {
    try {
      return await ConsentInformation.instance.getPrivacyOptionsRequirementStatus() ==
          PrivacyOptionsRequirementStatus.required;
    } catch (_) {
      return false;
    }
  }

  /// Rouvre le formulaire pour modifier ses choix.
  @override
  Future<void> showPrivacyOptions() {
    final done = Completer<void>();
    ConsentForm.showPrivacyOptionsForm((FormError? error) {
      if (error != null) debugPrint('UMP options : ${error.errorCode} ${error.message}');
      if (!done.isCompleted) done.complete();
    });
    return done.future;
  }
}
