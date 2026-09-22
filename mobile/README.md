# QB Balances (Flutter)

Wave 6 foundation: login, secure token, Dio + Bearer, last-synced banner, role-aware shell.

## Run

Backend must be up (`cd backend && php artisan serve --host=127.0.0.1 --port=8000`).

```powershell
cd mobile

# Physical Android phone over USB (recommended):
adb reverse tcp:8000 tcp:8000
flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000 --dart-define=CURRENCY=USD

# Android emulator:
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000 --dart-define=CURRENCY=USD

# Physical phone over Wi‑Fi (same LAN): use your PC IP, serve on 0.0.0.0
# php artisan serve --host=0.0.0.0 --port=8000
# flutter run --dart-define=API_BASE_URL=http://192.168.x.x:8000
```

Seed user: `owner@demo.test` / `Password123!` (see `docs/DECISIONS.md`).

## Branding / UI

Assets live in `assets/branding/` (all declared via `assets/branding/`):

| File | Use |
|------|-----|
| `logo-mark-transparent-1024.png` | App bar mark on light surfaces |
| `logo-icon-1024.png` / `app-icon.png` | Launcher + splash (green tile) |
| `logo-full-light.png` | Login / dark-green headers |
| `logo-full-dark.png` | Light backgrounds (if needed) |
| `favicon-64.png` | Small icon / web favicon later |

```powershell
dart run flutter_launcher_icons
dart run flutter_native_splash:create
```
