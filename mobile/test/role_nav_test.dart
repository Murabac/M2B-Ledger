import 'package:flutter_test/flutter_test.dart';
import 'package:qb_balances/features/auth/models/user_role.dart';
import 'package:qb_balances/routing/role_nav.dart';

void main() {
  test('owner sees Dashboard + Accounts + Customers', () {
    final dest = roleNavDestinations(UserRole.owner);
    expect(dest.map((d) => d.label).toList(), ['Home', 'Accounts', 'Customers']);
    expect(dest.map((d) => d.branch).toList(), [0, 1, 2]);
  });

  test('admin sees Dashboard + Accounts + Customers', () {
    final dest = roleNavDestinations(UserRole.admin);
    expect(dest.map((d) => d.label).toList(), ['Home', 'Accounts', 'Customers']);
  });

  test('sales_rep does not see Accounts or Dashboard', () {
    final dest = roleNavDestinations(UserRole.salesRep);
    expect(dest.map((d) => d.label).toList(), ['Customers']);
    expect(dest.single.branch, 2);
  });

  test('collections sees Accounts + Customers (no Dashboard)', () {
    final dest = roleNavDestinations(UserRole.collections);
    expect(dest.map((d) => d.label).toList(), ['Accounts', 'Customers']);
    expect(dest.map((d) => d.branch).toList(), [1, 2]);
  });
}
