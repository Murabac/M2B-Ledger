import 'user_role.dart';

class AuthUser {
  const AuthUser({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.companyId,
    this.qbSalesRepName,
  });

  final int id;
  final String name;
  final String email;
  final UserRole role;
  final int companyId;
  final String? qbSalesRepName;

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    return AuthUser(
      id: _asInt(json['id']),
      name: json['name'] as String,
      email: json['email'] as String,
      role: UserRole.fromApi(json['role'] as String),
      companyId: _asInt(json['company_id']),
      qbSalesRepName: json['qb_sales_rep_name'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'role': role.apiValue,
        'company_id': companyId,
        'qb_sales_rep_name': qbSalesRepName,
      };

  static int _asInt(Object? value) {
    if (value is int) {
      return value;
    }
    if (value is num) {
      return value.toInt();
    }
    if (value is String) {
      return int.parse(value);
    }
    throw FormatException('Expected int, got $value');
  }
}
