<?php

declare(strict_types=1);

namespace Swatch\Service;

use Swatch\Contract\HasHooks;

defined('ABSPATH') || exit;

/**
 * Front-end renderer. On single product pages it filters WooCommerce's native
 * variation-attribute dropdown markup and appends an accessible swatch group
 * alongside the original <select>, which is kept (visually hidden) so the native
 * variations form keeps working. A small vanilla-JS script mirrors swatch
 * clicks onto the hidden <select> and reflects WooCommerce's own
 * found_variation / reset_data events back onto the swatches (selected /
 * out-of-combination states). No jQuery.
 *
 * Robustness: products with no swatch configuration (no colour/label data and
 * no resolvable type) fall back to the untouched WooCommerce dropdown. Missing
 * terms, deleted attributes and custom (non-taxonomy) attributes never fatal.
 */
final class FrontendRenderer implements HasHooks
{
    public function __construct(
        private readonly SwatchData $data,
        private readonly Settings $settings,
    ) {
    }

    public function registerHooks(): void
    {
        if (! $this->settings->isEnabled()) {
            return;
        }

        add_filter('woocommerce_dropdown_variation_attribute_options_html', [$this, 'renderSwatches'], 20, 2);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue swatch assets only on single product pages that carry a variations
     * form. Registered up front so they are available, then printed on demand.
     */
    public function enqueueAssets(): void
    {
        if (! function_exists('is_product') || ! is_product()) {
            return;
        }

        $plugin = \Swatch\Plugin::instance();

        wp_enqueue_style(
            'swatch',
            $plugin->url('assets/css/swatch.css'),
            [],
            \Swatch\VERSION,
        );

        wp_enqueue_script(
            'swatch',
            $plugin->url('assets/js/swatch.js'),
            [],
            \Swatch\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );
    }

    /**
     * Filter callback: append a swatch group to WooCommerce's dropdown HTML.
     *
     * @param string               $html The original <select> markup.
     * @param array<string, mixed> $args WooCommerce dropdown args.
     */
    public function renderSwatches(string $html, array $args): string
    {
        $attribute = isset($args['attribute']) ? (string) $args['attribute'] : '';
        if ('' === $attribute) {
            return $html;
        }

        /** @var array<int|string, string> $options */
        $options = isset($args['options']) && is_array($args['options']) ? $args['options'] : [];
        $product = $args['product'] ?? null;

        // Taxonomy-based attributes expose real terms (and thus colours/labels);
        // resolve the option list from the product when WooCommerce passed none.
        $isTaxonomy = taxonomy_exists($attribute);

        if ([] === $options && $product instanceof \WC_Product_Variable) {
            $variationAttributes = $product->get_variation_attributes();
            $options             = $variationAttributes[$attribute] ?? [];
            if (! is_array($options)) {
                $options = [];
            }
        }

        if ([] === $options) {
            return $html;
        }

        $type = $this->data->resolveType(
            $attribute,
            (string) $this->settings->get('default_type', 'button'),
        );

        $items = $this->buildItems($attribute, $options, $isTaxonomy, $type);

        if ([] === $items) {
            return $html;
        }

        $selectId = $this->selectId($args, $attribute);
        $swatches = $this->renderGroup($items, $type, $attribute, $selectId);

        /**
         * Filters the rendered swatch group markup before it is appended to the
         * WooCommerce dropdown. Add-ons (e.g. Swatch Pro image swatches) use this
         * to enhance the buttons the FREE renderer produced.
         *
         * @param string               $swatches  The swatch group HTML.
         * @param string               $attribute Attribute taxonomy/name.
         * @param array<string, mixed> $args      Original WooCommerce dropdown args.
         */
        $swatches = (string) apply_filters('swatch/swatch_group_html', $swatches, $attribute, $args);

        // Keep the original <select> in the DOM (the JS hides it) so the native
        // variations form keeps functioning; append the swatch group after it.
        return $html . $swatches;
    }

    /**
     * Build the renderable item list for an attribute.
     *
     * @param array<int|string, string> $options
     * @return list<array{value:string,label:string,color:string}>
     */
    private function buildItems(string $attribute, array $options, bool $isTaxonomy, string $type): array
    {
        $items = [];

        foreach ($options as $value) {
            $value = (string) $value;
            if ('' === $value) {
                continue;
            }

            $label = $value;
            $color = '';

            if ($isTaxonomy) {
                $term = get_term_by('slug', $value, $attribute);
                if ($term instanceof \WP_Term) {
                    $label = $term->name;
                    $color = $this->data->colorForTerm($term->term_id);

                    $customLabel = $this->data->labelForTerm($term->term_id);
                    if ('' !== $customLabel) {
                        $label = $customLabel;
                    }
                }
            }

            $items[] = [
                'value' => $value,
                'label' => $label,
                'color' => $color,
            ];
        }

        // Colour swatches with zero configured colours add no value over the
        // dropdown — fall back to the native control in that case.
        if ('color' === $type) {
            $hasColor = false;
            foreach ($items as $item) {
                if ('' !== $item['color']) {
                    $hasColor = true;
                    break;
                }
            }
            if (! $hasColor) {
                return [];
            }
        }

        return $items;
    }

    /**
     * Render the accessible swatch group (radiogroup semantics).
     *
     * @param list<array{value:string,label:string,color:string}> $items
     */
    private function renderGroup(array $items, string $type, string $attribute, string $selectId): string
    {
        ob_start();
        ?>
        <div
            class="swatch-group swatch-group--<?php echo esc_attr($type); ?>"
            role="radiogroup"
            aria-label="<?php echo esc_attr(wc_attribute_label($attribute)); ?>"
            data-swatch-for="<?php echo esc_attr($selectId); ?>"
            data-swatch-type="<?php echo esc_attr($type); ?>"
        >
            <?php foreach ($items as $item) :
                $isColor = 'color' === $type && '' !== $item['color'];
                $style   = $isColor ? 'background-color:' . $item['color'] . ';' : '';
                ?>
                <button
                    type="button"
                    class="swatch swatch--<?php echo esc_attr($type); ?>"
                    role="radio"
                    aria-checked="false"
                    data-swatch-value="<?php echo esc_attr($item['value']); ?>"
                    aria-label="<?php echo esc_attr($item['label']); ?>"
                    title="<?php echo esc_attr($item['label']); ?>"
                    <?php if ('' !== $style) : ?>style="<?php echo esc_attr($style); ?>"<?php endif; ?>
                >
                    <?php if ('button' === $type) : ?>
                        <span class="swatch__label"><?php echo esc_html($item['label']); ?></span>
                    <?php else : ?>
                        <span class="screen-reader-text"><?php echo esc_html($item['label']); ?></span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Recreate the id WooCommerce assigns to the attribute <select> so the JS
     * can target it. Mirrors WooCommerce core: `id="<sanitized attribute>"`.
     *
     * @param array<string, mixed> $args
     */
    private function selectId(array $args, string $attribute): string
    {
        if (isset($args['id']) && '' !== (string) $args['id']) {
            return sanitize_html_class((string) $args['id']);
        }

        return sanitize_title($attribute);
    }
}
