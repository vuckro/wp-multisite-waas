<?php
/**
 * Plugin Name: WP Multisite WaaS
 * Description: The WordPress Multisite Website as a Service (WaaS) plugin.
 * Plugin URI: https://wpmultisitewaas.org
 * Text Domain: wp-ultimo
 * Version: 2.3.3
 * Author: Arindo Duque & NextPress
 * Author URI: https://nextpress.co/
 * Network: true
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /lang
 * Requires at least: 5.3
 * Requires PHP: 7.4.30
 *
 * WP Multisite WaaS is distributed under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP Multisite WaaS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP Multisite WaaS. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author   Arindo Duque and NextPress
 * @category Core
 * @package  WP_Ultimo
 * @version  2.3.3
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

if (!defined('WP_ULTIMO_PLUGIN_FILE')) {
	define('WP_ULTIMO_PLUGIN_FILE', __FILE__);
} elseif ( WP_ULTIMO_PLUGIN_FILE !== __FILE__) {
	return; // Different plugin loaded.
}


// Check if old name is installed and we should upgrade.
if ( function_exists('is_plugin_active') && is_plugin_active( 'wp-ultimo/wp-ultimo.php' ) ) {
	deactivate_plugins( 'wp-ultimo/wp-ultimo.php', true, true);
	add_action(
		'admin_notices',
		function() {
			echo '<div class="error"><p>';
			echo esc_html__( 'The WP Multisite WaaS plugin has been deactivated as it has been renamed WP Multisite WaaS', 'wp-ultimo' );
			echo '</p></div>';
		}
	);
	if ( defined('SUNRISE' && SUNRISE) && file_exists(WP_CONTENT_DIR . '/sunrise.php')) {
		$possible_sunrises = array(
			WP_PLUGIN_DIR . '/wp-multisite-waas/sunrise.php',
			WPMU_PLUGIN_DIR . '/wp-multisite-waas/sunrise.php',
		);

		foreach ( $possible_sunrises as $new_file ) {

			if ( ! file_exists( $new_file ) ) {
				continue;
			}

			$copy_results = @copy( $new_file, WP_CONTENT_DIR . '/sunrise.php' ); // phpcs:ignore

			if ( ! $copy_results ) {
				continue;
			}

			wu_log_add( 'sunrise', __( 'Sunrise upgrade attempt succeeded.', 'wp-ultimo' ) );

			break;
		}
		return;
	}
}

/**
 * Require core file dependencies
 */
require_once __DIR__ . '/constants.php';

require_once __DIR__ . '/autoload.php';

require_once __DIR__ . '/inc/class-autoloader.php';

require_once __DIR__ . '/dependencies/woocommerce/action-scheduler/action-scheduler.php';

require_once __DIR__ . '/inc/traits/trait-singleton.php';

/**
 * Setup autoloader
 */
WP_Ultimo\Autoloader::init();

/**
 * Setup activation/deactivation hooks
 */
WP_Ultimo\Hooks::init();

/**
 * Initializes the WP Ultimo class
 *
 * This function returns the WP_Ultimo class singleton, and
 * should be used to avoid declaring globals.
 *
 * @since 2.0.0
 * @return WP_Ultimo
 */
function WP_Ultimo() { // phpcs:ignore

	return WP_Ultimo::get_instance();

} // end WP_Ultimo;

// Initialize and set to global for back-compat
$GLOBALS['WP_Ultimo'] = WP_Ultimo();
