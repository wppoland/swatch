<?php
/**
 * Uninstall cleanup for Swatch.
 *
 * Removes the plugin's own options when it is deleted from wp-admin. Per-term
 * swatch colours/labels are intentionally left in place (they are attribute term
 * meta the merchant curated and may want again on reinstall); WooCommerce data
 * is never touched.
 *
 * @package Swatch
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('swatch_settings');
delete_option('swatch_attribute_types');
delete_option('swatch_db_version');
