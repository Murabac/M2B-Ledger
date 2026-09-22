import 'package:flutter/material.dart';

import '../features/auth/models/user_role.dart';

/// Bottom-nav destinations for a role. Branch indexes match [appRouterProvider]
/// shell branches: 0=home, 1=accounts, 2=customers.
class RoleNavDestination {
  const RoleNavDestination({
    required this.label,
    required this.icon,
    required this.branch,
  });

  final String label;
  final IconData icon;
  final int branch;
}

List<RoleNavDestination> roleNavDestinations(UserRole role) {
  return [
    if (role.canSeeDashboard)
      const RoleNavDestination(
        label: 'Home',
        icon: Icons.dashboard_outlined,
        branch: 0,
      ),
    if (role.canSeeAccounts)
      const RoleNavDestination(
        label: 'Accounts',
        icon: Icons.account_balance_outlined,
        branch: 1,
      ),
    if (role.canSeeCustomers)
      const RoleNavDestination(
        label: 'Customers',
        icon: Icons.people_outline,
        branch: 2,
      ),
  ];
}
