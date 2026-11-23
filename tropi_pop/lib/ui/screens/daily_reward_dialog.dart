import 'package:material_ui/material_ui.dart';

import '../../core/l10n/strings.dart';
import '../../core/theme/app_theme.dart';
import '../../data/economy.dart';
import '../../services/app_services.dart';
import '../widgets/common.dart';

Future<void> showDailyReward(BuildContext context) => showPopDialog<void>(context, (ctx) => const _DailyRewardPanel());

class _DailyRewardPanel extends StatefulWidget {
  const _DailyRewardPanel();
  @override
  State<_DailyRewardPanel> createState() => _DailyRewardPanelState();
}

class _DailyRewardPanelState extends State<_DailyRewardPanel> {
  @override
  Widget build(BuildContext context) {
    final s = AppServices.of(context);
    final p = s.progress;
    final today = p.dailyIndex;
    final can = p.canClaimDaily;
    return PopPanel(
      title: tr('daily_reward'),
      onClose: () => Navigator.pop(context),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Wrap(
          alignment: WrapAlignment.center,
          spacing: 8,
          runSpacing: 8,
          children: [
            for (var i = 0; i < Economy.dailyRewards.length; i++)
              _DayTile(
                day: i + 1,
                reward: Economy.dailyRewards[i],
                state: i < today || (i == today && !can) ? _DayState.done : (i == today ? _DayState.today : _DayState.future),
              ),
          ],
        ),
        const SizedBox(height: 18),
        PopButton(
          label: can ? tr('claim') : tr('claimed'),
          color: AppColors.lagoon,
          onTap: can
              ? () async {
                  await p.claimDaily();
                  s.audio.play('coin');
                  if (context.mounted) setState(() {});
                }
              : null,
        ),
      ]),
    );
  }
}

enum _DayState { done, today, future }

class _DayTile extends StatelessWidget {
  final int day;
  final DailyReward reward;
  final _DayState state;
  const _DayTile({required this.day, required this.reward, required this.state});

  @override
  Widget build(BuildContext context) {
    final big = day == 7;
    return Container(
      width: big ? 160 : 76,
      height: 92,
      padding: const EdgeInsets.all(6),
      decoration: BoxDecoration(
        color: state == _DayState.today ? AppColors.mango.withValues(alpha: 0.35) : const Color(0x33000000),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: state == _DayState.today ? AppColors.mango : Colors.white24, width: 3),
      ),
      child: Stack(children: [
        Column(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text(tr('day', {'n': day}), style: AppTheme.title(12)),
          Icon(big ? Icons.redeem_rounded : Icons.monetization_on_rounded, color: AppColors.mango, size: 28),
          Text(
            '${reward.coins}${reward.boosters.isNotEmpty ? ' +${reward.boosters.values.fold(0, (a, b) => a + b)}🎁' : ''}',
            style: AppTheme.title(13),
          ),
        ]),
        if (state == _DayState.done)
          const Center(child: Icon(Icons.check_circle_rounded, color: AppColors.lagoon, size: 40)),
      ]),
    );
  }
}
