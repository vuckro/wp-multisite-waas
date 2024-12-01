<?php
/**
 * Handles limitations to disk space
 *
 * @todo We need to move posts on downgrade.
 * @package WP_Ultimo
 * @subpackage Limits
 * @since 2.0.0
 */

namespace WP_Ultimo\Limits;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Handles limitations to post types, uploads and more.
 *
 * @since 2.0.0
 */
class Disk_Space_Limits {

	use \WP_Ultimo\Traits\Singleton;

	/**
	 * Whether or not the disk space limitations should be loaded.
	 * This is for performance reasons, so we don't have to run all the hooks if the site doesn't have limitations.
	 *
	 * @since 2.1.2
	 * @var boolean
	 */
	protected $should_load = false;

	/**
	 * Whether or not the class has started.
	 *
	 * @since 2.1.2
	 * @var boolean
	 */
	protected $started = false;

	/**
	 * Runs on the first and only instantiation.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init() {

		add_filter('site_option_upload_space_check_disabled', array($this, 'upload_space_check_disabled'));

		add_filter('get_space_allowed', array($this, 'apply_disk_space_limitations'));

	} // end init;

	/**
	 * Disables the upload space check if the site has limitations.
	 * This way we can handle our own checks.
	 *
	 * @since 2.1.2
	 *
	 * @param int $value The current value.
	 * @return int
	 */
	public function upload_space_check_disabled($value) {

		if (!$this->should_load()) {

			return $value;

		} // end if;

		return 0;

	} // end upload_space_check_disabled;

	/**
	 * Checks if the disk space limitations should be loaded.
	 *
	 * @since 2.1.2
	 * @return boolean
	 */
	protected function should_load() {

		if ($this->started) {

			return $this->should_load;

		} // end if;

		$this->started     = true;
		$this->should_load = true;

		/**
		 * Allow plugin developers to short-circuit the limitations.
		 *
		 * You can use this filter to run arbitrary code before any of the limits get initiated.
		 * If you filter returns any truthy value, the process will move on, if it returns any falsy value,
		 * the code will return and none of the hooks below will run.
		 *
		 * @since 1.7.0
		 * @return bool
		 */
		if (!apply_filters('wu_apply_plan_limits', wu_get_current_site()->has_limitations())) {

			$this->should_load = false;

		} // end if;

		if (!wu_get_current_site()->has_module_limitation('disk_space')) {

			$this->should_load = false;

		} // end if;

		return $this->should_load;

	} // end should_load;

	/**
	 * Changes the disk_space to the one on the product.
	 *
	 * @since 2.0.0
	 *
	 * @param string $disk_space The new disk space.
	 * @return int
	 */
	public function apply_disk_space_limitations($disk_space) {

		if (!$this->should_load()) {

			return $disk_space;

		} // end if;

		$modified_disk_space = wu_get_current_site()->get_limitations()->disk_space->get_limit();

		if (is_numeric($modified_disk_space)) {

			return $modified_disk_space;

		} // end if;

		return $disk_space;

	} // end apply_disk_space_limitations;

} // end class Disk_Space_Limits;
