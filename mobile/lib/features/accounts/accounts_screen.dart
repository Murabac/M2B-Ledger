import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../auth/providers/auth_controller.dart';
import '../status/providers/sync_status_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_theme.dart';
import '../../widgets/ui_kit.dart';
import 'models/account_balance.dart';
import 'providers/accounts_provider.dart';

class AccountsScreen extends ConsumerStatefulWidget {
  const AccountsScreen({super.key});

  @override
  ConsumerState<AccountsScreen> createState() => _AccountsScreenState();
}

class _AccountsScreenState extends ConsumerState<AccountsScreen> {
  String _query = '';
  String? _apiType;

  /// Display label → API `?type=` value (null = all).
  static const _types = <(String, String?)>[
    ('All', null),
    ('Bank', 'Bank'),
    ('A/R', 'AccountsReceivable'),
    ('A/P', 'AccountsPayable'),
    ('Income', 'Income'),
    ('Expense', 'Expense'),
  ];

  Future<void> _refresh() async {
    await Future.wait([
      ref.read(accountsProvider(_apiType).notifier).refresh(),
      ref.read(syncStatusProvider.notifier).refresh(),
    ]);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final accountsAsync = ref.watch(accountsProvider(_apiType));

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: AppTheme.overlayFor(theme.colorScheme.surface, lightIcons: theme.brightness == Brightness.dark),
      child: Scaffold(
        appBar: AppBar(
          title: Row(
            children: [
              BrandLogo.mark(height: 28),
              const SizedBox(width: 10),
              const Text('Accounts'),
            ],
          ),
          actions: [
            IconButton(
              tooltip: 'Log out',
              onPressed: () => ref.read(authControllerProvider.notifier).logout(),
              icon: const Icon(Icons.logout_rounded),
            ),
          ],
        ),
        body: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
              child: TextField(
                onChanged: (v) => setState(() => _query = v),
                decoration: InputDecoration(
                  hintText: 'Search accounts',
                  prefixIcon: const Icon(Icons.search_rounded),
                  filled: true,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(AppColors.radiusPill),
                    borderSide: BorderSide.none,
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(AppColors.radiusPill),
                    borderSide: BorderSide.none,
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(AppColors.radiusPill),
                    borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 10),
            SizedBox(
              height: 40,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                children: [
                  for (final t in _types) ...[
                    FilterChip(
                      label: Text(t.$1),
                      selected: _apiType == t.$2,
                      onSelected: (_) => setState(() => _apiType = t.$2),
                      showCheckmark: false,
                      selectedColor: AppColors.primaryContainer,
                      labelStyle: theme.textTheme.labelLarge?.copyWith(
                        color: _apiType == t.$2
                            ? AppColors.primaryDark
                            : theme.colorScheme.onSurfaceVariant,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(width: 8),
                  ],
                ],
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                color: AppColors.primary,
                onRefresh: _refresh,
                child: accountsAsync.when(
                  loading: () => ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: const [
                      SizedBox(height: 80),
                      SoftLoading(label: 'Loading accounts…'),
                    ],
                  ),
                  error: (error, _) => ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      SoftEmptyState(
                        icon: Icons.error_outline,
                        title: 'Could not load accounts',
                        subtitle: error.toString(),
                      ),
                    ],
                  ),
                  data: (cached) {
                    final q = _query.trim().toLowerCase();
                    final rows = cached.data.where((AccountBalance a) {
                      return q.isEmpty || a.fullName.toLowerCase().contains(q);
                    }).toList();

                    if (rows.isEmpty) {
                      return ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        children: [
                          if (cached.fromCache)
                            const Padding(
                              padding: EdgeInsets.fromLTRB(16, 12, 16, 0),
                              child: Align(
                                alignment: Alignment.centerLeft,
                                child: OfflineDataChip(),
                              ),
                            ),
                          const SizedBox(height: 80),
                          const SoftEmptyState(
                            icon: Icons.account_balance_outlined,
                            title: 'No accounts match',
                            subtitle: 'Try another search or filter.',
                          ),
                        ],
                      );
                    }

                    return ListView.separated(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                      itemCount: rows.length + (cached.fromCache ? 1 : 0),
                      separatorBuilder: (context, index) => const SizedBox(height: 10),
                      itemBuilder: (context, i) {
                        if (cached.fromCache && i == 0) {
                          return const Align(
                            alignment: Alignment.centerLeft,
                            child: OfflineDataChip(),
                          );
                        }
                        final row = rows[cached.fromCache ? i - 1 : i];
                        return M2bCard(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          child: Row(
                            children: [
                              InitialsAvatar(name: row.fullName),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      row.fullName,
                                      style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      row.accountType,
                                      style: theme.textTheme.bodySmall?.copyWith(
                                        color: theme.colorScheme.onSurfaceVariant,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              Text(
                                formatMoney(row.balance),
                                style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                              ),
                            ],
                          ),
                        );
                      },
                    );
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
