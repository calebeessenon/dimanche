import 'package:flutter_test/flutter_test.dart';
import 'package:material_ui/material_ui.dart';
import 'package:tropi_pop/app.dart';
import 'package:tropi_pop/data/progress_store.dart';
import 'package:tropi_pop/game/controller/game_controller.dart';
import 'package:tropi_pop/game/levels/level_catalog.dart';
import 'package:tropi_pop/game/widgets/board_view.dart';
import 'package:tropi_pop/services/audio_service.dart';
import 'package:tropi_pop/services/monetization.dart';
import 'package:tropi_pop/ui/screens/game_screen.dart';
import 'package:tropi_pop/ui/screens/home_screen.dart';

void main() {
  Widget app(ProgressStore p) => TropiPopApp(
        progress: p,
        audio: AudioService(),
        ads: SimulatedAdsService(enabled: false),
        purchases: UnavailablePurchaseService(),
        home: const HomeScreen(),
      );

  testWidgets('accueil → lancement du niveau → écran de jeu', (tester) async {
    await tester.binding.setSurfaceSize(const Size(400, 860));
    final p = ProgressStore(MemoryStore());
    await p.claimDaily(); // évite l'ouverture automatique de la récompense
    await tester.pumpWidget(app(p));
    await tester.pump(const Duration(milliseconds: 500));

    expect(find.text('Jouer'), findsOneWidget);
    expect(find.text('Continuer'), findsOneWidget);

    await tester.tap(find.text('Continuer'));
    await tester.pump(const Duration(milliseconds: 100));
    await tester.pump(const Duration(milliseconds: 600));
    expect(find.text('Niveau 1'), findsWidgets);

    await tester.tap(find.text("C'est parti !"));
    await tester.pump(const Duration(milliseconds: 100));
    await tester.pump(const Duration(milliseconds: 600));
    expect(find.byType(GameScreen), findsOneWidget);
    expect(find.byType(BoardView), findsOneWidget);
    expect(p.lives, 4, reason: 'une vie est réservée au lancement');
  });

  testWidgets('un coup joué via le plateau consomme un coup et se résout', (tester) async {
    final c = GameController(LevelCatalog.level(5), seed: 42);
    await tester.pumpWidget(MaterialApp(home: Scaffold(body: Center(child: SizedBox(width: 400, height: 400, child: BoardView(controller: c))))));
    final before = c.engine.movesLeft;
    final (a, b) = c.engine.findHint()!;
    final f = c.trySwap(a, b);
    for (var i = 0; i < 200 && c.phase == Phase.busy; i++) {
      await tester.pump(const Duration(milliseconds: 16));
    }
    await f;
    expect(c.engine.movesLeft, before - 1);
    expect(c.engine.score, greaterThan(0));
    expect(c.phase, Phase.idle);
    // Toutes les cases jouables sont remplies.
    for (final p in c.engine.board.positions) {
      final cell = c.engine.board.at(p);
      if (cell.canHoldTile) expect(cell.tile, isNotNull);
    }
    await tester.pumpWidget(const SizedBox());
    c.dispose();
  });
}
