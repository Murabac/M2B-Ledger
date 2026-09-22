import '../../../core/api/money.dart';
import '../../accounts/models/account_balance.dart';
import '../../customers/models/customer_balance.dart';

class DashboardSummary {
  const DashboardSummary({
    required this.totalAr,
    required this.customersWithBalance,
    required this.topBalances,
    this.keyAccounts,
  });

  final double totalAr;
  final int customersWithBalance;
  final List<CustomerBalance> topBalances;
  final List<AccountBalance>? keyAccounts;

  bool get hasKeyAccounts => keyAccounts != null;

  factory DashboardSummary.fromJson(Map<String, dynamic> json) {
    final top = (json['top_balances'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((e) => CustomerBalance.fromJson(Map<String, dynamic>.from(e)))
        .toList();

    List<AccountBalance>? keys;
    if (json.containsKey('key_accounts')) {
      keys = (json['key_accounts'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((e) => AccountBalance.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    }

    return DashboardSummary(
      totalAr: parseMoney(json['total_ar']),
      customersWithBalance: _asInt(json['customers_with_balance']),
      topBalances: top,
      keyAccounts: keys,
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
