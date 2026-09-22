# M2B Ledger — Design tokens

Filament 3 admin theme lives in `backend/resources/css/filament/admin/theme.css`. Compile with `npm run build:filament`.

Shared Filament UI helpers (no business logic):

| Helper | Path |
|--------|------|
| Table chrome + row ActionGroup | `app/Filament/Support/ResourceTable.php` |
| Aside form sections | `app/Filament/Support/ResourceForm.php` |
| Name (+ email) identity cell | `app/Filament/Support/IdentityColumn.php` |
| Role badges | `app/Filament/Support/RoleBadgeColumn.php` |
| Active status pill | `app/Filament/Support/StatusDotColumn.php` |
| Sticky form bar + ghost delete | `app/Filament/Concerns/ModernizesResourceForm.php` |

Filter “0” badge fix: `resources/views/vendor/filament-tables/components/filters/dialog.blade.php` (badge only when count > 0).

## Brand

| Token | Value |
|-------|--------|
| App name | `config('app.name')` → **M2B Ledger** |
| Mark | `/images/m2b-mark.svg` — login card + favicon |
| Sidebar / panel brand | `/images/m2b-ledger-logo-dark.png` (white M2B + light-green Ledger) |
| Wordmark (light) | `/logo assets/m2b-ledger-logo-light.png` |
| Wordmark (dark) | `/logo assets/m2b-ledger-logo-dark.png` |
| Favicon | `/images/m2b-mark.svg` |
| Avatar | Users (list + topbar): always `#14532d` + white initials. Companies/tokens: unique `.m2b-avatar-bg-*` colors. Table cells use CSS spans (Filament `sanitizeHtml` strips data-URI images). |

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
| Page / cards | `#f9fafb` / `#ffffff` |

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

## Resource layout

| Token | Value | CSS variable |
|-------|--------|----------------|
| Content max width | `80rem` (7xl) | `--m2b-content-max` |
| Form max width | `62.5rem` (~1000px) | `--m2b-form-max` |
| Page vertical gap | `2rem` (32px) | `--m2b-page-gap` |
| Page title | `1.625rem` / weight `600` | `--m2b-title-size` / `--m2b-title-weight` |
| Breadcrumbs | `0.8125rem`, gray, quiet | `--m2b-breadcrumb-size` |

## Tables

| Token | Light | Dark | CSS variable |
|-------|-------|------|----------------|
| Card radius | `0.75rem` (xl), overflow clips chrome | same | `--m2b-table-radius` |
| Card outer border | `#d1d5db` | `#1a2b22` | `--m2b-table-border` |
| Cell grid | row + column dividers | same | `--m2b-table-divider` / header border |
| Column header bg | `#166534` (sidebar green) | `#0f1f17` | `--m2b-table-header-bg` |
| Column header text | `#ffffff` | `#86efac` | `--m2b-table-header-color` |
| Sort icons | white @ 85% (hover 100%) | `#86efac` | `--m2b-table-header-sort` |
| Header / footer border | `rgba(255,255,255,0.12)` | `#1a2b22` | `--m2b-table-header-border` |
| Header type | 12px uppercase medium, tracking `0.04em` | same | `--m2b-table-header-*` |
| Toolbar bg | `#ffffff` | `#0d1813` | `--m2b-table-toolbar-bg` |
| Toolbar border | `#dcfce7` | `#1a2b22` | `--m2b-table-toolbar-border` |
| Footer / pagination bg | `#166534` | `#0f1f17` | `--m2b-table-footer-bg` |
| Footer text | `rgba(255,255,255,0.9)` | `#8fa79a` | `--m2b-table-footer-color` |
| Per-page / page controls | white bg, dark text; active = white + `#166534` | surface + muted | `--m2b-table-footer-select-bg` / `--m2b-table-footer-control-text` / `--m2b-table-footer-active-text` |
| Row min height | `3.5rem` (56px) | same | `--m2b-table-row-min` |
| Row hover | green-50 @ 60% | `rgba(34,197,94,0.06)` | `--m2b-table-row-hover` |
| Body dividers | `#e5e7eb` | `#1a2b22` | `--m2b-table-divider` |

Light-only solid green chrome is also reinforced under `html:not(.dark)` so pale green (`#f0fdf4`) / gray thead/tfoot leftovers cannot win. Dark mode is unchanged via `.dark` token overrides.

Identity cell: 40px green-tinted initials avatar + medium name + small gray description (email).

Role badges (Filament custom colors + `.m2b-role-*`):

| Role | Light | Dark |
|------|-------|------|
| admin | bg `#dcfce7` / text `#14532d` | deep green wash / `#bbf7d0` |
| owner | emerald soft | emerald wash |
| collections | amber soft | amber wash |
| sales_rep | sky soft | sky wash |

Toolbar: search left; **Filters** button with count badge only when count > 0; column toggle as quiet icon.

Empty state: centered icon, short title, primary CTA.

## Forms (create / edit)

| Token | Light | Dark | CSS variable |
|-------|-------|------|----------------|
| Form max width | `960px` (`60rem`) | same | `--m2b-form-max` |
| Section gap / pad | `24px` | same | `--m2b-section-gap` / `--m2b-section-pad` |
| Section card | white, `#e5e7eb` border, rounded-xl | `#0d1813` / `#1a2b22` | — |
| Section header | `#166534` bg, white title, muted white description | `#0f1f17` / `#86efac` | — |
| Layout | Full-width cards (no aside); 2-col field grid on `md+` | same | `ResourceForm::section()` |
| Input height | `40px` | same | `--m2b-input-height` |
| Input bg / border | `#ffffff` / `#d1d5db` | `#0a1410` / `#1f3328` | `--m2b-input-bg` / `--m2b-input-border` |
| Focus | border `#22c55e` + 3px ring @ 20% | same | `--m2b-input-focus-*` |
| Disabled bg | `#f9fafb` | `#0a1611` | `--m2b-input-disabled-bg` |
| Label | 14px medium, 6px above input | same | `--m2b-label-*` |
| Required mark | muted red | same | `--m2b-required` |
| Helper text | 12–13px gray | same | `--m2b-helper-*` |
| Sticky bar | content-column only, blur, 16px pad; page bottom pad = bar height | same | `--m2b-sticky-bg` / `--m2b-sticky-bar-height` |

`ResourceForm::section()` builds standard Filament sections (title + description in header). Users: Account / Access / Security. Sticky **Save changes** + ghost **Cancel**; **Delete** remains outlined danger in the page header.

## Radii, spacing, type (global)

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
