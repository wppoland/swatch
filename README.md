# Swatch

Swatch replaces WooCommerce's default variation dropdowns with accessible colour and label swatches on your single product pages, so shoppers can pick options visually instead of from a `<select>` menu.

## Features

- Colour and button/label swatch types, chosen per attribute on the global attribute screen.
- Per-term colour and custom label, stored as term meta.
- Drives WooCommerce's native variations form, so price, stock and the add-to-cart button update exactly as with the standard dropdown. Vanilla JavaScript, no jQuery.
- Keyboard operable and screen-reader labelled, with visible focus and no layout shift.
- Settings page under WooCommerce → Swatch: enable/disable, default type, size, shape and tooltip.
- Graceful fallback to the standard dropdown when an attribute has no swatch data.

## Installation

1. Upload the plugin to `/wp-content/plugins/swatch`, or install it from Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to WooCommerce → Swatch to set the defaults, then assign a colour or label to your attribute terms under Products → Attributes.

## Frequently Asked Questions

**Does it require WooCommerce?**
Yes. Swatch extends WooCommerce variable products and does nothing without it.

**What happens to attributes I have not configured?**
They keep WooCommerce's standard dropdown, so nothing ever breaks.

Built by WPPoland — https://plogins.com

License: GPL-2.0-or-later
