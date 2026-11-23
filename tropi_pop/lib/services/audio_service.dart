import 'package:audioplayers/audioplayers.dart';
import 'package:material_ui/material_ui.dart' show debugPrint;

/// Effets sonores et musique (fichiers générés par tool/generate_audio.py).
class AudioService {
  bool soundOn = true;
  bool musicOn = true;

  final _music = AudioPlayer();
  final Map<String, AudioPool> _pools = {};
  bool _musicStarted = false;
  bool _ready = false;

  static const _sfx = [
    'swap', 'invalid', 'pop0', 'pop1', 'pop2', 'pop3', 'pop4', 'pop5', 'bomb', 'line', 'rainbow', //
    'special', 'win', 'lose', 'tap', 'coin', 'star',
  ];

  Future<void> init({required bool sound, required bool music}) async {
    soundOn = sound;
    musicOn = music;
    try {
      for (final s in _sfx) {
        _pools[s] = await AudioPool.createFromAsset(path: 'audio/$s.wav', maxPlayers: 3);
      }
      await _music.setReleaseMode(ReleaseMode.loop);
      await _music.setVolume(0.45);
      _ready = true;
      if (musicOn) await startMusic();
    } catch (e) {
      // Pas d'audio disponible (tests, plateforme) : le jeu continue en silence.
      debugPrint('Audio indisponible : $e');
    }
  }

  void play(String name) {
    if (!soundOn || !_ready) return;
    _pools[name]?.start().then((_) {}, onError: (Object _) {});
  }

  /// Son de "pop" plus aigu à chaque cascade.
  void pop(int cascade) => play('pop${(cascade - 1).clamp(0, 5)}');

  Future<void> startMusic() async {
    if (!_ready || !musicOn) return;
    if (_musicStarted) {
      await _music.resume();
      return;
    }
    _musicStarted = true;
    await _music.play(AssetSource('audio/music.wav'));
  }

  Future<void> setMusic(bool on) async {
    musicOn = on;
    if (!_ready) return;
    on ? await startMusic() : await _music.pause();
  }

  Future<void> pauseAll() async => _ready ? _music.pause() : null;
  Future<void> resumeAll() async => musicOn ? startMusic() : null;
}
