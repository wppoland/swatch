<?php

declare(strict_types=1);

namespace Swatch\Admin;

defined('ABSPATH') || exit;

use Swatch\Contract\HasHooks;
use Swatch\Service\SwatchData;
use Swatch\Service\Settings as SettingsStore;

/**
 * Admin settings page registered under the WooCommerce submenu.
 *
 * Stores settings in the `swatch_settings` option (array): the master toggle and
 * the default swatch type. All output is escaped; all input is sanitised on save.
 * The save capability is aligned to manage_woocommerce so shop managers can save.
 */
final class Settings implements HasHooks
{
    private const PAGE = 'swatch-settings';

    public function __construct(private readonly SettingsStore $store)
    {
    }

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Load the scoped admin stylesheet only on the Swatch settings screen.
     *
     * @param string $hook The current admin page hook suffix.
     */
    public function enqueueAssets(string $hook): void
    {
        if (! str_contains($hook, self::PAGE)) {
            return;
        }

        wp_enqueue_style(
            'swatch-admin',
            \Swatch\Plugin::instance()->url('assets/css/admin.css'),
            [],
            \Swatch\VERSION,
        );
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Swatch Settings', 'swatch'),
            __('Swatch', 'swatch'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::PAGE,
            SettingsStore::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );

        add_filter(
            'option_page_capability_' . self::PAGE,
            static fn (): string => 'manage_woocommerce',
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings     = $this->store->all();
        $defaults     = $this->store->defaults();
        $enabled      = $this->store->isEnabled();
        $type         = (string) ($settings['default_type'] ?? 'button');
        $optionName   = SettingsStore::OPTION;
        ?>
        <div class="wrap swatch-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <p class="swatch-intro"><?php esc_html_e('Swatch turns WooCommerce variation dropdowns into accessible colour dots and labelled buttons, so shoppers pick a variation in one tap instead of scanning a select menu. It works out of the box, the settings below are only for fine-tuning.', 'swatch'); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <section class="swatch-section">
                    <h2><?php esc_html_e('Storefront display', 'swatch'); ?></h2>
                    <p class="swatch-section__hint"><?php esc_html_e('Controls whether swatches appear on single product pages, and what an unconfigured attribute looks like.', 'swatch'); ?></p>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Enable swatches', 'swatch'); ?>
                                    <?php if ((bool) ($defaults['enabled'] ?? true) === true) : ?>
                                        <span class="swatch-default-tag"><?php esc_html_e('On by default', 'swatch'); ?></span>
                                    <?php endif; ?>
                                </th>
                                <td>
                                    <label for="swatch_enabled">
                                        <input
                                            type="checkbox"
                                            id="swatch_enabled"
                                            name="<?php echo esc_attr($optionName); ?>[enabled]"
                                            value="1"
                                            <?php checked($enabled, true); ?>
                                        />
                                        <?php esc_html_e('Show swatches on product pages.', 'swatch'); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('When off, shoppers see WooCommerce’s standard variation dropdowns and no swatch styles or scripts load, nothing is lost, the look just reverts to default. Your per-term colours and labels are kept.', 'swatch'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="swatch_default_type"><?php esc_html_e('Default swatch type', 'swatch'); ?></label>
                                </th>
                                <td>
                                    <select id="swatch_default_type" name="<?php echo esc_attr($optionName); ?>[default_type]" aria-describedby="swatch_default_type_desc">
                                        <option value="button" <?php selected($type, 'button'); ?>><?php esc_html_e('Button / label', 'swatch'); ?></option>
                                        <option value="color" <?php selected($type, 'color'); ?>><?php esc_html_e('Colour', 'swatch'); ?></option>
                                    </select>
                                    <?php if ($type === (string) ($defaults['default_type'] ?? 'button')) : ?>
                                        <span class="swatch-default-tag"><?php esc_html_e('Default', 'swatch'); ?></span>
                                    <?php endif; ?>

                                    <div class="swatch-preview" aria-hidden="true">
                                        <span class="swatch-preview__label"><?php esc_html_e('Colour:', 'swatch'); ?></span>
                                        <span class="swatch-preview__chip swatch-preview__chip--blue"></span>
                                        <span class="swatch-preview__chip swatch-preview__chip--green"></span>
                                        <span class="swatch-preview__label" style="margin-left:8px;"><?php esc_html_e('Button:', 'swatch'); ?></span>
                                        <span class="swatch-preview__pill"><?php esc_html_e('Small', 'swatch'); ?></span>
                                        <span class="swatch-preview__pill"><?php esc_html_e('Large', 'swatch'); ?></span>
                                    </div>

                                    <p class="description" id="swatch_default_type_desc"><?php esc_html_e('Applied to any attribute you have not given its own type. “Colour” renders colour dots (you set a colour per term); “Button / label” renders the term name as a pill. Either way, an attribute with no colours configured falls back to the dropdown automatically, so this choice never breaks a product.', 'swatch'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="swatch-section">
                    <h2><?php esc_html_e('Per-attribute setup', 'swatch'); ?></h2>
                    <p class="swatch-section__hint"><?php esc_html_e('Colours and labels live with each attribute, not here, so they stay correct as your catalogue grows.', 'swatch'); ?></p>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %s: link to the WooCommerce Attributes screen. */
                            esc_html__('Set a swatch colour or label on each term under %s. Override the default type per attribute on the same screen. Anything left unset uses the default above.', 'swatch'),
                            '<a href="' . esc_url(admin_url('edit.php?post_type=product&page=product_attributes')) . '">' . esc_html__('Products → Attributes', 'swatch') . '</a>'
                        );
                        ?>
                    </p>
                </section>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitise submitted settings, preserving defaults for fields not on the form.
     *
     * @param mixed $raw
     * @return array<string, mixed>
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        $defaults = $this->store->defaults();

        $type = isset($raw['default_type']) ? sanitize_key((string) $raw['default_type']) : 'button';
        if (! in_array($type, SwatchData::validTypes(), true)) {
            $type = 'button';
        }

        $sanitized = array_merge($defaults, [
            'enabled'      => ! empty($raw['enabled']),
            'default_type' => $type,
        ]);

        $this->store->flush();

        return $sanitized;
    }
}
