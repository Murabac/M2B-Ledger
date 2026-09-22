import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/storage/response_cache.dart';
import '../../../core/storage/token_storage.dart';
import '../models/auth_user.dart';

final tokenStorageProvider = Provider<TokenStorage>((ref) => TokenStorage());

final responseCacheProvider = Provider<ResponseCache>((ref) => ResponseCache());

final authControllerProvider =
    StateNotifierProvider<AuthController, AsyncValue<AuthUser?>>(
  (ref) => AuthController(ref),
);

class AuthController extends StateNotifier<AsyncValue<AuthUser?>> {
  AuthController(this._ref) : super(const AsyncValue.loading()) {
    _restore();
  }

  final Ref _ref;
  late final ApiClient _api = ApiClient(
    tokenStorage: _ref.read(tokenStorageProvider),
    onUnauthorized: () {
      // Fire-and-forget; avoid awaiting in interceptor.
      logout();
    },
  );

  ApiClient get apiClient => _api;

  Future<void> _restore() async {
    try {
      final storage = _ref.read(tokenStorageProvider);
      final token = await storage.readToken();
      final userJson = await storage.readUserJson();
      if (token == null || token.isEmpty || userJson == null) {
        state = const AsyncValue.data(null);
        return;
      }
      final decoded = jsonDecode(userJson) as Map<String, dynamic>;
      state = AsyncValue.data(AuthUser.fromJson(decoded));
    } catch (error, stack) {
      state = AsyncValue.error(error, stack);
    }
  }

  Future<void> login({required String email, required String password}) async {
    // Keep prior session visible to the router while the request is in flight.
    // (Setting AsyncLoading() clears asData and can bounce redirect back to /login.)
    try {
      final response = await _api.dio.post<Map<String, dynamic>>(
        '/api/login',
        data: {'email': email, 'password': password},
      );
      final data = response.data ?? {};
      final token = data['token'] as String?;
      final userMap = data['user'] as Map<String, dynamic>?;
      if (token == null || userMap == null) {
        throw StateError('Login response missing token or user.');
      }
      final user = AuthUser.fromJson(userMap);
      final storage = _ref.read(tokenStorageProvider);
      await storage.saveToken(token);
      await storage.saveUserJson(jsonEncode(user.toJson()));
      state = AsyncValue.data(user);
    } on DioException catch (error, stack) {
      final message = _dioMessage(error);
      state = AsyncValue.error(message, stack);
      throw Exception(message);
    } catch (error, stack) {
      state = AsyncValue.error(error, stack);
      rethrow;
    }
  }

  Future<void> logout() async {
    try {
      await _api.dio.post('/api/logout');
    } catch (_) {
      // Still clear local session.
    }
    await _ref.read(tokenStorageProvider).clear();
    await _ref.read(responseCacheProvider).clear();
    state = const AsyncValue.data(null);
  }

  static String _dioMessage(DioException error) {
    if (error.type == DioExceptionType.connectionError ||
        error.type == DioExceptionType.connectionTimeout) {
      return 'Cannot reach API at ${error.requestOptions.uri.origin}. '
          'Physical phone: run `adb reverse tcp:8000 tcp:8000` (USB) or use your PC LAN IP. '
          'Emulator: use http://10.0.2.2:8000. Is php artisan serve running?';
    }
    final data = error.response?.data;
    if (data is Map && data['message'] is String) {
      return data['message'] as String;
    }
    if (data is Map && data['errors'] is Map) {
      final errors = data['errors'] as Map;
      final first = errors.values.cast<dynamic>().firstOrNull;
      if (first is List && first.isNotEmpty) {
        return first.first.toString();
      }
    }
    return error.message ?? 'Login failed';
  }
}
