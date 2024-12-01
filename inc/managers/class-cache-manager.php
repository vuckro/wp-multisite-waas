<?php
/**
 * Cache Manager Class
 *
 * Handles processes related to cache.
 *
 * @package WP_Ultimo
 * @subpackage Managers/Cache_Manager
 * @since 2.1.2
 */

namespace WP_Ultimo\Managers;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Handles processes related to cache.
 *
 * @since 2.1.2
 */
class Cache_Manager {

	use \WP_Ultimo\Traits\Singleton;

	/**
	 * Flush known caching plugins, offers hooks to add more plugins in the future
	 *
	 * @since 2.1.2
	 * @return void
	 */
	public function flush_known_caches() {

		/**
		 * Iterate through known caching plugins methods and flush them
		 * This is done by calling this class' methods ended in '_cache_flush'
		 *
		 * To support more caching plugins, just add a method to this class suffixed with '_cache_flush'
		 */
		foreach (get_class_methods($this) as $method) {

			if (substr_compare($method, '_cache_flush', -strlen('_cache_flush')) === 0) {

				$this->$method();

			} // end if;

		} // end foreach;

		/**
		 * Hook to additional cleaning
		 */
		do_action('wu_flush_known_caches');

	} // end flush_known_caches;

	/**
	 * Flush WPEngine Cache
	 *
	 * @since 2.1.2
	 * @return void
	 */
	protected function wp_engine_cache_flush() {

		if (class_exists('\WpeCommon') && method_exists('\WpeCommon', 'purge_varnish_cache')) {

			\WpeCommon::purge_memcached(); // WPEngine Cache Flushing
			\WpeCommon::clear_maxcdn_cache(); // WPEngine Cache Flushing
			\WpeCommon::purge_varnish_cache(); // WPEngine Cache Flushing

		} // end if;

	} // end wp_engine_cache_flush;

	/**
	 * Flush WP Rocket Cache
	 *
	 * @since 2.1.2
	 * @return void
	 */
	protected function wp_rocket_cache_flush() {

		if (function_exists('rocket_clean_domain')) {

			\rocket_clean_domain();

		} // end if;

	} // end wp_rocket_cache_flush;

	/**
	 * Flush WP Super Cache
	 *
	 * @since 2.1.2
	 * @return void
	 */
	protected function wp_super_cache_flush() {

		if (function_exists('wp_cache_clear_cache')) {

			\wp_cache_clear_cache(); // WP Super Cache Flush

		} // end if;

	} // end wp_super_cache_flush;

	/**
	 * Flush WP Fastest Cache
	 *
	 * @since 2.1.2
	 * @return void
	 */
	protected function wp_fastest_cache_flush() {

		if (function_exists('wpfc_clear_all_cache')) {

			\wpfc_clear_all_cache(); // WP Fastest Cache Flushing

		} // end if;

	} // end wp_fastest_cache_flush;

	/**
	 * Flush W3 Total Cache
	 *
	 * @since 2.1.2
	 * @return void
	 */
	protected function w3_total_cache_flush() {

		if (function_exists('w3tc_pgcache_flush')) {

			\w3tc_pgcache_flush(); // W3TC Cache Flushing

		} // end if;

	} // end w3_total_cache_flush;

	/**
	 * Flush Hummingbird Cache
	 *
	 * @since 2.1.2
	 * @return void
	 */
	protected function hummingbird_cache_flush() {

		if (class_exists('\Hummingbird\WP_Hummingbird') && method_exists('\Hummingbird\WP_Hummingbird', 'flush_cache')) {

			\Hummingbird\WP_Hummingbird::flush_cache(); // Hummingbird Cache Flushing

		} // end if;

	} // end hummingbird_cache_flush;

	/**
	 * Flush WP Optimize Cache
	 *
	 * @since 2.1.2
	 * @return void
	 */
	protected function wp_optimize_cache_flush() {

		if (class_exists('\WP_Optimize') && method_exists('\WP_Optimize', 'get_page_cache')) {

			$wp_optimize = \WP_Optimize()->get_page_cache();

			if (method_exists($wp_optimize, 'purge')) {

				$wp_optimize->purge(); // WP Optimize Cache Flushing

			} // end if;

		} // end if;

	} // end wp_optimize_cache_flush;

	/**
	 * Flush Comet Cache
	 *
	 * @since 2.1.2
	 * @return void
	 */
	protected function comet_cache_flush() {

		if (class_exists('\Comet_Cache') && method_exists('\Comet_Cache', 'clear')) {

			\Comet_Cache::clear(); // Comet Cache Flushing

		} // end if;

	} // end comet_cache_flush;

	/**
	 * Flush LiteSpeed Cache
	 *
	 * @since 2.1.2
	 * @return void
	 */
	protected function litespeed_cache_flush() {

		if (class_exists('\LiteSpeed_Cache_API') && method_exists('\LiteSpeed_Cache_API', 'purge_all')) {

			\LiteSpeed_Cache_API::purge_all(); // LiteSpeed Cache Flushing

		} // end if;

	} // end litespeed_cache_flush;

} // end class Cache_Manager;
