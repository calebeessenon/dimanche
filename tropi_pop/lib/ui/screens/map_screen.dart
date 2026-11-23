import 'dart:math';

import 'package:material_ui/material_ui.dart';

import '../../core/l10n/strings.dart';
import '../../core/theme/app_theme.dart';
import '../../game/levels/level_catalog.dart';
import '../../services/app_services.dart';
import '../widgets/common.dart';
import '../widgets/top_bar.dart';
import 'level_start_dialog.dart';

/// Carte de progression : chemin sinueux à travers 10 mondes.
class MapScreen extends StatefulWidget {
  const MapScreen({super.key});

  @override
  State<MapScreen> createState() => _MapScreenState();
}

class _MapScreenState extends State<MapScreen> {
  static const spacing = 96.0;
  static const topPad = 140.0;
  static const bottomPad = 120.0;
  ScrollController? _scroll;

  static const worldColors = [
    [Color(0xFFFFC46B), Color(0xFFFF8A5B)], // plage mangue
    [Color(0xFF41D3C8), Color(0xFF1C8FB5)], // lagon
    [Color(0xFF7BD66B), Color(0xFF2E8B57)], // jungle
    [Color(0xFFFF7A59), Color(0xFFB0301C)], // volcan
    [Color(0xFFC9A27A), Color(0xFF7A5536)], // île coco
    [Color(0xFFFF8FAB), Color(0xFFD6336C)], // récif corail
    [Color(0xFFFFE066), Color(0xFFE0A100)], // ananas
    [Color(0xFFB197FC), Color(0xFF6741D9)], // cascade
    [Color(0xFFF2C185), Color(0xFFB7793F)], // désert
    [Color(0xFFFFD43B), Color(0xFFFF6B6B)], // sommet soleil
  ];

  double get _height => topPad + bottomPad + (LevelCatalog.count - 1) * spacing;

  Offset _nodePos(int n, double width) {
    final y = _height - bottomPad - (n - 1) * spacing;
    final x = width / 2 + sin(n * 0.85) * width * 0.28;
    return Offset(x, y);
  }

  @override
  Widget build(BuildContext context) {
    final p = AppServices.of(context).progress;
    return Scaffold(
      body: Stack(children: [
        LayoutBuilder(builder: (context, box) {
          final width = box.maxWidth;
          final current = p.data.unlockedLevel.clamp(1, LevelCatalog.count);
          // Défilement inversé : 0 = bas de la carte (niveau 1).
          _scroll ??= ScrollController(
            initialScrollOffset: max(0, (current - 1) * spacing + bottomPad - box.maxHeight / 2),
          );
          return SingleChildScrollView(
            controller: _scroll,
            reverse: true,
            child: SizedBox(
              height: _height,
              width: width,
              child: ListenableBuilder(
                listenable: p,
                builder: (context, _) => Stack(children: [
                  Positioned.fill(child: CustomPaint(painter: _MapPainter(this, width, p.data.unlockedLevel))),
                  for (var w = 0; w < LevelCatalog.count ~/ 10; w++)
                    Positioned(
                      left: 0,
                      right: 0,
                      top: _nodePos(w * 10 + 10, width).dy - spacing * 0.75,
                      child: Center(
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                          decoration: BoxDecoration(
                            color: const Color(0x88000000),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text('${w + 1}. ${tr('world_$w')}', style: AppTheme.title(16)),
                        ),
                      ),
                    ),
                  for (var n = 1; n <= LevelCatalog.count; n++)
                    Positioned(
                      left: _nodePos(n, width).dx - 34,
                      top: _nodePos(n, width).dy - 34,
                      child: _LevelNode(
                        n: n,
                        stars: p.starsFor(n),
                        unlocked: p.isUnlocked(n),
                        current: n == current,
                        onTap: () => showLevelStart(context, n),
                      ),
                    ),
                ]),
              ),
            ),
          );
        }),
        SafeArea(
          child: TopBar(
            leading: GestureDetector(
              onTap: () => Navigator.pop(context),
              child: Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(color: AppColors.pink, shape: BoxShape.circle, border: Border.all(color: Colors.white, width: 3)),
                child: const Icon(Icons.arrow_back_rounded, color: Colors.white),
              ),
            ),
          ),
        ),
      ]),
    );
  }
}

