=== Swatch - Variation Swatches for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, variation swatches, color swatches, variations, product attributes
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.4
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replace WooCommerce variation dropdowns with accessible colour and label swatches that wire straight into the native variations form.

== Description ==

Swatch replaces WooCommerce's default variation `<select>` dropdowns with visual, accessible swatches on single product pages. Choose a swatch type per attribute (colour dots or button/label pills) and assign a colour or label per term.

The swatches drive WooCommerce's own variations form, so price, stock and the add-to-cart button update exactly as they do with the stock dropdowns. Selected and out-of-combination states are reflected automatically.

The full source lives on GitHub at https://github.com/wppoland/swatch if you want to read the code or report a problem.

**Features**

* Colour and button/label swatch types.
* Per-attribute type selection on the global attribute screen.
* Per-term colour (`sanitize_hex_color`) and custom label, stored as term meta.
* Wires into WooCommerce's native variations form — no jQuery, vanilla JS.
* Keyboard operable (radiogroup semantics, arrow keys) and screen-reader labelled.
* Focus-visible rings, sufficient contrast, reduced-motion friendly, no layout shift.
* Graceful fallback to the standard dropdown when an attribute has no swatch data.
* Settings page under WooCommerce: enable/disable and default swatch type.

**Self-contained.** No external services, no account, no third-party dependencies.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/swatch`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to WooCommerce → Swatch to tune the defaults.
4. On Products → Attributes, set a swatch colour or label on each attribute term.

== Frequently Asked Questions ==

= Documentation and links =

* **Documentation** - https://plogins.com/swatch/docs/
* **Plugin page** - https://plogins.com/swatch/
* **Source code** - https://github.com/wppoland/swatch
* **Bug reports and feature requests** - https://github.com/wppoland/swatch/issues
* **Discussions and questions** - https://github.com/wppoland/swatch/discussions


= Does it require WooCommerce? =

Yes. Swatch extends WooCommerce's variable products and does nothing without it.

= What happens to attributes I have not configured? =

They keep WooCommerce's standard dropdown. Colour swatches with no colours set fall back to the dropdown automatically, so nothing ever breaks.

= Does it work without jQuery? =

Yes. The front-end is vanilla JavaScript that hooks WooCommerce's own variation events.

= Can shoppers pick variations with a keyboard? =

Yes. Swatches use radiogroup semantics, arrow-key navigation and visible focus rings.

= Does it work on mobile? =

Yes. Swatches stay in the native variations form with touch-friendly targets; no separate mobile app or script framework is required.

== Screenshots ==

1. Colour and button swatches on a single product page.
2. The Swatch settings screen under the WooCommerce menu.

== External Services ==

Swatch does not connect to any external services. It makes no outbound HTTP requests, loads no remote scripts, fonts or CDN assets, and sends no telemetry or analytics. There is no account or API key.

Everything is stored in your own database: the swatch type per attribute, the global defaults and a schema version are kept in the `swatch_attribute_types`, `swatch_settings` and `swatch_db_version` options, and each term's colour and label are stored as the `swatch_color` and `swatch_label` term meta on your WooCommerce attribute terms. The plugin sends no email.

== Changelog ==

= 0.1.4 =
* Swatch group filters `swatch/swatch_group_vars` and `swatch/swatch_group_classes` for PRO sizing and shapes.

= 0.1.3 =
* Per-swatch filters (`swatch/product_swatch_html`, `swatch/archive_swatch_html`) and `swatch/swatch_items` for PRO tooltips and add-ons.

= 0.1.2 =
* Add optional archive-loop swatch preview via `swatch/archive_enabled` and `swatch/archive_html` filters for add-ons.

= 0.1.1 =
* Add `swatch/variation_gallery` filter and expose `swatch_variation_gallery` on variation JSON for add-ons.

= 0.1.0 =
* Initial release.
