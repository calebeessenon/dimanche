import 'package:material_ui/material_ui.dart';

/// Identité visuelle « Tropi Pop » : coucher de soleil tropical.
abstract final class AppColors {
  static const night = Color(0xFF2B1B5A); // violet nuit (fonds sombres)
  static const plum = Color(0xFF4A2380);
  static const pink = Color(0xFFFF4F8B); // rose hibiscus (action principale)
  static const mango = Color(0xFFFFB627); // mangue (monnaie, étoiles)
  static const lagoon = Color(0xFF2EC4B6); // lagon (succès, bonus)
  static const coral = Color(0xFFFF7E5F);
  static const cream = Color(0xFFFFF6E5);
  static const boardBg = Color(0xCC24124F);
  static const cellA = Color(0x33FFFFFF);
  static const cellB = Color(0x22FFFFFF);

  /// Dégradé de fond des écrans.
  static const sunset = LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [Color(0xFF3A1C71), Color(0xFFB0457C), Color(0xFFFF9A6B)],
    stops: [0, 0.6, 1],
  );
}

abstract final class AppTheme {
  static const font = 'Fredoka';

  static TextStyle title(double size, {Color color = Colors.white}) => TextStyle(
        fontFamily: font,
        fontSize: size,
        color: color,
        fontWeight: FontWeight.w700,
        fontVariations: const [FontVariation('wght', 650)],
        shadows: const [Shadow(color: Color(0x88000000), offset: Offset(0, 2), blurRadius: 3)],
      );

  static ThemeData build() {
    final base = ThemeData(
      useMaterial3: true,
      fontFamily: font,
      colorScheme: ColorScheme.fromSeed(seedColor: AppColors.pink, brightness: Brightness.dark),
    );
    return base.copyWith(
      scaffoldBackgroundColor: AppColors.night,
      textTheme: base.textTheme.apply(fontFamily: font, bodyColor: Colors.white, displayColor: Colors.white),
      dialogTheme: const DialogThemeData(backgroundColor: Colors.transparent, elevation: 0),
    );
  }
}
