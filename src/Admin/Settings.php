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
 * Stores settings in the `swatch_settings` option (array): the master toggle,
 * the default swatch type, sizes, shape and the tooltip toggle. All output is
 * escaped; all input is sanitised on save. The save capability is aligned to
 * manage_woocommerce so shop managers can save.
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

    public function enqueueAssets(string $hookSuffix): void
    {
        if ($hookSuffix !== 'woocommerce_page_' . self::PAGE) {
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

        $settings = $this->store->all();
        ?>
        <div class="wrap swatch-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="swatch-admin__intro">
                <span class="swatch-admin__intro-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" focusable="false">
                        <path fill="currentColor" d="M12 3a9 9 0 000 18 2.5 2.5 0 002.5-2.5c0-.6-.2-1.1-.6-1.5-.4-.4-.6-.9-.6-1.5a2 2 0 012-2H17a4 4 0 004-4c0-3.9-4-7-9-7zm-5 9a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm3-4a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm5 0a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/>
                    </svg>
                </span>
                <div class="swatch-admin__intro-text">
                    <h2><?php esc_html_e('Turn variation dropdowns into visual swatches', 'swatch'); ?></h2>
                    <p><?php esc_html_e('Swatch replaces the default WooCommerce variation selects with accessible colour or label swatches. Set a colour or label per attribute term on the global attribute screens (Products → Attributes → Configure terms), then tune the look here. Hover a “?” for a quick explanation.', 'swatch'); ?></p>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <div class="swatch-admin__section">
                    <h2><?php esc_html_e('General', 'swatch'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Enable swatches', 'swatch'); ?>
                                    <?php $this->helpTip('enabled', __('Master switch. When off, WooCommerce shows its standard dropdowns everywhere and no swatch assets are loaded.', 'swatch')); ?>
                                </th>
                                <td>
                                    <label for="swatch_enabled">
                                        <input
                                            type="checkbox"
                                            id="swatch_enabled"
                                            name="<?php echo esc_attr(SettingsStore::OPTION); ?>[enabled]"
                                            value="1"
                                            aria-describedby="swatch-tip-enabled"
                                            <?php checked($this->store->isEnabled(), true); ?>
                                        />
                                        <?php esc_html_e('Replace variation dropdowns with swatches on single product pages.', 'swatch'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="swatch_default_type"><?php esc_html_e('Default swatch type', 'swatch'); ?></label>
                                    <?php $this->helpTip('default_type', __('Used for any attribute you have not given an explicit type. “Colour” shows colour dots (needs a colour set per term); “Button” shows the term name as a pill — the safe choice for sizes, materials and anything without a colour.', 'swatch')); ?>
                                </th>
                                <td>
                                    <?php $type = (string) ($settings['default_type'] ?? 'button'); ?>
                                    <select id="swatch_default_type" name="<?php echo esc_attr(SettingsStore::OPTION); ?>[default_type]" aria-describedby="swatch-tip-default_type">
                                        <option value="button" <?php selected($type, 'button'); ?>><?php esc_html_e('Button / label', 'swatch'); ?></option>
                                        <option value="color" <?php selected($type, 'color'); ?>><?php esc_html_e('Colour', 'swatch'); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e('Attributes with no colours configured automatically fall back to the dropdown, so this is safe to leave on “Colour”.', 'swatch'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="swatch-admin__section">
                    <h2><?php esc_html_e('Appearance', 'swatch'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="swatch_shape"><?php esc_html_e('Colour swatch shape', 'swatch'); ?></label>
                                    <?php $this->helpTip('shape', __('Shape of colour swatches. Button/label swatches are always pill-shaped.', 'swatch')); ?>
                                </th>
                                <td>
                                    <?php $shape = (string) ($settings['shape'] ?? 'circle'); ?>
                                    <select id="swatch_shape" name="<?php echo esc_attr(SettingsStore::OPTION); ?>[shape]" aria-describedby="swatch-tip-shape">
                                        <option value="circle" <?php selected($shape, 'circle'); ?>><?php esc_html_e('Circle', 'swatch'); ?></option>
                                        <option value="square" <?php selected($shape, 'square'); ?>><?php esc_html_e('Square', 'swatch'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <?php
                            $this->numberRow('size', __('Swatch size (px)', 'swatch'), __('Height of each swatch in pixels (20–80).', 'swatch'), $settings, 20, 80, __('The visible height of each swatch. Colour swatches are square/round at this size; button swatches use it as their height.', 'swatch'));
                            $this->numberRow('button_min_width', __('Button min width (px)', 'swatch'), __('Minimum width of button/label swatches (0–200).', 'swatch'), $settings, 0, 200, __('Keeps short labels (like “S” or “XL”) from looking cramped. Set 0 to size purely to the text.', 'swatch'));
                            ?>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Tooltip', 'swatch'); ?>
                                    <?php $this->helpTip('show_tooltip', __('Shows the term name in a native tooltip on hover and focus. The name is always available to screen readers regardless of this setting.', 'swatch')); ?>
                                </th>
                                <td>
                                    <label for="swatch_show_tooltip">
                                        <input
                                            type="checkbox"
                                            id="swatch_show_tooltip"
                                            name="<?php echo esc_attr(SettingsStore::OPTION); ?>[show_tooltip]"
                                            value="1"
                                            aria-describedby="swatch-tip-show_tooltip"
                                            <?php checked((bool) ($settings['show_tooltip'] ?? true), true); ?>
                                        />
                                        <?php esc_html_e('Show the term name as a tooltip on hover/focus.', 'swatch'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Selected value label', 'swatch'); ?>
                                    <?php $this->helpTip('show_selected_label', __('Prints the chosen value (e.g. “Red”) next to the swatches, updated as the shopper selects. Handy for colour swatches where the dot alone is not enough.', 'swatch')); ?>
                                </th>
                                <td>
                                    <label for="swatch_show_selected_label">
                                        <input
                                            type="checkbox"
                                            id="swatch_show_selected_label"
                                            name="<?php echo esc_attr(SettingsStore::OPTION); ?>[show_selected_label]"
                                            value="1"
                                            aria-describedby="swatch-tip-show_selected_label"
                                            <?php checked((bool) ($settings['show_selected_label'] ?? true), true); ?>
                                        />
                                        <?php esc_html_e('Show the selected value beside the swatches.', 'swatch'); ?>
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function helpTip(string $key, string $text): void
    {
        $tipId = 'swatch-tip-' . $key;
        ?>
        <button
            type="button"
            class="swatch-help"
            aria-label="<?php esc_attr_e('More information', 'swatch'); ?>"
            aria-describedby="<?php echo esc_attr($tipId); ?>"
            title="<?php echo esc_attr($text); ?>"
        >?</button>
        <span class="swatch-help-tip" id="<?php echo esc_attr($tipId); ?>" role="tooltip" hidden><?php echo esc_html($text); ?></span>
        <?php
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function numberRow(string $key, string $label, string $help, array $settings, int $min, int $max, string $tip = ''): void
    {
        $id = 'swatch_' . $key;
        ?>
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
                <?php if ('' !== $tip) {
                    $this->helpTip($key, $tip);
                } ?>
            </th>
            <td>
                <input
                    type="number"
                    id="<?php echo esc_attr($id); ?>"
                    name="<?php echo esc_attr(SettingsStore::OPTION); ?>[<?php echo esc_attr($key); ?>]"
                    value="<?php echo esc_attr((string) ($settings[$key] ?? '')); ?>"
                    min="<?php echo esc_attr((string) $min); ?>"
                    max="<?php echo esc_attr((string) $max); ?>"
                    step="1"
                    class="small-text"
                    <?php if ('' !== $tip) : ?>aria-describedby="<?php echo esc_attr('swatch-tip-' . $key); ?>"<?php endif; ?>
                />
                <p class="description"><?php echo esc_html($help); ?></p>
            </td>
        </tr>
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

        $shape = isset($raw['shape']) ? sanitize_key((string) $raw['shape']) : 'circle';
        if (! in_array($shape, ['circle', 'square'], true)) {
            $shape = 'circle';
        }

        $size = isset($raw['size']) ? (int) $raw['size'] : (int) ($defaults['size'] ?? 36);
        $size = max(20, min(80, $size));

        $minWidth = isset($raw['button_min_width']) ? (int) $raw['button_min_width'] : (int) ($defaults['button_min_width'] ?? 48);
        $minWidth = max(0, min(200, $minWidth));

        $sanitized = array_merge($defaults, [
            'enabled'             => ! empty($raw['enabled']),
            'default_type'        => $type,
            'shape'               => $shape,
            'size'                => $size,
            'button_min_width'    => $minWidth,
            'show_tooltip'        => ! empty($raw['show_tooltip']),
            'show_selected_label' => ! empty($raw['show_selected_label']),
        ]);

        $this->store->flush();

        return (array) apply_filters('swatch/sanitize_settings', $sanitized, $raw);
    }
}
