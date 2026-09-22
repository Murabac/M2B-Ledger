class SyncStatus {
  const SyncStatus({
    required this.syncedAt,
    required this.isStale,
    this.fromCache = false,
  });

  final DateTime? syncedAt;
  final bool isStale;
  final bool fromCache;

  factory SyncStatus.fromJson(Map<String, dynamic> json, {bool fromCache = false}) {
    final raw = json['synced_at'];
    return SyncStatus(
      syncedAt: raw is String ? DateTime.tryParse(raw)?.toUtc() : null,
      isStale: json['is_stale'] as bool? ?? true,
      fromCache: fromCache,
    );
  }

  Map<String, dynamic> toJson() => {
        'synced_at': syncedAt?.toUtc().toIso8601String(),
        'is_stale': isStale,
      };
}
