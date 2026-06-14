<?php

declare(strict_types=1);

namespace Swatch\Service;

defined('ABSPATH') || exit;

/**
 * Resolves Swatch plugin settings: stored options merged over packaged
 * defaults. Shared by the admin screen, the renderer and the asset enqueue.
 */
final class Settings
{
    public const OPTION = 'swatch_settings';

    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    /**
     * All resolved settings (defaults merged with stored options).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (null !== $this->cache) {
            return $this->cache;
        }

        $stored = get_option(self::OPTION, []);
        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require \Swatch\Plugin::instance()->path('config/defaults.php');

        return $this->cache = array_merge($defaults, $stored);
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        $all = $this->all();

        return $all[$key] ?? $fallback;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->get('enabled', true);
    }

    /**
     * Forget the cached settings (used after a save in the same request).
     */
    public function flush(): void
    {
        $this->cache = null;
    }

    /**
     * Packaged defaults only (no stored overrides).
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        /** @var array<string, mixed> $defaults */
        $defaults = require \Swatch\Plugin::instance()->path('config/defaults.php');

        return $defaults;
    }
}
