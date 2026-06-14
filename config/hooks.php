<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Swatch\Contract\HasHooks.
 *
 * @package Swatch
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Swatch\Admin\AttributeFields;
use Swatch\Admin\Settings;
use Swatch\Service\FrontendRenderer;

defined('ABSPATH') || exit;

return [
    FrontendRenderer::class,
    ...(is_admin() ? [Settings::class, AttributeFields::class] : []),
];
