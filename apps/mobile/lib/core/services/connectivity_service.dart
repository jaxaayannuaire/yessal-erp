class ConnectivityService {
  bool _lastRequestSucceeded = true;

  bool get isLikelyOnline => _lastRequestSucceeded;

  void recordSuccess() => _lastRequestSucceeded = true;

  void recordFailure() => _lastRequestSucceeded = false;
}
