enum UserRole {
  owner,
  salesRep,
  collections,
  admin;

  static UserRole fromApi(String value) {
    switch (value) {
      case 'owner':
        return UserRole.owner;
      case 'sales_rep':
        return UserRole.salesRep;
      case 'collections':
        return UserRole.collections;
      case 'admin':
        return UserRole.admin;
      default:
        throw ArgumentError.value(value, 'value', 'Unknown role');
    }
  }

  String get apiValue => switch (this) {
        UserRole.owner => 'owner',
        UserRole.salesRep => 'sales_rep',
        UserRole.collections => 'collections',
        UserRole.admin => 'admin',
      };

  bool get canSeeDashboard => this == UserRole.owner || this == UserRole.admin;

  bool get canSeeAccounts =>
      this == UserRole.owner ||
      this == UserRole.admin ||
      this == UserRole.collections;

  bool get canSeeCustomers => true;
}
