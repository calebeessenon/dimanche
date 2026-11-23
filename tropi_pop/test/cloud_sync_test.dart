import 'package:fake_cloud_firestore/fake_cloud_firestore.dart';
import 'package:firebase_auth_mocks/firebase_auth_mocks.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:tropi_pop/data/player_data.dart';
import 'package:tropi_pop/data/progress_merge.dart';
import 'package:tropi_pop/data/progress_store.dart';
import 'package:tropi_pop/services/cloud_sync_service.dart';

void main() {
  group('fusion des sauvegardes', () {
    test('ne perd jamais de progression', () {
      final local = PlayerData(unlockedLevel: 5, stars: {1: 3, 2: 1}, bestScores: {1: 100}, updatedAtMs: 10);
      final cloud = PlayerData(unlockedLevel: 12, stars: {2: 3, 7: 2}, bestScores: {1: 50, 7: 900}, updatedAtMs: 5);
      final m = mergeProgress(local, cloud);
      expect(m.unlockedLevel, 12);
      expect(m.stars, {1: 3, 2: 3, 7: 2});
      expect(m.bestScores, {1: 100, 7: 900});
    });

    test('pièces de la sauvegarde la plus récente (pas de duplication)', () {
      final local = PlayerData(coins: 900, updatedAtMs: 10);
      final cloud = PlayerData(coins: 300, updatedAtMs: 20);
      expect(mergeProgress(local, cloud).coins, 300);
      expect(mergeProgress(cloud, local).coins, 300);
    });

    test("réglages de l'appareil conservés", () {
      final local = PlayerData(language: 'en', music: false);
      final cloud = PlayerData(language: 'fr', music: true, updatedAtMs: 99);
      final m = mergeProgress(local, cloud);
      expect(m.language, 'en');
      expect(m.music, isFalse);
    });
  });

  group('CloudSyncService', () {
    late FakeFirebaseFirestore db;
    late MockFirebaseAuth auth;

    setUp(() {
      db = FakeFirebaseFirestore();
      auth = MockFirebaseAuth();
    });

    test('premier lancement : compte anonyme + envoi de la progression', () async {
      final store = ProgressStore(MemoryStore());
      await store.completeLevel(1, 12000, 3);
      final sync = CloudSyncService(store, auth: auth, db: db);
      await sync.syncNow();
      expect(sync.state, SyncState.synced);
      final uid = sync.uid!;
      final doc = await db.collection('players').doc(uid).get();
      expect(doc.data()!['unlockedLevel'], 2);
      expect(doc.data()!['totalStars'], 3);
      final lb = await db.collection('leaderboard').doc(uid).get();
      expect(lb.data()!['totalScore'], 12000);
      expect(lb.data()!['name'], startsWith('Joueur '));
    });

    test('nouveau téléphone (même compte) : la progression cloud est restaurée', () async {
      final oldPhone = ProgressStore(MemoryStore());
      for (var n = 1; n <= 8; n++) {
        await oldPhone.completeLevel(n, 5000, 2);
      }
      final sync1 = CloudSyncService(oldPhone, auth: auth, db: db);
      await sync1.syncNow();

      final newPhone = ProgressStore(MemoryStore()); // installation vierge
      final sync2 = CloudSyncService(newPhone, auth: auth, db: db); // même compte (lié Google/Apple)
      await sync2.syncNow();
      expect(newPhone.data.unlockedLevel, 9);
      expect(newPhone.data.totalStars, 16);
    });

    test('scores de niveau : seul le meilleur est conservé', () async {
      final sync = CloudSyncService(ProgressStore(MemoryStore()), auth: auth, db: db);
      await sync.syncNow();
      await sync.submitLevelScore(4, 8000, 2);
      await sync.submitLevelScore(4, 3000, 1);
      final ref = db.collection('levels').doc('4').collection('scores').doc(sync.uid);
      expect((await ref.get()).data()!['score'], 8000);
      await sync.submitLevelScore(4, 9500, 3);
      expect((await ref.get()).data()!['score'], 9500);
    });

    test('classement trié par étoiles', () async {
      for (final (uid, stars) in [('a', 10), ('b', 30), ('c', 20)]) {
        await db.collection('leaderboard').doc(uid).set({'name': uid, 'totalStars': stars});
      }
      final sync = CloudSyncService(ProgressStore(MemoryStore()), auth: auth, db: db);
      final top = await sync.topPlayers();
      expect(top.map((e) => e['uid']), ['b', 'c', 'a']);
    });
  });
}
