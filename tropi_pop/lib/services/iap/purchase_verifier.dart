/// Vérification d'un achat AVANT livraison.
///
/// Règle d'or : ne jamais faire confiance au téléphone. Le reçu
/// (`serverVerificationData`) doit être contrôlé par un serveur auprès de
/// Google Play Developer API (purchases.products.get) ou de l'App Store Server
/// API, qui confirme : produit, état « acheté », non déjà consommé/remboursé.
///
/// Implémentation prévue : Cloud Function Firebase `verifyPurchase` (projet
/// pitro-pop) appelée avec l'utilisateur connecté, qui enregistre aussi le
/// jeton d'achat pour empêcher sa réutilisation. Voir docs/MONETIZATION.md.
abstract class PurchaseVerifier {
  Future<bool> verify({
    required String productId,
    required String purchaseId,
    required String source, // 'google_play' | 'app_store'
    required String serverVerificationData,
  });
}

/// Refuse tout : utilisé tant que le serveur de vérification n'existe pas.
/// Aucun achat ne peut donc être livré par erreur.
class RejectAllVerifier implements PurchaseVerifier {
  @override
  Future<bool> verify({
    required String productId,
    required String purchaseId,
    required String source,
    required String serverVerificationData,
  }) async =>
      false;
}
