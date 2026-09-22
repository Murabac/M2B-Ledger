import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../theme/app_colors.dart';
import '../../theme/app_theme.dart';
import '../../widgets/ui_kit.dart';
import 'providers/customers_provider.dart';

class CustomerDetailScreen extends ConsumerWidget {
  const CustomerDetailScreen({super.key, required this.customerId});

  final int customerId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final detailAsync = ref.watch(customerDetailProvider(customerId));

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: AppTheme.overlayFor(AppColors.chrome, lightIcons: true),
      child: Scaffold(
        body: detailAsync.when(
          loading: () => const Center(child: SoftLoading(label: 'Loading customer…')),
          error: (error, _) => Scaffold(
            appBar: AppBar(
              leading: IconButton(
                onPressed: () => Navigator.of(context).pop(),
                icon: const Icon(Icons.arrow_back_rounded),
              ),
            ),
            body: SoftEmptyState(
              icon: Icons.error_outline,
              title: 'Customer unavailable',
              subtitle: error.toString(),
            ),
          ),
          data: (cached) {
            final customer = cached.data;
            return Column(
              children: [
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.fromLTRB(8, 0, 8, 24),
                  decoration: const BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [AppColors.chrome, AppColors.primaryDark],
                    ),
                  ),
                  child: SafeArea(
                    bottom: false,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        IconButton(
                          onPressed: () => Navigator.of(context).pop(),
                          icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
                        ),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          child: Row(
                            children: [
                              InitialsAvatar(name: customer.fullName, size: 56),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      customer.fullName,
                                      style: theme.textTheme.headlineSmall?.copyWith(
                                        color: Colors.white,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    const SizedBox(height: 6),
                                    Text(
                                      formatMoney(customer.balance),
                                      style: theme.textTheme.headlineMedium?.copyWith(
                                        color: Colors.white,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                Expanded(
                  child: RefreshIndicator(
                    color: AppColors.primary,
                    onRefresh: () =>
                        ref.read(customerDetailProvider(customerId).notifier).refresh(),
                    child: ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(16),
                      children: [
                        if (cached.fromCache) ...[
                          const Align(
                            alignment: Alignment.centerLeft,
                            child: OfflineDataChip(),
                          ),
                          const SizedBox(height: 12),
                        ],
                        M2bCard(
                          child: Column(
                            children: [
                              _InfoRow(label: 'Balance', value: formatMoney(customer.balance)),
                              const Divider(height: 24),
                              _InfoRow(
                                label: 'Total balance',
                                value: formatMoney(customer.totalBalance),
                              ),
                              const Divider(height: 24),
                              _InfoRow(
                                label: 'Sales rep',
                                value: customer.salesRepName?.isNotEmpty == true
                                    ? customer.salesRepName!
                                    : '—',
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      children: [
        Expanded(
          child: Text(
            label,
            style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
          ),
        ),
        Text(
          value,
          style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
        ),
      ],
    );
  }
}
