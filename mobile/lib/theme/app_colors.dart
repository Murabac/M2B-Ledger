import 'package:flutter/material.dart';

/// M2B Ledger color tokens (light + dark).
abstract final class AppColors {
  static const primary = Color(0xFF16A34A);
  static const primaryDark = Color(0xFF14532D);
  static const chrome = Color(0xFF166534);
  static const primaryContainer = Color(0xFFDCFCE7);
  static const secondary = Color(0xFF6B7C72);
  static const secondaryDark = Color(0xFF8FA79A);

  static const surface = Color(0xFFFFFFFF);
  static const surfaceDark = Color(0xFF0D1813);
  static const background = Color(0xFFF7FAF8);
  static const backgroundDark = Color(0xFF060D0A);

  static const border = Color(0xFFE5E7EB);
  static const borderDark = Color(0xFF1A2B22);

  static const textPrimary = Color(0xFF111827);
  static const textSecondary = Color(0xFF6B7280);
  static const textOnGreen = Color(0xFFFFFFFF);

  static const error = Color(0xFFDC2626);
  static const errorContainer = Color(0xFFFEE2E2);
  static const stale = Color(0xFFD97706);
  static const staleContainer = Color(0xFFFEF3C7);

  static const radiusCard = 16.0;
  static const radiusControl = 12.0;
  static const radiusPill = 999.0;

  static const spaceXs = 4.0;
  static const spaceSm = 8.0;
  static const spaceMd = 16.0;
  static const spaceLg = 24.0;
  static const spaceXl = 32.0;

  static List<BoxShadow> softShadow(Brightness brightness) {
    if (brightness == Brightness.dark) {
      return [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.35),
          blurRadius: 16,
          offset: const Offset(0, 4),
        ),
      ];
    }
    return [
      BoxShadow(
        color: const Color(0xFF0F172A).withValues(alpha: 0.06),
        blurRadius: 16,
        offset: const Offset(0, 4),
      ),
    ];
  }
}

abstract final class BrandAssets {
  /// Full-bleed launcher / splash squircle (green tile + white bars).
  static const appIcon = 'assets/branding/app-icon.png';
  static const logoIcon = 'assets/branding/logo-icon-1024.png';

  /// Bars-only mark with true transparency — use on light app bars / surfaces.
  static const logoMark = 'assets/branding/logo-mark-transparent-1024.png';

  /// Squircle mark (same family as app icon); optional in-app use on neutral surfaces.
  static const logoMarkTile = 'assets/branding/logo-mark.png';

  /// Wordmark for dark / green backgrounds (light text).
  static const logoFullLight = 'assets/branding/logo-full-light.png';

  /// Wordmark for light backgrounds (dark text).
  static const logoFullDark = 'assets/branding/logo-full-dark.png';

  static const favicon = 'assets/branding/favicon-64.png';
}

