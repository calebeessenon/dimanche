import 'dart:math';

import 'package:material_ui/material_ui.dart';
import 'package:flutter/scheduler.dart';

import '../controller/game_controller.dart';
import '../engine/models.dart';
import '../render/board_painter.dart';

/// Widget du plateau : rendu (CustomPainter) + gestes (glisser / toucher).
class BoardView extends StatefulWidget {
  final GameController controller;
  const BoardView({super.key, required this.controller});

  @override
  State<BoardView> createState() => _BoardViewState();
}

class _BoardViewState extends State<BoardView> with SingleTickerProviderStateMixin {
  late final Ticker _ticker;
  Duration _last = Duration.zero;
  Pos? _dragStart;
  Offset _dragOrigin = Offset.zero;
  bool _dragUsed = false;

  GameController get c => widget.controller;

  @override
  void initState() {
    super.initState();
    _ticker = createTicker((elapsed) {
      final dt = (elapsed - _last).inMicroseconds / 1e6;
      _last = elapsed;
      c.anim.tick(dt);
    })
      ..start();
  }

  @override
  void dispose() {
    _ticker.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final board = c.engine.board;
    return AspectRatio(
      aspectRatio: board.width / board.height,
      child: LayoutBuilder(builder: (context, box) {
        final cs = min(box.maxWidth / board.width, box.maxHeight / board.height);
        final ox = (box.maxWidth - cs * board.width) / 2;
        final oy = (box.maxHeight - cs * board.height) / 2;

        Pos? cellAt(Offset local) {
          final x = ((local.dx - ox) / cs).floor();
          final y = ((local.dy - oy) / cs).floor();
          return board.inside(x, y) ? Pos(x, y) : null;
        }

        return GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTapUp: (d) {
            final p = cellAt(d.localPosition);
            if (p != null) c.tap(p);
          },
          onPanStart: (d) {
            _dragStart = cellAt(d.localPosition);
            _dragOrigin = d.localPosition;
            _dragUsed = false;
          },
          onPanUpdate: (d) {
            final s = _dragStart;
            if (s == null || _dragUsed || c.hammerMode) return;
            final delta = d.localPosition - _dragOrigin;
            if (delta.distance < cs * 0.35) return;
            _dragUsed = true;
            final dir = delta.dx.abs() > delta.dy.abs()
                ? Pos(s.x + delta.dx.sign.toInt(), s.y)
                : Pos(s.x, s.y + delta.dy.sign.toInt());
            c.trySwap(s, dir);
          },
          onPanEnd: (_) => _dragStart = null,
          child: ListenableBuilder(
            listenable: c,
            builder: (context, _) => CustomPaint(
              size: Size(box.maxWidth, box.maxHeight),
              painter: BoardPainter(
                board: board,
                anim: c.anim,
                selected: c.selected,
                hint: c.hint,
                hammerMode: c.hammerMode,
              ),
            ),
          ),
        );
      }),
    );
  }
}
