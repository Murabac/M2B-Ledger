import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../auth/providers/auth_controller.dart';
import '../../../core/api/cached_result.dart';
import '../../../core/api/fetch_cached.dart';
import '../models/account_balance.dart';

final accountsProvider = AsyncNotifierProvider.family<AccountsNotifier,
    CachedResult<List<AccountBalance>>, String?>(AccountsNotifier.new);

class AccountsNotifier
    extends FamilyAsyncNotifier<CachedResult<List<AccountBalance>>, String?> {
  static const path = '/api/accounts';

  @override
  Future<CachedResult<List<AccountBalance>>> build(String? arg) {
    ref.watch(authControllerProvider);
    return refresh();
  }

  Future<CachedResult<List<AccountBalance>>> refresh() async {
    final auth = ref.read(authControllerProvider).valueOrNull;
    if (auth == null) {
      const empty = CachedResult(data: <AccountBalance>[]);
      state = const AsyncValue.data(empty);
      return empty;
    }

    final type = arg;
    final api = ref.read(authControllerProvider.notifier).apiClient;
    final cache = ref.read(responseCacheProvider);
    final result = await fetchCachedJson(
      dio: api.dio,
      cache: cache,
      path: path,
      queryParameters: type == null || type.isEmpty ? null : {'type': type},
      parse: (json) {
        final raw = json['data'];
        if (raw is! List) {
          return <AccountBalance>[];
        }
        return raw
            .whereType<Map>()
            .map((e) => AccountBalance.fromJson(Map<String, dynamic>.from(e)))
            .toList();
      },
    );
    state = AsyncValue.data(result);
    return result;
  }
}
