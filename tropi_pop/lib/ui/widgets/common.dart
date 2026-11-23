import 'dart:math';

import 'package:material_ui/material_ui.dart';

import '../../core/theme/app_theme.dart';
import '../../services/app_services.dart';

/// Fond dégradé « coucher de soleil » avec palmiers stylisés.
class SunsetBackground extends StatelessWidget {
  final Widget child;
  const SunsetBackground({super.key, required this.child});

  @override
  Widget build(BuildContext context) => DecoratedBox(
    decoration: const BoxDecoration(gradient: AppColors.sunset),
    child: CustomPaint(painter: _DecorPainter(), child: child),
  );
}

class _DecorPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    // Soleil.
    final sun = Offset(size.width * 0.78, size.height * 0.16);
    canvas.drawCircle(sun, size.width * 0.22, Paint()..color = const Color(0x22FFE6A0));
    canvas.drawCircle(sun, size.width * 0.14, Paint()..color = const Color(0x33FFE6A0));
    // Collines / vagues.
    final wave = Path()..moveTo(0, size.height);
    for (var x = 0.0; x <= size.width; x += 8) {
      wave.lineTo(x, size.height * 0.9 + sin(x / size.width * pi * 3) * 14);
    }
    wave
      ..lineTo(size.width, size.height)
      ..close();
    canvas.drawPath(wave, Paint()..color = const Color(0x332EC4B6));
    // Bulles.
    final rnd = Random(3);
    for (var i = 0; i < 18; i++) {
      canvas.drawCircle(
        Offset(rnd.nextDouble() * size.width, rnd.nextDouble() * size.height),
        2 + rnd.nextDouble() * 6,
        Paint()..color = Colors.white.withValues(alpha: 0.06 + rnd.nextDouble() * 0.08),
      );
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// Bouton « bonbon » brillant, adapté au tactile (≥ 48 px).
class PopButton extends StatefulWidget {
  final String label;
  final VoidCallback? onTap;
  final Color color;
  final IconData? icon;
  final double fontSize;
  final EdgeInsets padding;
  const PopButton({
    super.key,
    required this.label,
    this.onTap,
    this.color = AppColors.pink,
    this.icon,
    this.fontSize = 22,
    this.padding = const EdgeInsets.symmetric(horizontal: 28, vertical: 14),
  });

  @override
  State<PopButton> createState() => _PopButtonState();
}

class _PopButtonState extends State<PopButton> {
  bool _down = false;

  @override
  Widget build(BuildContext context) {
    final enabled = widget.onTap != null;
    final col = enabled ? widget.color : Colors.grey;
    return GestureDetector(
      onTapDown: enabled ? (_) => setState(() => _down = true) : null,
      onTapCancel: () => setState(() => _down = false),
      onTapUp: enabled
          ? (_) {
              setState(() => _down = false);
              AppServices.of(context).audio.play('tap');
              widget.onTap!();
            }
          : null,
      child: AnimatedScale(
        scale: _down ? 0.93 : 1,
        duration: const Duration(milliseconds: 90),
        child: Container(
          constraints: const BoxConstraints(minHeight: 48),
          padding: widget.padding,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(40),
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [Color.lerp(col, Colors.white, 0.35)!, col, Color.lerp(col, Colors.black, 0.2)!],
            ),
            border: Border.all(color: Colors.white.withValues(alpha: 0.7), width: 3),
            boxShadow: const [BoxShadow(color: Color(0x55000000), offset: Offset(0, 5), blurRadius: 6)],
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (widget.icon != null) ...[
                Icon(widget.icon, color: Colors.white, size: widget.fontSize + 2),
                const SizedBox(width: 8),
              ],
              Flexible(
                child: Text(widget.label, style: AppTheme.title(widget.fontSize), textAlign: TextAlign.center),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Pastille d'information (vies, pièces…).
class StatPill extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String value;
  final String? sub;
  final VoidCallback? onPlus;
  const StatPill({super.key, required this.icon, required this.iconColor, required this.value, this.sub, this.onPlus});

  @override
  Widget build(BuildContext context) => GestureDetector(
    onTap: onPlus,
    child: Container(
      padding: const EdgeInsets.fromLTRB(6, 4, 10, 4),
      decoration: BoxDecoration(
        color: const Color(0x66000000),
        borderRadius: BorderRadius.circular(30),
        border: Border.all(color: Colors.white24, width: 2),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: iconColor, size: 26),
          const SizedBox(width: 6),
          Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(value, style: AppTheme.title(18)),
              if (sub != null) Text(sub!, style: AppTheme.title(10, color: Colors.white70)),
            ],
          ),
          if (onPlus != null) ...[
            const SizedBox(width: 6),
            Container(
              decoration: const BoxDecoration(color: AppColors.lagoon, shape: BoxShape.circle),
              child: const Icon(Icons.add, size: 18, color: Colors.white),
            ),
          ],
        ],
      ),
    ),
  );
}

/// Panneau de dialogue arrondi.
class PopPanel extends StatelessWidget {
  final String title;
  final Widget child;
  final VoidCallback? onClose;
  const PopPanel({super.key, required this.title, required this.child, this.onClose});

  @override
  Widget build(BuildContext context) => Center(
    child: ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 420),
      child: Material(
        type: MaterialType.transparency,
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              Container(
                margin: const EdgeInsets.only(top: 26),
                padding: const EdgeInsets.fromLTRB(20, 40, 20, 20),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Color(0xFF6A35B8), AppColors.night],
                  ),
                  borderRadius: BorderRadius.circular(28),
                  border: Border.all(color: Colors.white.withValues(alpha: 0.8), width: 3),
                  boxShadow: const [BoxShadow(color: Color(0x88000000), blurRadius: 20, offset: Offset(0, 8))],
                ),
                child: child,
              ),
              Positioned(
                top: 0,
                left: 30,
                right: 30,
                child: Container(
                  height: 52,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(colors: [AppColors.pink, AppColors.coral]),
                    borderRadius: BorderRadius.circular(26),
                    border: Border.all(color: Colors.white, width: 3),
                  ),
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: FittedBox(
                    fit: BoxFit.scaleDown,
                    child: Text(title, style: AppTheme.title(24)),
                  ),
                ),
              ),
              if (onClose != null)
                Positioned(
                  top: 14,
                  right: -6,
                  child: GestureDetector(
                    onTap: onClose,
                    child: Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: AppColors.coral,
                        shape: BoxShape.circle,
                        border: Border.all(color: Colors.white, width: 3),
                      ),
                      child: const Icon(Icons.close, color: Colors.white),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    ),
  );
}

/// Rangée d'étoiles.
class StarRow extends StatelessWidget {
  final int stars;
  final double size;
  const StarRow({super.key, required this.stars, this.size = 18});

  @override
  Widget build(BuildContext context) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      for (var i = 0; i < 3; i++)
        Icon(Icons.star_rounded, size: size, color: i < stars ? AppColors.mango : Colors.black38),
    ],
  );
}

/// Affiche un dialogue avec animation d'apparition.
Future<T?> showPopDialog<T>(BuildContext context, Widget Function(BuildContext) builder, {bool dismissible = true}) =>
    showGeneralDialog<T>(
      context: context,
      barrierDismissible: dismissible,
      barrierLabel: 'dialog',
      barrierColor: const Color(0xAA000000),
      transitionDuration: const Duration(milliseconds: 280),
      pageBuilder: (ctx, _, _) => builder(ctx),
      transitionBuilder: (ctx, a, _, child) => ScaleTransition(
        scale: CurvedAnimation(parent: a, curve: Curves.easeOutBack),
        child: FadeTransition(opacity: a, child: child),
      ),
    );