class _MapPainter extends CustomPainter {
  final _MapScreenState s;
  final double width;
  final int unlocked;
  _MapPainter(this.s, this.width, this.unlocked);

  @override
  void paint(Canvas canvas, Size size) {
    // Bandes de couleur des mondes.
    for (var w = 0; w < LevelCatalog.count ~/ 10; w++) {
      final bottom = w == 0 ? size.height : s._nodePos(w * 10, width).dy - _MapScreenState.spacing / 2;
      final top = w == LevelCatalog.count ~/ 10 - 1 ? 0.0 : s._nodePos(w * 10 + 10, width).dy - _MapScreenState.spacing / 2;
      final cols = _MapScreenState.worldColors[w % _MapScreenState.worldColors.length];
      final rect = Rect.fromLTRB(0, top, size.width, bottom);
      canvas.drawRect(
        rect,
        Paint()
          ..shader = LinearGradient(begin: Alignment.bottomCenter, end: Alignment.topCenter, colors: cols).createShader(rect),
      );
      // Décors : petites îles / palmiers stylisés.
      final rnd = Random(w);
      for (var i = 0; i < 6; i++) {
        final c = Offset(rnd.nextDouble() * size.width, top + rnd.nextDouble() * (bottom - top));
        canvas.drawCircle(c, 18 + rnd.nextDouble() * 30, Paint()..color = Colors.white.withValues(alpha: 0.08));
      }
    }
    // Chemin.
    final path = Path();
    for (var n = 1; n <= LevelCatalog.count; n++) {
      final p = s._nodePos(n, width);
      if (n == 1) {
        path.moveTo(p.dx, p.dy);
      } else {
        final prev = s._nodePos(n - 1, width);
        final mid = (prev.dy + p.dy) / 2;
        path.cubicTo(prev.dx, mid, p.dx, mid, p.dx, p.dy);
      }
    }
    canvas.drawPath(
        path,
        Paint()
          ..style = PaintingStyle.stroke
          ..strokeWidth = 22
          ..strokeCap = StrokeCap.round
          ..color = const Color(0x55000000));
    canvas.drawPath(
        path,
        Paint()
          ..style = PaintingStyle.stroke
          ..strokeWidth = 14
          ..strokeCap = StrokeCap.round
          ..color = AppColors.cream.withValues(alpha: 0.9));
  }

  @override
  bool shouldRepaint(covariant _MapPainter old) => old.unlocked != unlocked || old.width != width;
}

class _LevelNode extends StatefulWidget {
  final int n;
  final int stars;
  final bool unlocked;
  final bool current;
  final VoidCallback onTap;
  const _LevelNode({required this.n, required this.stars, required this.unlocked, required this.current, required this.onTap});

  @override
  State<_LevelNode> createState() => _LevelNodeState();
}

class _LevelNodeState extends State<_LevelNode> with SingleTickerProviderStateMixin {
  AnimationController? _pulse;

  @override
  void initState() {
    super.initState();
    if (widget.current) {
      _pulse = AnimationController(vsync: this, duration: const Duration(milliseconds: 900))..repeat(reverse: true);
    }
  }

  @override
  void dispose() {
    _pulse?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final boss = widget.n % 10 == 0;
    final color = !widget.unlocked ? Colors.grey : (boss ? AppColors.coral : AppColors.pink);
    Widget node = Container(
      width: 68,
      height: 68,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: RadialGradient(colors: [Color.lerp(color, Colors.white, 0.35)!, color]),
        border: Border.all(color: Colors.white, width: 4),
        boxShadow: const [BoxShadow(color: Color(0x66000000), offset: Offset(0, 4), blurRadius: 5)],
      ),
      child: Center(
        child: widget.unlocked
            ? Text('${widget.n}', style: AppTheme.title(24))
            : const Icon(Icons.lock_rounded, color: Colors.white70),
      ),
    );
    if (_pulse != null) {
      node = ScaleTransition(scale: Tween(begin: 1.0, end: 1.12).animate(_pulse!), child: node);
    }
    return GestureDetector(
      onTap: widget.unlocked ? widget.onTap : null,
      child: SizedBox(
        width: 68,
        height: 86,
        child: Stack(clipBehavior: Clip.none, alignment: Alignment.topCenter, children: [
          node,
          if (widget.unlocked && widget.stars > 0) Positioned(bottom: 0, child: StarRow(stars: widget.stars, size: 20)),
        ]),
      ),
    );
  }
}
