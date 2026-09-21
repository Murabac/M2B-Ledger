# M2B Ledger — Design tokens

Filament 3 admin theme lives in `backend/resources/css/filament/admin/theme.css`. Compile with `npm run build:filament`.

## Brand

| Token | Value |
|-------|--------|
| App name | `config('app.name')` → **M2B Ledger** |
| Mark | `/images/m2b-mark.svg` — login card + favicon |
| Sidebar / panel brand | `/images/m2b-ledger-logo-dark.png` (white M2B + light-green Ledger) |
| Wordmark (light) | `/logo assets/m2b-ledger-logo-light.png` |
| Wordmark (dark) | `/logo assets/m2b-ledger-logo-dark.png` |
| Favicon | `/images/m2b-mark.svg` |
| Avatar | ui-avatars, background `#14532d`, text `#f0fdf4` |

## Primary green (accents)

Bright green is reserved for buttons, active states, icons, and links.

| Step | Hex | CSS variable |
|------|-----|----------------|
| 50 | `#f0fdf4` | `--m2b-green-50` |
| 100 | `#dcfce7` | `--m2b-green-100` |
| 200 | `#bbf7d0` | `--m2b-green-200` |
| 300 | `#86efac` | `--m2b-green-300` |
| 400 | `#4ade80` | `--m2b-green-400` |
| 500 | `#22c55e` | `--m2b-green-500` |
| 600 | `#16a34a` | `--m2b-green-600` |
| 700 | `#15803d` | `--m2b-green-700` |
| 800 | `#166534` | `--m2b-green-800` |
| 900 | `#14532d` | `--m2b-green-900` |
| 950 | `#052e16` | `--m2b-green-950` |

## Light mode chrome

| Token | Value |
|-------|--------|
| Sidebar / top bar | `#166534` |
| Chrome border | `rgba(255,255,255,0.10)` |
| Nav text / icons | `rgba(255,255,255,0.80)` |
| Nav hover | `rgba(255,255,255,0.08)` |
| Active nav | bg `rgba(255,255,255,0.16)`, text/icon `#ffffff` |
| Brand | White "M2B" + "Ledger"; mark on white `rounded-lg` tile |
| Avatar | `#14532d` + `rgba(255,255,255,0.16)` wash, white initials |
| Page / cards | unchanged (`#f9fafb` / `#ffffff`) |

White text on `#166534` meets WCAG AA for normal text (~7.1:1). Active white on 16% frost still sits on `#166534` (≥4.5:1).

## Dark mode (green-tinted theme)

| Token | Value | CSS variable |
|-------|--------|----------------|
| Page background | `#060d0a` | `--m2b-page` |
| Cards / surfaces | `#0d1813` | `--m2b-card` |
| Surface border | `#1a2b22` | `--m2b-border` |
| Soft shadow | dark soft elevation | `--m2b-shadow` |
| Sidebar + top bar | `#0a1a12` | `--m2b-sidebar` / `--m2b-topbar` |
| Chrome border | `#16281f` | `--m2b-chrome-border` |
| Primary text | `#f0fdf4` | `--m2b-text` |
| Secondary text | `#8fa79a` | `--m2b-text-secondary` |
| Accent / buttons | `#22c55e` | `--m2b-accent` |
| Accent hover | `#4ade80` | `--m2b-accent-hover` |
| Links | `#86efac` | `--m2b-accent-link` |
| Active nav bg | `rgba(34,197,94,0.14)` | `--m2b-chrome-active-bg` |
| Active nav text | `#86efac` | `--m2b-chrome-active-text` |
| Active nav icon | `#4ade80` | `--m2b-chrome-active-icon` |
| Nav hover | `rgba(255,255,255,0.05)` | `--m2b-chrome-hover` |

Dark mode also remaps Filament `--gray-*` CSS variables to green-tinted RGB so default Tailwind gray utilities no longer read blue-black.

## Radii, spacing, type

| Token | Value | CSS variable |
|-------|--------|----------------|
| Corner radius | `0.75rem` (xl) | `--m2b-radius` |
| Comfortable gap | `1.5rem` | `--m2b-space` |
| Font | Inter | `--m2b-font` |
| Body size | `15px` | `--m2b-body-size` |

## Compile

```bash
cd backend
npm run build:filament
```

Uses isolated Tailwind 3 in `backend/tools/tw3`. Output: `public/css/filament/admin/theme.css`.
