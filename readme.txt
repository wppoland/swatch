=== Swatch - Variation Swatches for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, variation swatches, color swatches, variations, product attributes
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
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

= Does it require WooCommerce? =

Yes. Swatch extends WooCommerce's variable products and does nothing without it.

= What happens to attributes I have not configured? =

They keep WooCommerce's standard dropdown. Colour swatches with no colours set fall back to the dropdown automatically, so nothing ever breaks.

= Does it work without jQuery? =

Yes. The front-end is vanilla JavaScript that hooks WooCommerce's own variation events.

== Screenshots ==

1. Colour and button swatches on a single product page.
2. The Swatch settings screen under the WooCommerce menu.

== Changelog ==

= 0.1.0 =
* Initial release.
