import 'package:material_ui/material_ui.dart';

import '../../core/l10n/strings.dart';
import '../../core/theme/app_theme.dart';
import '../../services/app_services.dart';
import '../widgets/common.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final s = AppServices.of(context);
    final p = s.progress;
    return Scaffold(
      body: SunsetBackground(
        child: SafeArea(
          child: ListenableBuilder(
            listenable: p,
            builder: (context, _) => ListView(padding: const EdgeInsets.all(16), children: [
              Row(children: [
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.arrow_back_rounded, size: 32, color: Colors.white),
                ),
                Text(tr('settings'), style: AppTheme.title(30)),
              ]),
              const SizedBox(height: 12),
              _tile(Icons.volume_up_rounded, tr('sound'), Switch(
                value: p.data.sound,
                onChanged: (v) {
                  s.audio.soundOn = v;
                  p.updateSettings(sound: v);
                },
              )),
              _tile(Icons.music_note_rounded, tr('music'), Switch(
                value: p.data.music,
                onChanged: (v) {
                  s.audio.setMusic(v);
                  p.updateSettings(music: v);
                },
              )),
              _tile(Icons.notifications_rounded, tr('notifications'), Switch(
                value: p.data.notifications,
                onChanged: (v) => p.updateSettings(notifications: v),
              )),
              _tile(Icons.language_rounded, tr('language'), DropdownButton<String>(
                value: p.data.language,
                dropdownColor: AppColors.plum,
                underline: const SizedBox(),
                items: [
                  for (final e in S.languages.entries)
                    DropdownMenuItem(value: e.key, child: Text(e.value, style: AppTheme.title(16))),
                ],
                onChanged: (v) {
                  if (v == null) return;
                  S.setLanguage(v);
                  p.updateSettings(language: v);
                },
              )),
              _tile(Icons.privacy_tip_rounded, tr('privacy'), const Icon(Icons.chevron_right_rounded, color: Colors.white),
                  onTap: () => showPopDialog<void>(
                        context,
                        (ctx) => PopPanel(
                          title: tr('privacy'),
                          onClose: () => Navigator.pop(ctx),
                          child: Text(
                            'Tropi Pop enregistre votre progression sur votre appareil. '
                            'La politique complète (publicité, achats, sauvegarde cloud) est disponible '
                            'sur la fiche du Store et sera affichée ici dès la version publiée.',
                            style: AppTheme.title(15),
                          ),
                        ),
                      )),
              const SizedBox(height: 24),
              Center(child: Text('Tropi Pop v1.0.0', style: AppTheme.title(13, color: Colors.white54))),
            ]),
          ),
        ),
      ),
    );
  }

  Widget _tile(IconData icon, String label, Widget trailing, {VoidCallback? onTap}) => GestureDetector(
        onTap: onTap,
        child: Container(
          margin: const EdgeInsets.only(bottom: 10),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(
            color: const Color(0x66000000),
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: Colors.white24, width: 2),
          ),
          child: Row(children: [
            Icon(icon, color: AppColors.mango, size: 28),
            const SizedBox(width: 12),
            Expanded(child: Text(label, style: AppTheme.title(18))),
            trailing,
          ]),
        ),
      );
}
