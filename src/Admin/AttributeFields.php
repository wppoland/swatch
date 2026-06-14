<?php

declare(strict_types=1);

namespace Swatch\Admin;

defined('ABSPATH') || exit;

use Swatch\Contract\HasHooks;
use Swatch\Service\SwatchData;

/**
 * Admin fields for assigning swatch data to global product attributes:
 *
 *  - A "Swatch type" selector on the Add/Edit global attribute screen
 *    (Products → Attributes), stored per taxonomy.
 *  - "Swatch colour" and "Swatch label" fields on each attribute term's
 *    add/edit screen, stored as term meta.
 *
 * Every save is nonce-checked and capability-gated (manage_woocommerce) and all
 * input is sanitised. Output is escaped. The term fields are registered for any
 * existing WooCommerce product-attribute taxonomy.
 */
final class AttributeFields implements HasHooks
{
    public function __construct(private readonly SwatchData $data)
    {
    }

    public function registerHooks(): void
    {
        // Swatch type on the global attribute add/edit screens.
        add_action('woocommerce_after_add_attribute_fields', [$this, 'renderTypeFieldOnAdd']);
        add_action('woocommerce_after_edit_attribute_fields', [$this, 'renderTypeFieldOnEdit']);
        add_action('woocommerce_attribute_added', [$this, 'saveAttributeType'], 10, 1);
        add_action('woocommerce_attribute_updated', [$this, 'saveAttributeType'], 10, 1);

        // Per-term colour/label fields on every product-attribute taxonomy.
        foreach ($this->attributeTaxonomies() as $taxonomy) {
            add_action($taxonomy . '_add_form_fields', [$this, 'renderTermAddFields']);
            add_action($taxonomy . '_edit_form_fields', [$this, 'renderTermEditFields'], 10, 1);
            add_action('created_' . $taxonomy, [$this, 'saveTermFields']);
            add_action('edited_' . $taxonomy, [$this, 'saveTermFields']);
        }
    }

    /**
     * All registered WooCommerce product-attribute taxonomies (e.g. pa_color).
     *
     * @return list<string>
     */
    private function attributeTaxonomies(): array
    {
        if (! function_exists('wc_get_attribute_taxonomy_names')) {
            return [];
        }

        /** @var list<string> $names */
        $names = wc_get_attribute_taxonomy_names();

        return $names;
    }

