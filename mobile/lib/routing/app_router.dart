import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../features/accounts/accounts_screen.dart';
import '../features/auth/login_screen.dart';
import '../features/auth/providers/auth_controller.dart';
import '../features/customers/customers_screen.dart';
import '../features/home/home_screen.dart';
import '../widgets/app_shell.dart';

final _rootKey = GlobalKey<NavigatorState>(debugLabel: 'root');

/// Stable router — do **not** `watch` auth here (that recreates GoRouter and breaks redirects).
final appRouterProvider = Provider<GoRouter>((ref) {
  final refresh = _AuthRefresh(ref);

  return GoRouter(
    navigatorKey: _rootKey,
    initialLocation: '/home',
    refreshListenable: refresh,
    redirect: (context, state) {
      final auth = ref.read(authControllerProvider);
      final loggingIn = state.matchedLocation == '/login';
      final user = auth.asData?.value;
      final loading = auth.isLoading && auth.asData == null && !auth.hasError;

      if (loading) {
        return null;
      }
      if (user == null) {
        return loggingIn ? null : '/login';
      }
      if (loggingIn) {
        return _homeForRole(user.role.canSeeDashboard, user.role.canSeeAccounts);
      }

      if (!user.role.canSeeDashboard && state.matchedLocation.startsWith('/home')) {
        return '/customers';
      }
      if (!user.role.canSeeAccounts && state.matchedLocation.startsWith('/accounts')) {
        return '/customers';
      }
      return null;
    },
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) =>
            AppShell(navigationShell: navigationShell),
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/home',
                builder: (context, state) => const HomeScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/accounts',
                builder: (context, state) => const AccountsScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/customers',
                builder: (context, state) => const CustomersScreen(),
              ),
            ],
          ),
        ],
      ),
    ],
  );
});

String _homeForRole(bool dashboard, bool accounts) {
  if (dashboard) {
    return '/home';
  }
  if (accounts) {
    return '/accounts';
  }
  return '/customers';
}

class _AuthRefresh extends ChangeNotifier {
  _AuthRefresh(this._ref) {
    _ref.listen(authControllerProvider, (previous, next) => notifyListeners());
  }

  final Ref _ref;
}
