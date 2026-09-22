import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../auth/providers/auth_controller.dart';
import '../status/providers/sync_status_provider.dart';
import '../summary/providers/summary_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_theme.dart';
import '../../widgets/ui_kit.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  Future<void> _refresh(WidgetRef ref) async {
    await Future.wait([
      ref.read(summaryProvider.notifier).refresh(),
      ref.read(syncStatusProvider.notifier).refresh(),
    ]);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider).valueOrNull;
    final summaryAsync = ref.watch(summaryProvider);
    final theme = Theme.of(context);

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: AppTheme.overlayFor(theme.colorScheme.surface, lightIcons: theme.brightness == Brightness.dark),
      child: Scaffold(
        appBar: AppBar(
          titleSpacing: 16,
          title: Row(
            children: [
              BrandLogo.mark(height: 30),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Welcome back${user != null ? ', ${user.name.split(' ').first}' : ''}',
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                ),
              ),
            ],
          ),
          actions: [
            IconButton(
              tooltip: 'Refresh',
              onPressed: () => _refresh(ref),
              icon: const Icon(Icons.refresh_rounded),
            ),
            IconButton(
              tooltip: 'Log out',
              onPressed: () => ref.read(authControllerProvider.notifier).logout(),
              icon: const Icon(Icons.logout_rounded),
            ),
          ],
        ),
        body: RefreshIndicator(
          color: AppColors.primary,
          onRefresh: () => _refresh(ref),
          child: summaryAsync.when(
            loading: () => ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              children: const [
                SizedBox(height: 120),
                SoftLoading(label: 'Loading dashboard…'),
              ],
            ),
            error: (error, _) => ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                SoftEmptyState(
                  icon: Icons.error_outline,
                  title: 'Could not load dashboard',
                  subtitle: error.toString(),
                ),
              ],
            ),
            data: (cached) {
              final summary = cached.data;
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                children: [
                  Text(
                    'Dashboard',
                    style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Snapshot balances from your last sync.',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                  if (cached.fromCache) ...[
                    const SizedBox(height: 8),
                    const Align(
                      alignment: Alignment.centerLeft,
                      child: OfflineDataChip(),
                    ),
                  ],
                  const SizedBox(height: 20),
                  Row(
                    children: [
                      Expanded(
                        child: _SummaryCard(
                          label: 'Total A/R',
                          value: formatMoney(summary.totalAr),
                          icon: Icons.account_balance_wallet_outlined,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _SummaryCard(
                          label: 'With balance',
                          value: '${summary.customersWithBalance}',
                          icon: Icons.people_outline,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  M2bCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Top customer balances',
                          style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                        ),
                        const SizedBox(height: 12),
                        if (summary.topBalances.isEmpty)
                          Text(
                            'No customers with balances yet.',
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                            ),
                          )
                        else
                          for (final row in summary.topBalances)
                            Padding(
                              padding: const EdgeInsets.symmetric(vertical: 8),
                              child: Row(
                                children: [
                                  InitialsAvatar(name: row.fullName, size: 40),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Text(
                                      row.fullName,
                                      style: theme.textTheme.bodyLarge?.copyWith(fontWeight: FontWeight.w500),
                                    ),
                                  ),
                                  Text(
                                    formatMoney(row.balance),
                                    style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                                  ),
                                ],
                              ),
                            ),
                      ],
                    ),
                  ),
                  if (summary.hasKeyAccounts) ...[
                    const SizedBox(height: 12),
                    M2bCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Key accounts',
                            style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                          ),
                          const SizedBox(height: 8),
                          if (summary.keyAccounts!.isEmpty)
                            Text(
                              'No key accounts in this snapshot.',
                              style: theme.textTheme.bodyMedium?.copyWith(
                                color: theme.colorScheme.onSurfaceVariant,
                              ),
                            )
                          else
                            for (final row in summary.keyAccounts!)
                              ListTile(
                                contentPadding: EdgeInsets.zero,
                                leading: CircleAvatar(
                                  backgroundColor: theme.colorScheme.primaryContainer,
                                  child: Icon(
                                    Icons.account_balance_outlined,
                                    color: AppColors.primary,
                                    size: 20,
                                  ),
                                ),
                                title: Text(row.fullName),
                                subtitle: Text(row.accountType),
                                trailing: Text(
                                  formatMoney(row.balance),
                                  style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                                ),
                              ),
                        ],
                      ),
                    ),
                  ],
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({required this.label, required this.value, required this.icon});

  final String label;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return M2bCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: AppColors.primary, size: 22),
          const SizedBox(height: 12),
          Text(
            value,
            style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
          ),
        ],
      ),
    );
  }
}
