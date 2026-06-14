<?php
/**
 * Constants needed by PHPStan to analyse the plugin without bootstrapping WordPress.
 *
 * @package Swatch
 */

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (! defined('SWATCH_DIR')) {
        define('SWATCH_DIR', '/tmp/swatch/');
    }
    if (! defined('SWATCH_URL')) {
        define('SWATCH_URL', 'https://example.test/wp-content/plugins/swatch/');
    }
}

namespace Swatch {
    if (! defined('Swatch\\VERSION')) {
        define('Swatch\\VERSION', '0.1.0');
    }
    if (! defined('Swatch\\PLUGIN_FILE')) {
        define('Swatch\\PLUGIN_FILE', '/tmp/swatch/swatch.php');
    }
}
