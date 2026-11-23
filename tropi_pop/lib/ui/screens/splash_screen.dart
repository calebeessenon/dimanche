import 'package:material_ui/material_ui.dart';

import '../widgets/common.dart';
import '../widgets/logo.dart';
import 'home_screen.dart';

/// Écran de démarrage animé (après l'écran natif).
class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with SingleTickerProviderStateMixin {
  late final AnimationController _a = AnimationController(vsync: this, duration: const Duration(milliseconds: 1600))..forward();

  @override
  void initState() {
    super.initState();
    Future<void>.delayed(const Duration(milliseconds: 1900), () {
      if (!mounted) return;
      Navigator.of(context).pushReplacement(PageRouteBuilder<void>(
        transitionDuration: const Duration(milliseconds: 500),
        pageBuilder: (_, _, _) => const HomeScreen(),
        transitionsBuilder: (_, a, _, child) => FadeTransition(opacity: a, child: child),
      ));
    });
  }

  @override
  void dispose() {
    _a.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        body: SunsetBackground(
          child: Center(
            child: ScaleTransition(
              scale: CurvedAnimation(parent: _a, curve: Curves.elasticOut),
              child: const Padding(
                padding: EdgeInsets.all(24),
                child: FittedBox(fit: BoxFit.scaleDown, child: TropiLogo(size: 80)),
              ),
            ),
          ),
        ),
      );
}
