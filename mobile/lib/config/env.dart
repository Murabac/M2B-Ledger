/// Compile-time config via `--dart-define`.
class Env {
  static const _apiBaseUrlDefine = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://127.0.0.1:8000',
  );

  static const currency = String.fromEnvironment(
    'CURRENCY',
    defaultValue: 'USD',
  );

  static String get apiBaseUrl {
    var url = _apiBaseUrlDefine.trim();
    if (url.endsWith('/')) {
      return url.substring(0, url.length - 1);
    }
    return url;
  }
}
