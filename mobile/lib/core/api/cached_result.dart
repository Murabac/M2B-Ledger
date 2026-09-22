class CachedResult<T> {
  const CachedResult({required this.data, this.fromCache = false});

  final T data;
  final bool fromCache;
}