    public function renderTypeFieldOnAdd(): void
    {
        ?>
        <div class="form-field">
            <label for="swatch_attribute_type"><?php esc_html_e('Swatch type', 'swatch'); ?></label>
            <?php wp_nonce_field('swatch_attribute_type', 'swatch_attribute_type_nonce'); ?>
            <select name="swatch_attribute_type" id="swatch_attribute_type">
                <option value=""><?php esc_html_e('Use plugin default', 'swatch'); ?></option>
                <option value="button"><?php esc_html_e('Button / label', 'swatch'); ?></option>
                <option value="color"><?php esc_html_e('Colour', 'swatch'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('How this attribute renders on the product page. Colour shows colour dots (set a colour on each term); Button shows the term name as a pill.', 'swatch'); ?></p>
        </div>
        <?php
    }

    public function renderTypeFieldOnEdit(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only: reflecting the attribute id WooCommerce already validated for this admin screen.
        $attributeId = isset($_GET['edit']) ? absint(wp_unslash($_GET['edit'])) : 0;
        $taxonomy    = $attributeId > 0 ? wc_attribute_taxonomy_name_by_id($attributeId) : '';
        $current     = '' !== $taxonomy ? $this->data->typeFor($taxonomy) : '';
        ?>
        <tr class="form-field">
            <th scope="row"><label for="swatch_attribute_type"><?php esc_html_e('Swatch type', 'swatch'); ?></label></th>
            <td>
                <?php wp_nonce_field('swatch_attribute_type', 'swatch_attribute_type_nonce'); ?>
                <select name="swatch_attribute_type" id="swatch_attribute_type">
                    <option value="" <?php selected($current, ''); ?>><?php esc_html_e('Use plugin default', 'swatch'); ?></option>
                    <option value="button" <?php selected($current, 'button'); ?>><?php esc_html_e('Button / label', 'swatch'); ?></option>
                    <option value="color" <?php selected($current, 'color'); ?>><?php esc_html_e('Colour', 'swatch'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('How this attribute renders on the product page.', 'swatch'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Persist the swatch type chosen on a global attribute screen.
     *
     * @param int $attributeId The attribute id WooCommerce just saved.
     */
    public function saveAttributeType(int $attributeId): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        if (! isset($_POST['swatch_attribute_type_nonce'])
            || ! wp_verify_nonce(sanitize_key(wp_unslash($_POST['swatch_attribute_type_nonce'])), 'swatch_attribute_type')
        ) {
            return;
        }

        $taxonomy = wc_attribute_taxonomy_name_by_id($attributeId);
        if ('' === $taxonomy) {
            return;
        }

        $type = isset($_POST['swatch_attribute_type']) ? sanitize_key(wp_unslash($_POST['swatch_attribute_type'])) : '';

        $this->data->setType($taxonomy, $type);
    }

    public function renderTermAddFields(): void
    {
        ?>
        <div class="form-field">
            <label for="swatch_color"><?php esc_html_e('Swatch colour', 'swatch'); ?></label>
            <?php wp_nonce_field('swatch_term_fields', 'swatch_term_fields_nonce'); ?>
            <input type="color" name="swatch_color" id="swatch_color" value="#000000" />
            <p class="description"><?php esc_html_e('Used when this attribute is shown as colour swatches.', 'swatch'); ?></p>
        </div>
        <div class="form-field">
            <label for="swatch_label"><?php esc_html_e('Swatch label', 'swatch'); ?></label>
            <input type="text" name="swatch_label" id="swatch_label" value="" />
            <p class="description"><?php esc_html_e('Optional short label for button swatches. Defaults to the term name.', 'swatch'); ?></p>
        </div>
        <?php
    }

    /**
     * @param \WP_Term|string $term
     */
    public function renderTermEditFields($term): void
    {
        $termId = $term instanceof \WP_Term ? (int) $term->term_id : 0;
        $color  = $termId > 0 ? $this->data->colorForTerm($termId) : '';
        $label  = $termId > 0 ? $this->data->labelForTerm($termId) : '';
        ?>
        <tr class="form-field">
            <th scope="row"><label for="swatch_color"><?php esc_html_e('Swatch colour', 'swatch'); ?></label></th>
            <td>
                <?php wp_nonce_field('swatch_term_fields', 'swatch_term_fields_nonce'); ?>
                <input type="color" name="swatch_color" id="swatch_color" value="<?php echo esc_attr('' !== $color ? $color : '#000000'); ?>" />
                <p class="description"><?php esc_html_e('Used when this attribute is shown as colour swatches.', 'swatch'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="swatch_label"><?php esc_html_e('Swatch label', 'swatch'); ?></label></th>
            <td>
                <input type="text" name="swatch_label" id="swatch_label" value="<?php echo esc_attr($label); ?>" />
                <p class="description"><?php esc_html_e('Optional short label for button swatches. Defaults to the term name.', 'swatch'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Persist colour/label term meta from the term add/edit screens.
     *
     * @param int $termId The term being saved.
     */
    public function saveTermFields(int $termId): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        if (! isset($_POST['swatch_term_fields_nonce'])
            || ! wp_verify_nonce(sanitize_key(wp_unslash($_POST['swatch_term_fields_nonce'])), 'swatch_term_fields')
        ) {
            return;
        }

        if (isset($_POST['swatch_color'])) {
            $this->data->setTermColor($termId, sanitize_text_field(wp_unslash($_POST['swatch_color'])));
        }

        if (isset($_POST['swatch_label'])) {
            $this->data->setTermLabel($termId, sanitize_text_field(wp_unslash($_POST['swatch_label'])));
        }
    }
}
