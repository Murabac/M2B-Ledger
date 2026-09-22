import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../features/auth/providers/auth_controller.dart';
import '../features/status/models/sync_status.dart';
import '../features/status/providers/sync_status_provider.dart';
import '../routing/role_nav.dart';
import '../theme/app_colors.dart';
import 'last_synced_banner.dart';

class AppShell extends ConsumerWidget {
  const AppShell({super.key, required this.navigationShell});

  final StatefulNavigationShell navigationShell;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider).valueOrNull;
    final statusAsync = ref.watch(syncStatusProvider);
    final theme = Theme.of(context);

    final destinations = user == null
        ? const <RoleNavDestination>[]
        : roleNavDestinations(user.role);

    final visibleBranches = destinations.map((d) => d.branch).toList();
    var selected = visibleBranches.indexOf(navigationShell.currentIndex);
    if (selected < 0) {
      selected = 0;
    }

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            statusAsync.when(
              data: (status) => LastSyncedBanner(status: status),
              loading: () => const SizedBox.shrink(),
              error: (error, stackTrace) => const LastSyncedBanner(
                status: SyncStatus(syncedAt: null, isStale: true),
              ),
            ),
            Expanded(child: navigationShell),
          ],
        ),
      ),
      bottomNavigationBar: destinations.length < 2
          ? null
          : NavigationBar(
              selectedIndex: selected.clamp(0, destinations.length - 1),
              backgroundColor: theme.colorScheme.surface,
              indicatorColor: AppColors.primaryContainer,
              onDestinationSelected: (index) {
                navigationShell.goBranch(
                  destinations[index].branch,
                  initialLocation:
                      destinations[index].branch == navigationShell.currentIndex,
                );
              },
              destinations: [
                for (final d in destinations)
                  NavigationDestination(
                    icon: Icon(d.icon),
                    selectedIcon: Icon(d.icon, color: AppColors.primary),
                    label: d.label,
                  ),
              ],
            ),
    );
  }
}
