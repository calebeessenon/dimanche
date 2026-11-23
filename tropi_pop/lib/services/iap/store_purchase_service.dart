import 'dart:async';

import 'package:in_app_purchase/in_app_purchase.dart';
import 'package:material_ui/material_ui.dart' show debugPrint;

import '../monetization.dart';
import 'iap_config.dart';
import 'purchase_verifier.dart';

/// Achats intégrés via `in_app_purchase` (Google Play Billing / StoreKit).
///
/// Flux : achat → flux `purchaseStream` → vérification serveur
/// ([PurchaseVerifier]) → livraison ([onDelivered], idempotente) →
/// `completePurchase` (sinon Google rembourse automatiquement après 3 jours).
///
/// N'est instancié que si [IapConfig.enabled] est vrai.
class StorePurchaseService implements PurchaseService {
  final InAppPurchase _iap;
  final PurchaseVerifier verifier;
  StreamSubscription<List<PurchaseDetails>>? _sub;
  final Map<String, ProductDetails> _details = {};
  bool _available = false;
  void Function(String productId)? _deliver;

  /// Achats déjà livrés (évite une double livraison si le flux rejoue un achat).
  final Set<String> Function() deliveredIds;
  final Future<void> Function(String purchaseId) markDelivered;

  StorePurchaseService({
    required this.verifier,
    required this.deliveredIds,
    required this.markDelivered,
    InAppPurchase? iap,
  }) : _iap = iap ?? InAppPurchase.instance;

  @override
  set onDelivered(void Function(String productId) cb) => _deliver = cb;

  @override
  bool get available => _available && _details.isNotEmpty;

  @override
  List<ProductInfo> get products => [
        for (final id in Products.all)
          ProductInfo(id, _details[id]?.price ?? Products.fallbackPrices[id]!),
      ];

  @override
  Future<void> init() async {
    _sub = _iap.purchaseStream.listen(_onPurchases, onError: (Object e) => debugPrint('IAP flux : $e'));
    _available = await _iap.isAvailable();
    if (!_available) return;
    final resp = await _iap.queryProductDetails(Products.all.toSet());
    if (resp.notFoundIDs.isNotEmpty) debugPrint('IAP produits introuvables : ${resp.notFoundIDs}');
    for (final d in resp.productDetails) {
      _details[d.id] = d;
    }
  }

  @override
  Future<void> buy(String productId) async {
    final d = _details[productId];
    if (!available || d == null) return;
    final param = PurchaseParam(productDetails: d);
    if (IapConfig.isConsumable(productId)) {
      await _iap.buyConsumable(purchaseParam: param);
    } else {
      await _iap.buyNonConsumable(purchaseParam: param);
    }
  }

  @override
  Future<void> restore() => _iap.restorePurchases();

  Future<void> _onPurchases(List<PurchaseDetails> list) async {
    for (final p in list) {
      switch (p.status) {
        case PurchaseStatus.pending:
          continue; // paiement en attente (ex. espèces) : rien à livrer
        case PurchaseStatus.error:
        case PurchaseStatus.canceled:
          debugPrint('IAP ${p.productID} : ${p.status} ${p.error?.message ?? ''}');
        case PurchaseStatus.purchased:
        case PurchaseStatus.restored:
          final id = p.purchaseID ?? '${p.productID}-${p.transactionDate}';
          if (!deliveredIds().contains(id)) {
            final ok = await verifier.verify(
              productId: p.productID,
              purchaseId: id,
              source: p.verificationData.source,
              serverVerificationData: p.verificationData.serverVerificationData,
            );
            if (!ok) {
              // Non vérifié : on ne livre pas et on NE finalise PAS (l'achat
              // sera re-présenté ; sans finalisation Google le rembourse).
              debugPrint('IAP ${p.productID} : vérification refusée');
              continue;
            }
            _deliver?.call(p.productID);
            await markDelivered(id);
          }
      }
      if (p.pendingCompletePurchase) await _iap.completePurchase(p);
    }
  }

  void dispose() => _sub?.cancel();
}
