import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../auth/providers/auth_controller.dart';
import '../../customers/models/customer_balance.dart';
import '../../../core/api/cached_result.dart';
import '../../../core/api/fetch_cached.dart';
import '../models/dashboard_summary.dart';

final summaryProvider =
    AsyncNotifierProvider<SummaryNotifier, CachedResult<DashboardSummary>>(
  SummaryNotifier.new,
);

class SummaryNotifier extends AsyncNotifier<CachedResult<DashboardSummary>> {
  static const path = '/api/summary';

  @override
  Future<CachedResult<DashboardSummary>> build() {
    ref.watch(authControllerProvider);
    return refresh();
  }

  Future<CachedResult<DashboardSummary>> refresh() async {
    final auth = ref.read(authControllerProvider).valueOrNull;
    if (auth == null) {
      const empty = CachedResult(
        data: DashboardSummary(
          totalAr: 0,
          customersWithBalance: 0,
          topBalances: <CustomerBalance>[],
        ),
      );
      state = const AsyncValue.data(empty);
      return empty;
    }

    final api = ref.read(authControllerProvider.notifier).apiClient;
    final cache = ref.read(responseCacheProvider);
    final result = await fetchCachedJson(
      dio: api.dio,
      cache: cache,
      path: path,
      parse: DashboardSummary.fromJson,
    );
    state = AsyncValue.data(result);
    return result;
  }
}
