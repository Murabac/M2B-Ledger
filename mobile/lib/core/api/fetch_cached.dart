import 'package:dio/dio.dart';

import '../storage/response_cache.dart';
import 'cached_result.dart';

typedef JsonParser<T> = T Function(Map<String, dynamic> json);

/// GET [path], cache successful JSON, fall back to cache on network failure.
Future<CachedResult<T>> fetchCachedJson<T>({
  required Dio dio,
  required ResponseCache cache,
  required String path,
  required JsonParser<T> parse,
  Map<String, dynamic>? queryParameters,
}) async {
  final cacheKey = _cacheKey(path, queryParameters);
  try {
    final response = await dio.get<Map<String, dynamic>>(
      path,
      queryParameters: queryParameters,
    );
    final data = response.data ?? <String, dynamic>{};
    await cache.write(cacheKey, data);
    return CachedResult(data: parse(data), fromCache: false);
  } on DioException {
    final cached = await cache.read(cacheKey);
    if (cached != null) {
      return CachedResult(data: parse(cached), fromCache: true);
    }
    rethrow;
  }
}

String _cacheKey(String path, Map<String, dynamic>? query) {
  if (query == null || query.isEmpty) {
    return path;
  }
  final entries = query.entries.toList()
    ..sort((a, b) => a.key.compareTo(b.key));
  final qs = entries
      .where((e) => e.value != null && '${e.value}'.isNotEmpty)
      .map((e) => '${e.key}=${e.value}')
      .join('&');
  return qs.isEmpty ? path : '$path?$qs';
}
