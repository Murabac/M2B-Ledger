import '../../../core/api/money.dart';

class AccountBalance {
  const AccountBalance({
    required this.id,
    required this.fullName,
    required this.accountType,
    required this.balance,
    required this.totalBalance,
    this.qbListId,
    this.isActive = true,
  });

  final int id;
  final String? qbListId;
  final String fullName;
  final String accountType;
  final double balance;
  final double totalBalance;
  final bool isActive;

  factory AccountBalance.fromJson(Map<String, dynamic> json) {
    return AccountBalance(
      id: _asInt(json['id']),
      qbListId: json['qb_list_id'] as String?,
      fullName: json['full_name'] as String? ?? '',
      accountType: json['account_type'] as String? ?? '',
      balance: parseMoney(json['balance']),
      totalBalance: parseMoney(json['total_balance'] ?? json['balance']),
      isActive: json['is_active'] as bool? ?? true,
    );
  }

  static int _asInt(Object? value) {
    if (value is int) {
      return value;
    }
    if (value is num) {
      return value.toInt();
    }
    if (value is String) {
      return int.tryParse(value) ?? 0;
    }
    return 0;
  }
}
