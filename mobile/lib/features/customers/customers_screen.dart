import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../auth/providers/auth_controller.dart';
import '../status/providers/sync_status_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_theme.dart';
import '../../widgets/ui_kit.dart';
import 'customer_detail_screen.dart';
import 'providers/customers_provider.dart';

class CustomersScreen extends ConsumerStatefulWidget {
  const CustomersScreen({super.key});

  @override
  ConsumerState<CustomersScreen> createState() => _CustomersScreenState();
}

class _CustomersScreenState extends ConsumerState<CustomersScreen> {
  String _searchInput = '';
  String _debouncedSearch = '';
  var _onlyWithBalance = false;
  var _page = 1;
  Timer? _debounce;

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }

  CustomersQuery get _query => CustomersQuery(
        search: _debouncedSearch,
        onlyWithBalance: _onlyWithBalance,
        page: _page,
      );

  void _onSearchChanged(String value) {
    setState(() => _searchInput = value);
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () {
      setState(() {
        _debouncedSearch = value.trim();
        _page = 1;
      });
    });
  }

  Future<void> _refresh() async {
    await Future.wait([
      ref.read(customersProvider(_query).notifier).refresh(),
      ref.read(syncStatusProvider.notifier).refresh(),
    ]);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final customersAsync = ref.watch(customersProvider(_query));

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: AppTheme.overlayFor(theme.colorScheme.surface, lightIcons: theme.brightness == Brightness.dark),
      child: Scaffold(
        appBar: AppBar(
          title: Row(
            children: [
              BrandLogo.mark(height: 28),
              const SizedBox(width: 10),
              const Text('Customers'),
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
                onChanged: _onSearchChanged,
                decoration: InputDecoration(
                  hintText: 'Search customers',
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
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(
                children: [
                  FilterChip(
                    label: const Text('Only with balance'),
                    selected: _onlyWithBalance,
                    onSelected: (v) => setState(() {
                      _onlyWithBalance = v;
                      _page = 1;
                    }),
                    showCheckmark: false,
                    selectedColor: AppColors.primaryContainer,
                  ),
                  const SizedBox(width: 8),
                  Chip(
                    avatar: Icon(Icons.sort_rounded, size: 16, color: theme.colorScheme.onSurfaceVariant),
                    label: const Text('Balance desc'),
                  ),
                ],
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                color: AppColors.primary,
                onRefresh: _refresh,
                child: customersAsync.when(
                  loading: () => ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: const [
                      SizedBox(height: 80),
                      SoftLoading(label: 'Loading customers…'),
                    ],
                  ),
                  error: (error, _) => ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      SoftEmptyState(
                        icon: Icons.error_outline,
                        title: 'Could not load customers',
                        subtitle: error.toString(),
                      ),
                    ],
                  ),
                  data: (cached) {
                    final page = cached.data;
                    final rows = page.items;

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
                          SoftEmptyState(
                            icon: Icons.people_outline,
                            title: 'No customers match',
                            subtitle: _searchInput.isEmpty && !_onlyWithBalance
                                ? 'Pull to refresh after a sync.'
                                : 'Try another search or clear filters.',
                          ),
                        ],
                      );
                    }

                    final extra = (cached.fromCache ? 1 : 0) + (page.hasMore || page.currentPage > 1 ? 1 : 0);

                    return ListView.separated(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                      itemCount: rows.length + extra,
                      separatorBuilder: (context, index) => const SizedBox(height: 10),
                      itemBuilder: (context, i) {
                        var index = i;
                        if (cached.fromCache) {
                          if (index == 0) {
                            return const Align(
                              alignment: Alignment.centerLeft,
                              child: OfflineDataChip(),
                            );
                          }
                          index--;
                        }
                        if (index < rows.length) {
                          final row = rows[index];
                          return M2bCard(
                            onTap: () {
                              Navigator.of(context).push(
                                MaterialPageRoute<void>(
                                  builder: (_) => CustomerDetailScreen(customerId: row.id),
                                ),
                              );
                            },
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
                                        row.salesRepName?.isNotEmpty == true
                                            ? row.salesRepName!
                                            : 'No sales rep',
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
                        }

                        return Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            if (page.currentPage > 1)
                              TextButton(
                                onPressed: () => setState(() => _page = page.currentPage - 1),
                                child: const Text('Previous'),
                              ),
                            if (page.hasMore)
                              TextButton(
                                onPressed: () => setState(() => _page = page.currentPage + 1),
                                child: const Text('Load more'),
                              ),
                          ],
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
