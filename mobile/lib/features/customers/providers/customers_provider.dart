import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../auth/providers/auth_controller.dart';
import '../../../core/api/cached_result.dart';
import '../../../core/api/fetch_cached.dart';
import '../models/customer_balance.dart';

class CustomersQuery {
  const CustomersQuery({
    this.search = '',
    this.onlyWithBalance = false,
    this.page = 1,
  });

  final String search;
  final bool onlyWithBalance;
  final int page;

  @override
  bool operator ==(Object other) =>
      other is CustomersQuery &&
      other.search == search &&
      other.onlyWithBalance == onlyWithBalance &&
      other.page == page;

  @override
  int get hashCode => Object.hash(search, onlyWithBalance, page);
}

final customersProvider = AsyncNotifierProvider.family<CustomersNotifier,
    CachedResult<CustomerPage>, CustomersQuery>(CustomersNotifier.new);

class CustomersNotifier
    extends FamilyAsyncNotifier<CachedResult<CustomerPage>, CustomersQuery> {
  static const path = '/api/customers';

  @override
  Future<CachedResult<CustomerPage>> build(CustomersQuery arg) {
    ref.watch(authControllerProvider);
    return refresh();
  }

  Future<CachedResult<CustomerPage>> refresh() async {
    final auth = ref.read(authControllerProvider).valueOrNull;
    if (auth == null) {
      const empty = CachedResult(
        data: CustomerPage(items: [], currentPage: 1, lastPage: 1, total: 0),
      );
      state = const AsyncValue.data(empty);
      return empty;
    }

    final q = arg;
    final api = ref.read(authControllerProvider.notifier).apiClient;
    final cache = ref.read(responseCacheProvider);
    final params = <String, dynamic>{
      'sort': 'balance_desc',
      'page': q.page,
      if (q.search.trim().isNotEmpty) 'search': q.search.trim(),
      if (q.onlyWithBalance) 'only_with_balance': 1,
    };

    final result = await fetchCachedJson(
      dio: api.dio,
      cache: cache,
      path: path,
      queryParameters: params,
      parse: CustomerPage.fromJson,
    );
    state = AsyncValue.data(result);
    return result;
  }
}

final customerDetailProvider = AsyncNotifierProvider.family<
    CustomerDetailNotifier, CachedResult<CustomerBalance>, int>(
  CustomerDetailNotifier.new,
);

class CustomerDetailNotifier
    extends FamilyAsyncNotifier<CachedResult<CustomerBalance>, int> {
  @override
  Future<CachedResult<CustomerBalance>> build(int arg) {
    ref.watch(authControllerProvider);
    return refresh();
  }

  Future<CachedResult<CustomerBalance>> refresh() async {
    final id = arg;
    final auth = ref.read(authControllerProvider).valueOrNull;
    if (auth == null) {
      throw StateError('Not authenticated');
    }

    final path = '/api/customers/$id';
    final api = ref.read(authControllerProvider.notifier).apiClient;
    final cache = ref.read(responseCacheProvider);
    final result = await fetchCachedJson(
      dio: api.dio,
      cache: cache,
      path: path,
      parse: (json) {
        final data = json['data'];
        if (data is Map) {
          return CustomerBalance.fromJson(Map<String, dynamic>.from(data));
        }
        return CustomerBalance.fromJson(json);
      },
    );
    state = AsyncValue.data(result);
    return result;
  }
}
