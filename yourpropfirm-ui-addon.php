<?php
/**
 * Plugin Name:       YourPropFirm UI Addon
 * Plugin URI:        https://github.com/ibnukasyfulhaq/checkout-frontend
 * Description:       Frontend UI customization layer for YourPropFirm Plugin. Overrides checkout CSS and templates without being affected by main plugin updates.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            YourPropFirm
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       yourpropfirm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YOURPROPFIRM_UI_ADDON_VERSION', '1.0.0' );
define( 'YOURPROPFIRM_UI_ADDON_DIR', plugin_dir_path( __FILE__ ) );
define( 'YOURPROPFIRM_UI_ADDON_URL', plugin_dir_url( __FILE__ ) );

require_once YOURPROPFIRM_UI_ADDON_DIR . 'includes/class-ypf-ui-addon-hooks.php';
require_once YOURPROPFIRM_UI_ADDON_DIR . 'includes/class-ypf-ui-addon-category-badge.php';

/**
 * Boot the add-on after all plugins are loaded.
 * Priority 1000 ensures we run after the main plugin (priority 10)
 * and after its disable_other_checkout_overrides cleanup (priority 999).
 */
add_action( 'plugins_loaded', function () {
	if ( ! defined( 'YOURPROPFIRM_VERSION' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>YourPropFirm UI Addon</strong> requires the <strong>YourPropFirm Plugin</strong> to be installed and activated.</p></div>';
		} );
		return;
	}

	YPF_UI_Addon_Hooks::init();
	YPF_UI_Addon_Category_Badge::init();
}, 1000 );
