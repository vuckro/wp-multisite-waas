<?php
/**
 * Manages WP Ultimo Core Updates.
 *
 * @package WP_Ultimo
 * @subpackage Core Updates
 * @since 2.0.0
 */

namespace WP_Ultimo;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Manages WP Ultimo Core Updates.
 *
 * @since 2.0.0
 */
class Core_Updates {

	use \WP_Ultimo\Traits\Singleton;

	/**
	 * @var string
	 */
	protected string $cache_key = 'wu_core_update';

	/**
	 * @var string
	 */
	protected string $plugin_slug;

	/**
	 * Use a local cache to avoid multiple requests to the API.
	 *
	 * Only disable this for debugging
	 *
	 * @var boolean
	 */
	protected bool $cache_allowed = true;

	/**
	 * @var string
	 */
	protected string $version;

	/**
	 * @var string
	 */
	protected string $plugin_id;

	/**
	 * @var string
	 */
	protected string $plugin_url = 'https://wpultimo.com';

	/**
	 * Initializes the class.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init() {

		// Ensure we load the version even without wu_get_version available.
		$this->version = class_exists(\WP_Ultimo::class) ? \WP_Ultimo::VERSION : '';

		/**
		 * As WP Ultimo needs to load in wp-ultimo/ directory to work with Sunrise
		 * we can assume the plugin as wp-ultimo/wp-ultimo.php. if it's a mu-plugin
		 * we will assume as wp-ultimo.php.
		 *
		 * At this point WPMU_PLUGIN_DIR is not defined yet.
		 */
		$is_mu             = strpos(__DIR__, '/mu-plugins/wp-multisite-waas/') !== false;
		$this->plugin_id   = $is_mu ? 'wp-multisite-waas.php' : 'wp-multisite-waas/wp-multisite-waas.php';
		$this->plugin_slug = 'wp-multisite-waas/wp-multisite-waas.php';

		/**
		 * Check for a WP Ultimo Core Updates.
		 */
		add_action('upgrader_process_complete', array($this, 'maybe_add_core_update_hooks'), 10, 2);

