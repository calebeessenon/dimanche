import '../engine/models.dart';

/// Types d'objectifs de niveau.
enum ObjectiveType {
  /// Atteindre un score.
  score,

  /// Collecter N fruits d'une couleur.
  collect,

  /// Briser toutes les couches de glace.
  clearIce,

  /// Détruire toutes les caisses.
  clearCrates,
}

class Objective {
  final ObjectiveType type;
  final TileColor? color;
  final int target;

  const Objective(this.type, this.target, [this.color]);

  Map<String, dynamic> toJson() => {
        'type': type.name,
        'target': target,
        if (color != null) 'color': color!.name,
      };

  factory Objective.fromJson(Map<String, dynamic> j) => Objective(
        ObjectiveType.values.byName(j['type'] as String),
        j['target'] as int,
        j['color'] == null ? null : TileColor.values.byName(j['color'] as String),
      );
}

/// Définition déclarative d'un niveau.
///
/// `layout` décrit la grille, une chaîne par ligne :
/// - `.` case normale
/// - `#` trou (non jouable)
/// - `i` / `I` glace simple / double
/// - `c` / `C` caisse 1 PV / 2 PV
///
/// Les niveaux sont sérialisables en JSON : on peut donc en ajouter via un
/// fichier d'assets ou une configuration distante sans recompiler le moteur.
class LevelDefinition {
  final int number;
  final List<String> layout;
  final int moves;
  final int colorCount;
  final List<Objective> objectives;

  /// Seuils de score pour 1, 2 et 3 étoiles.
  final List<int> starScores;

  const LevelDefinition({
    required this.number,
    required this.layout,
    required this.moves,
    required this.colorCount,
    required this.objectives,
    required this.starScores,
  });

  int get height => layout.length;
  int get width => layout.first.length;

  Map<String, dynamic> toJson() => {
        'number': number,
        'layout': layout,
        'moves': moves,
        'colorCount': colorCount,
        'objectives': [for (final o in objectives) o.toJson()],
        'starScores': starScores,
      };

  factory LevelDefinition.fromJson(Map<String, dynamic> j) => LevelDefinition(
        number: j['number'] as int,
        layout: (j['layout'] as List).cast<String>(),
        moves: j['moves'] as int,
        colorCount: j['colorCount'] as int,
        objectives: [
          for (final o in j['objectives'] as List) Objective.fromJson(o as Map<String, dynamic>)
        ],
        starScores: (j['starScores'] as List).cast<int>(),
      );
}
