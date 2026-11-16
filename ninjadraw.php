<?php
/**
 * Plugin Name:       NinjaDraw - Diagram, Flowchart, Mind Map & Sketching Tool
 * Plugin URI:        https://yalogica.com/ninjadraw
 * Description:       Drawing tool that allows you to create sketches and diagrams easily and intuitively
 * Version:           1.6.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Yalogica
 * Author URI:        https://yalogica.com
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       ninjadraw
 * Domain Path:       /languages
 */

namespace Yalogica\NinjaDraw;

defined('ABSPATH') || exit;

// Exit before any require to prevent a fatal error caused by redeclaring classes
if (class_exists('Yalogica\NinjaDraw\Plugin')) {
  return;
}

if (
  (in_array('ninjadraw-pro/ninjadraw.php', (array) get_option('active_plugins'), true) ||
    array_key_exists('ninjadraw-pro/ninjadraw.php', (array) get_site_option('active_sitewide_plugins'))) &&
  file_exists(WP_PLUGIN_DIR . '/ninjadraw-pro/ninjadraw.php')
) {
  return;
}

define('NINJADRAW_PLUGIN_NAME', 'ninjadraw');
define('NINJADRAW_PLUGIN_VERSION', '1.6.1');
define('NINJADRAW_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('NINJADRAW_PLUGIN_PATH', __DIR__);
define('NINJADRAW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NINJADRAW_PLUGIN_REST_URL', 'ninjadraw/v1');
define('NINJADRAW_PLUGIN_PUBLIC_REST_URL', 'ninjadraw/public/v1');
define('NINJADRAW_SHORTCODE_NAME', 'ninjadraw');
define('NINJADRAW_UPGRADE_URL', 'https://yalogica.com/ninjadraw/');
define('NINJADRAW_DOCS_URL', 'https://yalogica.com/docs/ninjadraw/');

require_once(__DIR__ . '/includes/autoload.php');

if (function_exists(__NAMESPACE__ . '\\ninjadraw_fs')) {
  ninjadraw_fs()->set_basename(false, __FILE__);
} else {
  // Create a helper function for easy SDK access.
  function ninjadraw_fs()
  {
    global $ninjadraw_fs;

    if (! isset($ninjadraw_fs)) {
      // Include Freemius SDK.
      require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';

      $ninjadraw_fs = fs_dynamic_init([
        'id'                  => '18716',
        'type'                => 'plugin',
        'slug'                => 'ninjadraw',
        'premium_slug'        => 'ninjadraw-pro',
        'public_key'          => 'pk_e939a106b50802921b4eb481040e9',
        'premium_suffix'      => 'Pro',
        'is_premium'          => false,
        'has_addons'          => false,
        'has_paid_plans'      => true,
        'is_org_compliant'    => true,
        'trial' => [
          'days' => 7,
          'is_require_payment' => false,
        ],
        'menu' => [
          'slug' => 'ninjadraw',
          'support' => false,
          'contact' => true
        ],
        'is_live'             => true,
      ]);
    }

    $ninjadraw_fs->add_filter('pricing/show_annual_in_monthly', '__return_false');
    $ninjadraw_fs->add_filter('plugin_icon', function () {
      return dirname(__FILE__) . '/assets/img/ninjadraw.png';
    });

    return $ninjadraw_fs;
  }

  ninjadraw_fs(); // Init Freemius.
  do_action('ninjadraw_fs_loaded'); // Signal that SDK was initiated.

  Plugin::run();
}
