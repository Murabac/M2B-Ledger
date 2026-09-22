import 'package:dio/dio.dart';

/// Smoke: login against a running Wave 2 backend.
/// Run: dart run tool/login_smoke.dart
Future<void> main() async {
  const base = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://127.0.0.1:8000',
  );
  final dio = Dio(BaseOptions(
    baseUrl: base,
    headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
  ));

  final login = await dio.post<Map<String, dynamic>>(
    '/api/login',
    data: {
      'email': 'owner@demo.test',
      'password': 'Password123!',
    },
  );
  final token = login.data?['token'] as String?;
  if (token == null || token.isEmpty) {
    throw StateError('Login failed: no token');
  }

  final status = await dio.get<Map<String, dynamic>>(
    '/api/status',
    options: Options(headers: {'Authorization': 'Bearer $token'}),
  );
  // ignore: avoid_print
  print('login ok; status=${status.data}');
}
