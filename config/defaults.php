<?php
/**
 * Default settings, merged under the option key `swatch_settings`.
 *
 * Swatch ships enabled. The merchant chooses a default swatch type and tunes
 * sizing and the tooltip from the Swatch admin screen. Per-attribute swatch
 * types and per-term colours/labels are stored separately as attribute taxonomy
 * meta (see Swatch\Service\SwatchData).
 *
 * @package Swatch
 *
 * @return array<string, mixed>
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return [
    // Master switch.
    'enabled' => true,

    // Default swatch type for attributes with no explicit type set: 'color' or 'button'.
    'default_type' => 'button',

    // Swatch dimensions in pixels.
    'size'             => 36,
    'button_min_width' => 48,

    // Shape of colour swatches: 'circle' or 'square'.
    'shape' => 'circle',

    // Show a tooltip with the term name on hover/focus.
    'show_tooltip' => true,

    // Show the selected attribute's value beside the swatches (e.g. "Color: Red").
    'show_selected_label' => true,
];
