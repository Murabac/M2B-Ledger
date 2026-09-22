import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../features/auth/providers/auth_controller.dart';
import '../features/status/models/sync_status.dart';
import '../features/status/providers/sync_status_provider.dart';
import 'last_synced_banner.dart';

class AppShell extends ConsumerWidget {
  const AppShell({super.key, required this.navigationShell});

  final StatefulNavigationShell navigationShell;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider).valueOrNull;
    final statusAsync = ref.watch(syncStatusProvider);

    final destinations = <_Dest>[
      if (user?.role.canSeeDashboard ?? false)
        const _Dest(label: 'Home', icon: Icons.dashboard_outlined, branch: 0),
      if (user?.role.canSeeAccounts ?? false)
        const _Dest(label: 'Accounts', icon: Icons.account_balance_outlined, branch: 1),
      if (user?.role.canSeeCustomers ?? false)
        const _Dest(label: 'Customers', icon: Icons.people_outline, branch: 2),
    ];

    final visibleBranches = destinations.map((d) => d.branch).toList();
    var selected = visibleBranches.indexOf(navigationShell.currentIndex);
    if (selected < 0) {
      selected = 0;
    }

    return Scaffold(
      body: Column(
        children: [
          statusAsync.when(
            data: (status) => LastSyncedBanner(status: status),
            loading: () => const SizedBox.shrink(),
            error: (_, __) => const LastSyncedBanner(
              status: SyncStatus(syncedAt: null, isStale: true),
            ),
          ),
          Expanded(child: navigationShell),
        ],
      ),
      bottomNavigationBar: destinations.length < 2
          ? null
          : NavigationBar(
              selectedIndex: selected.clamp(0, destinations.length - 1),
              onDestinationSelected: (index) {
                navigationShell.goBranch(
                  destinations[index].branch,
                  initialLocation:
                      destinations[index].branch == navigationShell.currentIndex,
                );
              },
              destinations: [
                for (final d in destinations)
                  NavigationDestination(icon: Icon(d.icon), label: d.label),
              ],
            ),
    );
  }
}

class _Dest {
  const _Dest({required this.label, required this.icon, required this.branch});
  final String label;
  final IconData icon;
  final int branch;
}
