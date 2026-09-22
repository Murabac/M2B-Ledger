import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

/// Last successful JSON body per API path. Not encrypted (token is in secure storage).
class ResponseCache {
  ResponseCache({SharedPreferences? prefs}) : _prefsOverride = prefs;

  static const _prefix = 'api_cache:';

  final SharedPreferences? _prefsOverride;
  SharedPreferences? _prefs;

  Future<SharedPreferences> _ensure() async {
    return _prefs ??= _prefsOverride ?? await SharedPreferences.getInstance();
  }

  Future<void> write(String path, Map<String, dynamic> json) async {
    final prefs = await _ensure();
    await prefs.setString('$_prefix$path', jsonEncode(json));
  }

  Future<Map<String, dynamic>?> read(String path) async {
    final prefs = await _ensure();
    final raw = prefs.getString('$_prefix$path');
    if (raw == null || raw.isEmpty) {
      return null;
    }
    final decoded = jsonDecode(raw);
    if (decoded is Map<String, dynamic>) {
      return decoded;
    }
    if (decoded is Map) {
      return Map<String, dynamic>.from(decoded);
    }
    return null;
  }

  Future<void> clear() async {
    final prefs = await _ensure();
    final keys = prefs.getKeys().where((k) => k.startsWith(_prefix)).toList();
    for (final key in keys) {
      await prefs.remove(key);
    }
  }
}
