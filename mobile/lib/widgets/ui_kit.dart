import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../config/env.dart';
import '../theme/app_colors.dart';

String formatMoney(num value) {
  return NumberFormat.currency(symbol: '\$', decimalDigits: 2).format(value);
}

String currencyCode() => Env.currency;

class BrandLogo extends StatelessWidget {
  const BrandLogo.full({
    super.key,
    required this.onDark,
    this.height = 36,
  })  : mark = false,
        tile = false;

  /// Transparent bars-only mark for light chrome (app bars).
  const BrandLogo.mark({
    super.key,
    this.height = 32,
  })  : mark = true,
        tile = false,
        onDark = false;

  /// Green squircle tile (icon plate) when a filled badge is preferred.
  const BrandLogo.tile({
    super.key,
    this.height = 32,
  })  : mark = false,
        tile = true,
        onDark = false;

  final bool mark;
  final bool tile;
  final bool onDark;
  final double height;

  @override
  Widget build(BuildContext context) {
    final String asset;
    if (tile) {
      asset = BrandAssets.logoIcon;
    } else if (mark) {
      asset = BrandAssets.logoMark;
    } else {
      asset = onDark ? BrandAssets.logoFullLight : BrandAssets.logoFullDark;
    }

    return Image.asset(
      asset,
      height: height,
      fit: BoxFit.contain,
      filterQuality: FilterQuality.high,
      isAntiAlias: true,
    );
  }
}

class SoftEmptyState extends StatelessWidget {
  const SoftEmptyState({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
  });

  final IconData icon;
  final String title;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppColors.spaceLg),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                color: theme.colorScheme.primaryContainer,
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: AppColors.primary, size: 32),
            ),
            const SizedBox(height: AppColors.spaceMd),
            Text(title, style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600)),
            if (subtitle != null) ...[
              const SizedBox(height: AppColors.spaceSm),
              Text(
                subtitle!,
                textAlign: TextAlign.center,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class SoftLoading extends StatelessWidget {
  const SoftLoading({super.key, this.label = 'Loading…'});

  final String label;

  @override
  Widget build(BuildContext context) {
    return SoftEmptyState(
      icon: Icons.hourglass_top_rounded,
      title: label,
      subtitle: null,
    );
  }
}

class OfflineDataChip extends StatelessWidget {
  const OfflineDataChip({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Chip(
      avatar: Icon(Icons.cloud_off_outlined, size: 16, color: theme.colorScheme.onSurfaceVariant),
      label: const Text('Offline data'),
      visualDensity: VisualDensity.compact,
      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
    );
  }
}

class InitialsAvatar extends StatelessWidget {
  const InitialsAvatar({super.key, required this.name, this.size = 44});

  final String name;
  final double size;

  @override
  Widget build(BuildContext context) {
    final initials = _initials(name);
    final hue = (name.hashCode.abs() % 360).toDouble();
    final bg = HSLColor.fromAHSL(1, hue.toDouble(), 0.35, 0.88).toColor();
    final fg = HSLColor.fromAHSL(1, hue.toDouble(), 0.45, 0.32).toColor();

    return Container(
      width: size,
      height: size,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: Color.lerp(bg, AppColors.primaryContainer, 0.45),
        borderRadius: BorderRadius.circular(size * 0.28),
      ),
      child: Text(
        initials,
        style: Theme.of(context).textTheme.labelLarge?.copyWith(
              color: Color.lerp(fg, AppColors.primaryDark, 0.5),
              fontWeight: FontWeight.w700,
            ),
      ),
    );
  }

  static String _initials(String name) {
    final parts = name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) return '?';
    if (parts.length == 1) {
      return parts.first.substring(0, parts.first.length.clamp(0, 2)).toUpperCase();
    }
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
}

class M2bCard extends StatelessWidget {
  const M2bCard({super.key, required this.child, this.padding, this.onTap});

  final Widget child;
  final EdgeInsetsGeometry? padding;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final card = Container(
      padding: padding ?? const EdgeInsets.all(AppColors.spaceMd),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(AppColors.radiusCard),
        border: Border.all(color: theme.colorScheme.outline.withValues(alpha: 0.8)),
        boxShadow: AppColors.softShadow(theme.brightness),
      ),
      child: child,
    );
    if (onTap == null) return card;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppColors.radiusCard),
        child: card,
      ),
    );
  }
}
