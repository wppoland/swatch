<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container. Swatch is self-contained — all logic lives in these services.
 *
 * @package Swatch
 */

declare(strict_types=1);

use Swatch\Admin\AttributeFields;
use Swatch\Admin\Settings;
use Swatch\Container;
use Swatch\Migrator;
use Swatch\Service\SwatchData;
use Swatch\Service\Settings as SettingsStore;
use Swatch\Service\FrontendRenderer;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    $c->singleton(Migrator::class, static fn (): Migrator => new Migrator());

    // Settings store: resolves stored options merged over packaged defaults.
    $c->singleton(SettingsStore::class, static fn (): SettingsStore => new SettingsStore());

    // Reads/writes per-attribute swatch types and per-term colours/labels.
    $c->singleton(SwatchData::class, static fn (): SwatchData => new SwatchData());

    // Front-end: replaces the variation <select> dropdowns with swatches.
    $c->singleton(FrontendRenderer::class, static fn (): FrontendRenderer => new FrontendRenderer(
        $c->get(SwatchData::class),
        $c->get(SettingsStore::class),
    ));

    // Admin (only needed in wp-admin context).
    if (is_admin()) {
        $c->singleton(Settings::class, static fn (): Settings => new Settings($c->get(SettingsStore::class)));
        $c->singleton(AttributeFields::class, static fn (): AttributeFields => new AttributeFields($c->get(SwatchData::class)));
    }
};
