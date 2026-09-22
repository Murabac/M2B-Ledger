import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../auth/providers/auth_controller.dart';
import '../models/sync_status.dart';

final syncStatusProvider =
    AsyncNotifierProvider<SyncStatusNotifier, SyncStatus>(SyncStatusNotifier.new);

class SyncStatusNotifier extends AsyncNotifier<SyncStatus> {
  static const cachePath = '/api/status';

  @override
  Future<SyncStatus> build() {
    // Rebuild when session changes (login / logout).
    ref.watch(authControllerProvider);
    return refresh();
  }

  Future<SyncStatus> refresh() async {
    final auth = ref.read(authControllerProvider).valueOrNull;
    if (auth == null) {
      return const SyncStatus(syncedAt: null, isStale: true);
    }

    final api = ref.read(authControllerProvider.notifier).apiClient;
    final cache = ref.read(responseCacheProvider);

    try {
      final response = await api.dio.get<Map<String, dynamic>>(cachePath);
      final data = response.data ?? {};
      await cache.write(cachePath, data);
      final status = SyncStatus.fromJson(data);
      state = AsyncValue.data(status);
      return status;
    } on DioException {
      final cached = await cache.read(cachePath);
      if (cached != null) {
        final status = SyncStatus.fromJson(cached, fromCache: true);
        state = AsyncValue.data(status);
        return status;
      }
      rethrow;
    }
  }
}
