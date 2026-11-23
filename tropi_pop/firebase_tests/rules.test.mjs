// Tests des règles Firestore (émulateur local, aucun accès au vrai projet).
// Lancer : cd firebase_tests && npm install && npm test
import { test, before, after, beforeEach } from 'node:test';
import { readFileSync } from 'node:fs';
import { initializeTestEnvironment, assertSucceeds, assertFails } from '@firebase/rules-unit-testing';
import { doc, setDoc, getDoc, collection, getDocs, serverTimestamp } from 'firebase/firestore';

let env;
before(async () => {
  env = await initializeTestEnvironment({
    projectId: 'demo-tropipop',
    firestore: { rules: readFileSync('../firestore.rules', 'utf8') },
  });
});
after(() => env.cleanup());
beforeEach(() => env.clearFirestore());

const player = (over = {}) => ({
  schemaVersion: 1,
  progress: { coins: 500, unlockedLevel: 3, stars: { 1: 3, 2: 2 } },
  unlockedLevel: 3,
  totalStars: 5,
  updatedAt: serverTimestamp(),
  ...over,
});
const board = (over = {}) => ({ name: 'Joueur ABCDE', totalStars: 5, levelsCompleted: 2, totalScore: 30000, updatedAt: serverTimestamp(), ...over });
const score = (s) => ({ name: 'Joueur ABCDE', score: s, stars: 2, updatedAt: serverTimestamp() });

test('un joueur lit et écrit sa propre progression', async () => {
  const db = env.authenticatedContext('alice').firestore();
  await assertSucceeds(setDoc(doc(db, 'players/alice'), player()));
  await assertSucceeds(getDoc(doc(db, 'players/alice')));
});

test("un joueur ne peut ni lire ni écrire la progression d'un autre", async () => {
  const db = env.authenticatedContext('bob').firestore();
  await env.withSecurityRulesDisabled((c) => setDoc(doc(c.firestore(), 'players/alice'), { x: 1 }));
  await assertFails(getDoc(doc(db, 'players/alice')));
  await assertFails(setDoc(doc(db, 'players/alice'), player()));
});

test('non connecté : tout est refusé', async () => {
  const db = env.unauthenticatedContext().firestore();
  await assertFails(getDoc(doc(db, 'players/alice')));
  await assertFails(getDocs(collection(db, 'leaderboard')));
});

test('valeurs aberrantes refusées', async () => {
  const db = env.authenticatedContext('alice').firestore();
  await assertFails(setDoc(doc(db, 'players/alice'), player({ unlockedLevel: 99999 })));
  await assertFails(setDoc(doc(db, 'players/alice'), player({ progress: { coins: 999999999 } })));
  await assertFails(setDoc(doc(db, 'players/alice'), player({ hack: true })));
  await assertFails(setDoc(doc(db, 'leaderboard/alice'), board({ totalStars: 100000 })));
});

test('classement : lecture pour tous les connectés, écriture de sa ligne seulement', async () => {
  const alice = env.authenticatedContext('alice').firestore();
  const bob = env.authenticatedContext('bob').firestore();
  await assertSucceeds(setDoc(doc(alice, 'leaderboard/alice'), board()));
  await assertSucceeds(getDocs(collection(bob, 'leaderboard')));
  await assertFails(setDoc(doc(bob, 'leaderboard/alice'), board()));
});

test('score de niveau : seulement en hausse', async () => {
  const db = env.authenticatedContext('alice').firestore();
  const ref = doc(db, 'levels/12/scores/alice');
  await assertSucceeds(setDoc(ref, score(10000)));
  await assertFails(setDoc(ref, score(5000)));
  await assertSucceeds(setDoc(ref, score(15000)));
  await assertFails(setDoc(ref, score(99999999)));
});

test('collections inconnues interdites', async () => {
  const db = env.authenticatedContext('alice').firestore();
  await assertFails(setDoc(doc(db, 'admin/config'), { x: 1 }));
});
