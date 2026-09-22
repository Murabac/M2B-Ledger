import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qb_balances/features/status/models/sync_status.dart';
import 'package:qb_balances/theme/app_colors.dart';
import 'package:qb_balances/widgets/last_synced_banner.dart';

void main() {
  testWidgets('stale banner turns amber/red when is_stale=true', (tester) async {
    final stale = SyncStatus(
      syncedAt: DateTime.utc(2026, 9, 22, 8, 0, 0),
      isStale: true,
    );

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: LastSyncedBanner(
            status: stale,
            now: DateTime.utc(2026, 9, 22, 8, 30, 0),
          ),
        ),
      ),
    );

    expect(find.textContaining('Data may be out of date'), findsOneWidget);
    expect(find.textContaining('Last synced'), findsOneWidget);

    final container = tester.widget<Container>(
      find.descendant(
        of: find.byType(LastSyncedBanner),
        matching: find.byType(Container),
      ).first,
    );
    final decoration = container.decoration! as BoxDecoration;
    expect(decoration.color, AppColors.staleContainer);
  });

  testWidgets('fresh banner uses green container', (tester) async {
    final fresh = SyncStatus(
      syncedAt: DateTime.utc(2026, 9, 22, 8, 28, 0),
      isStale: false,
    );

    await tester.pumpWidget(
      MaterialApp(
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: AppColors.primary),
          useMaterial3: true,
        ),
        home: Scaffold(
          body: LastSyncedBanner(
            status: fresh,
            now: DateTime.utc(2026, 9, 22, 8, 30, 0),
          ),
        ),
      ),
    );

    expect(find.textContaining('Data may be out of date'), findsNothing);
    expect(find.text('Last synced 2 min ago'), findsOneWidget);

    final container = tester.widget<Container>(
      find.descendant(
        of: find.byType(LastSyncedBanner),
        matching: find.byType(Container),
      ).first,
    );
    final decoration = container.decoration! as BoxDecoration;
    expect(decoration.color, AppColors.primaryContainer);
  });

  test('formatLastSynced copy', () {
    final now = DateTime.utc(2026, 9, 22, 12, 0, 0);
    expect(
      LastSyncedBanner.formatLastSynced(now.subtract(const Duration(seconds: 20)), now: now),
      'Last synced just now',
    );
    expect(
      LastSyncedBanner.formatLastSynced(now.subtract(const Duration(minutes: 5)), now: now),
      'Last synced 5 min ago',
    );
    expect(
      LastSyncedBanner.formatLastSynced(now.subtract(const Duration(hours: 3)), now: now),
      'Last synced 3 hr ago',
    );
  });
}
