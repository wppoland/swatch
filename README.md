# Swatch — Variation Swatches for WooCommerce

Replace WooCommerce's default variation `<select>` dropdowns with accessible
colour and label swatches. Self-contained — no external services, no Composer
runtime dependencies.

## What it does

- Per-attribute swatch **type** (colour or button/label), set on the global
  attribute screen.
- Per-term **colour** (`sanitize_hex_color`) and custom **label**, stored as
  term meta.
- Front-end swatches wired into WooCommerce's native variations form (sets the
  hidden `<select>` and dispatches `change`), so price/stock/add-to-cart update
  normally. Selected and out-of-combination states reflected via WooCommerce's
  own variation events. Vanilla JS, no jQuery.
- Settings page under **WooCommerce → Swatch**: enable/disable, default type,
  size, shape, tooltip, selected-value label.
- Graceful fallback to the standard dropdown when an attribute has no swatch
  data (e.g. colour type with no colours configured).

## Architecture

- `swatch.php` — bootstrap. Boots on `init:0` and fires `swatch/booted`.
- `src/Plugin.php` — singleton + DI container, `url()` / `path()` helpers.
- `src/Container.php` — minimal DI container (`singleton`, `get`, `has`).
- `config/{services,hooks,defaults}.php` — service wiring, boot order, defaults.
- `src/Service/SwatchData.php` — reads/writes attribute types + term meta.
- `src/Service/Settings.php` — resolves settings (defaults + stored option).
- `src/Service/FrontendRenderer.php` — filters the variation dropdown HTML.
- `src/Admin/Settings.php` — settings screen.
- `src/Admin/AttributeFields.php` — type selector + per-term colour/label fields.
- `assets/` — `swatch.css` / `swatch.js` (front-end), `admin.css`.

PRO companions hook `add_action('swatch/booted', ...)`.

## Development

```bash
composer install
composer cs        # PHPCS
composer analyse   # PHPStan level 6
node --check assets/js/swatch.js
wp i18n make-pot . languages/swatch.pot --domain=swatch --exclude=vendor,node_modules,tests
```

## License

GPL-2.0-or-later.
