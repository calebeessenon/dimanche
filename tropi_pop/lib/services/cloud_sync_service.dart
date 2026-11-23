import 'dart:async';

import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:material_ui/material_ui.dart' show ChangeNotifier, debugPrint;

import '../data/player_data.dart';
import '../data/progress_merge.dart';
import '../data/progress_store.dart';

enum SyncState { idle, syncing, synced, offline, error }

/// Sauvegarde cloud de la progression et des scores (Firebase).
///
/// Schéma Firestore (voir firestore.rules) :
/// * `players/{uid}`                    progression complète du joueur ;
/// * `leaderboard/{uid}`                classement général (étoiles, score cumulé) ;
/// * `levels/{niveau}/scores/{uid}`     meilleur score du joueur sur un niveau.
///
/// Le joueur est identifié par un compte **anonyme** créé automatiquement au
/// premier lancement. Pour retrouver sa progression sur un nouveau téléphone,
/// ce compte pourra être lié à Google / Apple (`linkWithCredential`) : l'uid,
/// donc la sauvegarde, est conservé.
///
/// Firestore met les écritures en file d'attente hors connexion et les envoie
/// au retour du réseau : le jeu reste jouable sans Internet.
class CloudSyncService extends ChangeNotifier {
  static const schemaVersion = 1;

  final ProgressStore store;
  final FirebaseAuth auth;
  final FirebaseFirestore db;

  SyncState state = SyncState.idle;
  String? lastError;
  Timer? _debounce;
  bool _applyingRemote = false;

  CloudSyncService(this.store, {FirebaseAuth? auth, FirebaseFirestore? db})
      : auth = auth ?? FirebaseAuth.instance,
        db = db ?? FirebaseFirestore.instance;

  String? get uid => auth.currentUser?.uid;

  DocumentReference<Map<String, dynamic>> _player(String uid) => db.collection('players').doc(uid);

  void _set(SyncState s, [String? error]) {
    state = s;
    lastError = error;
    notifyListeners();
  }

  /// Connexion, récupération de la sauvegarde cloud, fusion, puis envoi
  /// automatique (différé de quelques secondes) à chaque modification locale.
  Future<void> start() async {
    store.addListener(_onLocalChange);
    await syncNow();
  }

  Future<void> syncNow() async {
    _set(SyncState.syncing);
    try {
      final user = auth.currentUser ?? (await auth.signInAnonymously()).user!;
      final snap = await _player(user.uid).get();
      final remote = snap.data()?['progress'];
      if (remote is Map<String, dynamic>) {
        final local = store.data;
        final merged = mergeProgress(local, PlayerData.fromJson(remote));
        if (mergeChangesLocal(local, merged)) {
          _applyingRemote = true;
          await store.replaceWith(merged);
          _applyingRemote = false;
        }
      }
      await _push(user.uid);
      _set(SyncState.synced);
    } on FirebaseException catch (e) {
      debugPrint('Synchronisation cloud impossible : ${e.code} ${e.message}');
      _set(e.code == 'unavailable' || e.code == 'network-request-failed' ? SyncState.offline : SyncState.error,
          '${e.code}: ${e.message}');
    } catch (e) {
      debugPrint('Synchronisation cloud impossible : $e');
      _set(SyncState.error, '$e');
    }
  }

  void _onLocalChange() {
    if (_applyingRemote) return;
    _debounce?.cancel();
    _debounce = Timer(const Duration(seconds: 4), () {
      final id = uid;
      if (id != null) unawaited(_push(id).catchError((Object e) => _set(SyncState.error, '$e')));
    });
  }

  Future<void> _push(String uid) async {
    final d = store.data;
    final batch = db.batch()
      ..set(_player(uid), {
        'schemaVersion': schemaVersion,
        'progress': d.toJson(),
        'unlockedLevel': d.unlockedLevel,
        'totalStars': d.totalStars,
        'updatedAt': FieldValue.serverTimestamp(),
      })
      ..set(db.collection('leaderboard').doc(uid), {
        'name': playerName(uid),
        'totalStars': d.totalStars,
        'levelsCompleted': d.levelsCompleted,
        'totalScore': d.bestScores.values.fold<int>(0, (a, b) => a + b),
        'updatedAt': FieldValue.serverTimestamp(),
      });
    await batch.commit();
  }

  /// Enregistre le score d'un niveau terminé (conservé seulement s'il est meilleur).
  Future<void> submitLevelScore(int level, int score, int stars) async {
    final id = uid;
    if (id == null) return;
    final ref = db.collection('levels').doc('$level').collection('scores').doc(id);
    try {
      await db.runTransaction((tx) async {
        final cur = await tx.get(ref);
        final best = (cur.data()?['score'] as num?)?.toInt() ?? -1;
        if (score <= best) return;
        tx.set(ref, {
          'name': playerName(id),
          'score': score,
          'stars': stars,
          'updatedAt': FieldValue.serverTimestamp(),
        });
      });
    } catch (e) {
      debugPrint('Score non envoyé (sera ressaisi à la prochaine victoire) : $e');
    }
  }

  /// Meilleurs joueurs (classement général par étoiles).
  Future<List<Map<String, dynamic>>> topPlayers({int limit = 50}) async {
    final q = await db.collection('leaderboard').orderBy('totalStars', descending: true).limit(limit).get();
    return [for (final d in q.docs) {...d.data(), 'uid': d.id}];
  }

  /// Pseudonyme public dérivé de l'uid (aucune donnée personnelle publiée).
  static String playerName(String uid) => 'Joueur ${uid.substring(0, 5).toUpperCase()}';

  @override
  void dispose() {
    _debounce?.cancel();
    store.removeListener(_onLocalChange);
    super.dispose();
  }
}
