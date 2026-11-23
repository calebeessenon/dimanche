/// Modèles de base du moteur Match-3 (Dart pur, sans dépendance Flutter).
///
/// Le moteur est volontairement indépendant de l'interface : il peut être
/// testé unitairement, simulé par un bot d'équilibrage, et rejoué côté serveur
/// pour valider un score.
library;

/// Les 6 fruits tropicaux du jeu. L'ordre est utilisé par les niveaux :
/// un niveau à 5 couleurs utilise les 5 premières.
enum TileColor { red, orange, yellow, green, blue, purple }

/// Types de bonus spéciaux.
enum SpecialType {
  none,

  /// Vide toute la ligne (créé par un alignement horizontal de 4).
  lineH,

  /// Vide toute la colonne (créé par un alignement vertical de 4).
  lineV,

  /// Explose en 3×3 (créé par un alignement en L ou en T).
  bomb,

  /// "Soleil prisme" : supprime toute une couleur (alignement de 5).
  rainbow,
}

/// Position dans la grille. `x` = colonne, `y` = ligne (0 en haut).
class Pos {
  final int x;
  final int y;
  const Pos(this.x, this.y);

  bool isAdjacent(Pos o) => (x - o.x).abs() + (y - o.y).abs() == 1;

  @override
  bool operator ==(Object other) => other is Pos && other.x == x && other.y == y;

  @override
  int get hashCode => x * 1000 + y;

  @override
  String toString() => '($x,$y)';
}

/// Un élément (fruit) posé sur une case.
class Tile {
  /// Identifiant unique : permet à l'interface de suivre et d'animer l'élément.
  final int id;

  /// Couleur ; `null` pour le soleil prisme.
  TileColor? color;
  SpecialType special;

  Tile(this.id, this.color, [this.special = SpecialType.none]);

  bool get isSpecial => special != SpecialType.none;
  bool get isRainbow => special == SpecialType.rainbow;

  Tile copy() => Tile(id, color, special);

  @override
  String toString() => 'Tile#$id(${color?.name ?? 'rainbow'},${special.name})';
}

/// Une case de la grille.
class Cell {
  /// `false` = trou dans le plateau (forme du niveau).
  final bool playable;

  /// Couches de glace sous l'élément (0, 1 ou 2). Détruites quand l'élément
  /// posé dessus est éliminé.
  int ice;

  /// Points de vie d'une caisse en bois (0 = pas de caisse). Une caisse
  /// occupe la case, ne tombe pas, et est endommagée par les combinaisons
  /// adjacentes et les explosions.
  int crate;

  Tile? tile;

  Cell({required this.playable, this.ice = 0, this.crate = 0, this.tile});

  /// La case peut-elle accueillir un élément mobile ?
  bool get canHoldTile => playable && crate == 0;

  Cell copy() => Cell(playable: playable, ice: ice, crate: crate, tile: tile?.copy());
}