		/**
		 * Update process hooks.
		 */
		add_filter('plugins_api', array($this, 'get_plugin_info'), 20, 3);
		add_filter('site_transient_update_plugins', array($this, 'check_for_update'));
		add_action('upgrader_process_complete', array($this, 'purge_update_cache'), 10, 2);

	} // end init;

	/**
	 * Checks if a WP Ultimo core update is being performed and triggers an action if that's the case.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Upgrader $u The upgrader instance.
	 * @param array        $i Upgrade info.
	 * @return void
	 */
	public function maybe_add_core_update_hooks($u, $i) {

		$is_a_wp_ultimo_update = false;

		if (!empty($u->result) && in_array('wp-multisite-waas.php', $u->result['source_files'], true)) {

			$is_a_wp_ultimo_update = true;

		} elseif (isset($i['plugins']) && is_array($i['plugins'])) {

			if (in_array('wp-multisite-waas/wp-multisite-waas.php', $i['plugins'], true)) {

				$is_a_wp_ultimo_update = true;

			} // end if;

		} // end if;

		if ($is_a_wp_ultimo_update) {

			function_exists('wu_log_add') && wu_log_add('wp-ultimo-core', __('Updating WP Ultimo Core...', 'wp-ultimo'));

			try {

				/**
				 * Triggers an action that be used to perform
				 * tasks on a core update.
				 *
				 * @since 2.0.0
				 */
				do_action('wu_core_update');

			} catch (\Throwable $exception) {

				// Nothing to do in here.

			} // end try;

		} // end if;

	} // end maybe_add_core_update_hooks;

	/**
	 * Override the WordPress request to return the correct plugin info.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/plugins_api/
	 *
	 * @since 2.4.0
	 *
	 * @param false|object|array $result The result object or array. Default false.
	 * @param string             $action The type of information being requested from the Plugin Install API.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object|array
	 */
	public function get_plugin_info($result, $action, $args) {

		if ('plugin_information' !== $action) {

			return $result;

		} // end if;

		if ($this->plugin_slug !== $args->slug) {

			return $result;

		} // end if;

		$remote = $this->info_request();

		if (!$remote || !$remote->success || empty($remote->update)) {

			return false;

		} // end if;

		$plugin_data = get_plugin_data(__FILE__);

		$result           = $remote->update;
		$result->name     = $plugin_data['Name'];
		$result->slug     = $this->plugin_slug;
		$result->sections = (array) $result->sections;

		return $result;

	} // end get_plugin_info;

	/**
	 * Override the WordPress request to check if an update is available.
	 *
	 * @see https://make.wordpress.org/core/2020/07/30/recommended-usage-of-the-updates-api-to-support-the-auto-updates-ui-for-plugins-and-themes-in-wordpress-5-5/
	 *
	 * @since 2.4.0
	 *
	 * @param object $transient The transient data.
	 * @return object
	 */
	public function check_for_update($transient) {

		if (empty($transient->checked)) {

			return $transient;

		} // end if;

		$res = (object) array(
			'id'            => $this->plugin_id,
			'slug'          => $this->plugin_slug,
			'plugin'        => $this->plugin_id,
			'new_version'   => $this->version,
			'url'           => $this->plugin_url,
			'package'       => '',
			'icons'         => array(),
			'banners'       => array(),
			'banners_rtl'   => array(),
			'tested'        => '',
			'requires_php'  => '',
			'compatibility' => new \stdClass(),
		);

		$remote = $this->info_request();

		if (
			$remote && $remote->success && !empty($remote->update)
			&& version_compare($this->version, $remote->update->version, '<')
		) {

			$res->new_version = $remote->update->version;
			$res->package     = property_exists($remote->update, 'download_url') ? $remote->update->download_url : '';

			$transient->response[$res->plugin] = $res;

		} else {

			$transient->no_update[$res->plugin] = $res;

		} // end if;

		return $transient;

	} // end check_for_update;

	/**
	 * When the update is complete, purge the cache.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
	 *
	 * @since 2.4.0
	 *
	 * @param WP_Upgrader $upgrader The upgrader instance.
	 * @param array       $options  Upgrade info.
	 * @return void
	 */
	public function purge_update_cache($upgrader, $options) {

		if (
			$this->cache_allowed
			&& 'update' === $options['action']
			&& 'plugin' === $options['type']
			&& !empty($options['plugins'])
		) {

			foreach ($options['plugins'] as $plugin) {

				if ($plugin === $this->plugin_id) {

					delete_transient($this->cache_key);

				} // end if;

			} // end foreach;

		} // end if;

	} // end purge_update_cache;

	/**
	 * Fetch the update info from the remote server.
	 *
	 * @since 2.4.0
	 *
	 * @return object|bool
	 */
	protected function info_request() {

		$license = \WP_Ultimo\License::get_instance();

		$license_key = $license->get_license_key();

		$remote = get_transient($this->cache_key);

		if (false !== $remote && $this->cache_allowed) {

			if ('error' === $remote) {

				return false;

			} // end if;

			return json_decode($remote);

		} // end if;

		// WP Ultimo endpoint
		$api_url = 'https://licenses.nextpress.us/api/meta/wp-ultimo/';

		if ($license_key) {

			$api_url = add_query_arg(array(
				'license_key' => rawurlencode($license_key),
			), $api_url);

		} // end if;

		$remote = wp_remote_get(
			$api_url,
			array(
				'timeout' => 5,
			)
		);

		if (
			is_wp_error($remote)
			|| 200 !== wp_remote_retrieve_response_code($remote)
			|| empty(wp_remote_retrieve_body($remote))
		) {

			set_transient($this->cache_key, 'error', MINUTE_IN_SECONDS * 10);

			return false;

		} // end if;

		$payload = wp_remote_retrieve_body($remote);

		$response          = new \stdClass();
		$response->success = true;
		$response->update  = json_decode($payload);

		set_transient($this->cache_key, json_encode($response), DAY_IN_SECONDS);

		return $response;

	} // end info_request;

} // end class Core_Updates;
