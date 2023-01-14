<?php
/**
 * PHPUnit bootstrap file for wpunit tests. Since the plugin will not be otherwise autoloaded.
 *
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

global $plugin_root_dir;
require_once $plugin_root_dir . '/autoload.php';

// require_once ABSPATH . 'wp-admin/includes/screen.php';
// require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/template.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
