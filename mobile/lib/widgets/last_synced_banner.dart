import 'package:flutter/material.dart';

import '../../features/status/models/sync_status.dart';
import '../theme/app_colors.dart';

/// Persistent last-synced chrome as a pill. Red/amber when stale.
class LastSyncedBanner extends StatelessWidget {
  const LastSyncedBanner({
    super.key,
    required this.status,
    this.now,
  });

  final SyncStatus status;
  final DateTime? now;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final dark = theme.brightness == Brightness.dark;
    final stale = status.isStale;

    // Keep readable contrast in both themes (seeded primaryContainer is too
    // dark in dark mode for AppColors.primaryDark text).
    final Color bg;
    final Color fg;
    final Color border;
    if (stale) {
      bg = dark ? const Color(0xFF3B2A0A) : AppColors.staleContainer;
      fg = dark ? const Color(0xFFFBBF24) : const Color(0xFF92400E);
      border = AppColors.stale.withValues(alpha: dark ? 0.55 : 0.35);
    } else {
      bg = dark ? const Color(0xFF14532D) : AppColors.primaryContainer;
      fg = dark ? const Color(0xFFBBF7D0) : AppColors.primaryDark;
      border = AppColors.primary.withValues(alpha: dark ? 0.45 : 0.25);
    }

    final label = [
      formatLastSynced(status.syncedAt, now: now ?? DateTime.now().toUtc()),
      if (stale) ' · Data may be out of date',
      if (status.fromCache) ' · Offline data',
    ].join();

    return Material(
      color: theme.scaffoldBackgroundColor,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
        child: Align(
          alignment: Alignment.centerLeft,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: bg,
              borderRadius: BorderRadius.circular(AppColors.radiusPill),
              border: Border.all(color: border),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  stale ? Icons.warning_amber_rounded : Icons.check_circle_outline,
                  color: fg,
                  size: 18,
                ),
                const SizedBox(width: 8),
                ConstrainedBox(
                  constraints: BoxConstraints(
                    maxWidth: MediaQuery.sizeOf(context).width - 72,
                  ),
                  child: Text(
                    label,
                    style: theme.textTheme.labelLarge?.copyWith(
                      color: fg,
                      fontWeight: FontWeight.w600,
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

  static String formatLastSynced(DateTime? syncedAt, {required DateTime now}) {
    if (syncedAt == null) {
      return 'Last synced unknown';
    }
    final utcNow = now.toUtc();
    final at = syncedAt.toUtc();
    final diff = utcNow.difference(at);
    if (diff.inSeconds < 60) {
      return 'Last synced just now';
    }
    if (diff.inMinutes < 60) {
      return 'Last synced ${diff.inMinutes} min ago';
    }
    return 'Last synced ${diff.inHours} hr ago';
  }
}
