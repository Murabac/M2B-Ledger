import '../../../core/api/money.dart';

class CustomerBalance {
  const CustomerBalance({
    required this.id,
    required this.fullName,
    required this.balance,
    required this.totalBalance,
    this.qbListId,
    this.salesRepName,
    this.isActive = true,
  });

  final int id;
  final String? qbListId;
  final String fullName;
  final double balance;
  final double totalBalance;
  final String? salesRepName;
  final bool isActive;

  factory CustomerBalance.fromJson(Map<String, dynamic> json) {
    return CustomerBalance(
      id: _asInt(json['id']),
      qbListId: json['qb_list_id'] as String?,
      fullName: json['full_name'] as String? ?? '',
      balance: parseMoney(json['balance']),
      totalBalance: parseMoney(json['total_balance'] ?? json['balance']),
      salesRepName: json['sales_rep_name'] as String?,
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

class CustomerPage {
  const CustomerPage({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  final List<CustomerBalance> items;
  final int currentPage;
  final int lastPage;
  final int total;

  bool get hasMore => currentPage < lastPage;

  factory CustomerPage.fromJson(Map<String, dynamic> json) {
    final raw = json['data'];
    final items = <CustomerBalance>[];
    if (raw is List) {
      for (final row in raw) {
        if (row is Map) {
          items.add(CustomerBalance.fromJson(Map<String, dynamic>.from(row)));
        }
      }
    }
    return CustomerPage(
      items: items,
      currentPage: _asInt(json['current_page'], fallback: 1),
      lastPage: _asInt(json['last_page'], fallback: 1),
      total: _asInt(json['total'], fallback: items.length),
    );
  }

  static int _asInt(Object? value, {int fallback = 0}) {
    if (value is int) {
      return value;
    }
    if (value is num) {
      return value.toInt();
    }
    if (value is String) {
      return int.tryParse(value) ?? fallback;
    }
    return fallback;
  }
}
