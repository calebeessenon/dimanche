import 'models.dart';

/// Grille de jeu rectangulaire (les trous sont des cases non jouables).
class Board {
  final int width;
  final int height;
  final List<Cell> _cells;

  Board(this.width, this.height, List<Cell> cells) : _cells = cells {
    assert(cells.length == width * height);
  }

  bool inside(int x, int y) => x >= 0 && y >= 0 && x < width && y < height;

  Cell cell(int x, int y) => _cells[y * width + x];
  Cell at(Pos p) => cell(p.x, p.y);

  Tile? tileAt(int x, int y) => inside(x, y) ? cell(x, y).tile : null;

  /// Itère sur toutes les positions.
  Iterable<Pos> get positions sync* {
    for (var y = 0; y < height; y++) {
      for (var x = 0; x < width; x++) {
        yield Pos(x, y);
      }
    }
  }

  /// Couleur "assortissable" à une position (null si vide, caisse ou arc-en-ciel).
  TileColor? matchColor(int x, int y) {
    if (!inside(x, y)) return null;
    final c = cell(x, y);
    if (c.crate > 0) return null;
    final t = c.tile;
    if (t == null || t.isRainbow) return null;
    return t.color;
  }

  int get totalIce => _cells.fold(0, (s, c) => s + c.ice);
  int get totalCrates => _cells.fold(0, (s, c) => s + c.crate);

  Board clone() => Board(width, height, [for (final c in _cells) c.copy()]);

  /// Représentation texte (débogage / tests).
  String dump() {
    final sb = StringBuffer();
    for (var y = 0; y < height; y++) {
      for (var x = 0; x < width; x++) {
        final c = cell(x, y);
        if (!c.playable) {
          sb.write('#');
        } else if (c.crate > 0) {
          sb.write('X');
        } else if (c.tile == null) {
          sb.write('.');
        } else if (c.tile!.isRainbow) {
          sb.write('*');
        } else {
          sb.write('roygbp'[c.tile!.color!.index]);
        }
      }
      sb.writeln();
    }
    return sb.toString();
  }
}
