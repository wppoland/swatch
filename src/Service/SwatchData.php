<?php

declare(strict_types=1);

namespace Swatch\Service;

defined('ABSPATH') || exit;

/**
 * Reads and writes Swatch's persisted data:
 *
 *  - Per-attribute swatch TYPE ('color' | 'button'), stored against the global
 *    attribute taxonomy in the `swatch_attribute_types` option keyed by
 *    taxonomy slug (e.g. `pa_color`). Custom (product-level) attributes have no
 *    taxonomy, so they always fall back to the plugin default type.
 *
 *  - Per-term swatch VALUE (a hex colour for colour swatches, a short label for
 *    button swatches), stored as term meta `swatch_color` / `swatch_label`.
 *
 * All getters degrade gracefully: a missing type returns the default, a missing
 * colour returns an empty string (the renderer then falls back sensibly).
 */
final class SwatchData
{
    public const TYPES_OPTION = 'swatch_attribute_types';

    public const META_COLOR = 'swatch_color';
    public const META_LABEL = 'swatch_label';

    /** @var array<string, string>|null */
    private ?array $typesCache = null;

    /**
     * Valid swatch types.
     *
     * @return list<string>
     */
    public static function validTypes(): array
    {
        return ['color', 'button'];
    }

    /**
     * Swatch type for an attribute taxonomy (e.g. `pa_color`).
     *
     * @return string One of self::validTypes(), or '' when none is configured.
     */
    public function typeFor(string $taxonomy): string
    {
        $types = $this->allTypes();
        $type  = $types[$taxonomy] ?? '';

        return in_array($type, self::validTypes(), true) ? $type : '';
    }

    /**
     * All configured attribute → type mappings.
     *
     * @return array<string, string>
     */
    public function allTypes(): array
    {
        if (null !== $this->typesCache) {
            return $this->typesCache;
        }

        $stored = get_option(self::TYPES_OPTION, []);
        if (! is_array($stored)) {
            $stored = [];
        }

        $clean = [];
        foreach ($stored as $taxonomy => $type) {
            $taxonomy = sanitize_key((string) $taxonomy);
            $type     = sanitize_key((string) $type);
            if ('' !== $taxonomy && in_array($type, self::validTypes(), true)) {
                $clean[$taxonomy] = $type;
            }
        }

        return $this->typesCache = $clean;
    }

    /**
     * Persist the swatch type for an attribute taxonomy. Passing '' removes it.
     */
    public function setType(string $taxonomy, string $type): void
    {
        $taxonomy = sanitize_key($taxonomy);
        if ('' === $taxonomy) {
            return;
        }

        $types = $this->allTypes();

        if ('' === $type) {
            unset($types[$taxonomy]);
        } elseif (in_array($type, self::validTypes(), true)) {
            $types[$taxonomy] = $type;
        } else {
            return;
        }

        update_option(self::TYPES_OPTION, $types, false);
        $this->typesCache = $types;
    }

    /**
     * Hex colour stored against a term (empty string when none).
     */
    public function colorForTerm(int $termId): string
    {
        $value = get_term_meta($termId, self::META_COLOR, true);
        $value = is_string($value) ? $value : '';

        $clean = sanitize_hex_color($value);

        return is_string($clean) ? $clean : '';
    }

    /**
     * Custom button label stored against a term (empty string when none — the
     * renderer then uses the term name).
     */
    public function labelForTerm(int $termId): string
    {
        $value = get_term_meta($termId, self::META_LABEL, true);

        return is_string($value) ? $value : '';
    }

    public function setTermColor(int $termId, string $color): void
    {
        $clean = sanitize_hex_color($color);

        if (is_string($clean) && '' !== $clean) {
            update_term_meta($termId, self::META_COLOR, $clean);
        } else {
            delete_term_meta($termId, self::META_COLOR);
        }
    }

    public function setTermLabel(int $termId, string $label): void
    {
        $clean = sanitize_text_field($label);

        if ('' !== $clean) {
            update_term_meta($termId, self::META_LABEL, $clean);
        } else {
            delete_term_meta($termId, self::META_LABEL);
        }
    }

    /**
     * Resolve the effective type for a taxonomy, honouring the plugin default.
     */
    public function resolveType(string $taxonomy, string $default): string
    {
        $type = $this->typeFor($taxonomy);

        if ('' !== $type) {
            return $type;
        }

        return in_array($default, self::validTypes(), true) ? $default : 'button';
    }

    /**
     * Forget cached option data (used after a save in the same request).
     */
    public function flush(): void
    {
        $this->typesCache = null;
    }
}
