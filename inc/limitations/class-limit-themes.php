<?php
/**
 * Themes Limit Module.
 *
 * @package WP_Ultimo
 * @subpackage Limitations
 * @since 2.0.0
 */

namespace WP_Ultimo\Limitations;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Themes Limit Module.
 *
 * @since 2.0.0
 */
class Limit_Themes extends Limit {

	/**
	 * The module id.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected $id = 'themes';

	/**
	 * The theme being currently forced for this site.
	 *
	 * @since 2.0.0
	 * @var null|false|string Null when first initialized, false when no theme is forced or the theme name.
	 */
	protected $forced_active_theme;

	/**
	 * The check method is what gets called when allowed is called.
	 *
	 * Each module needs to implement a check method, that returns a boolean.
	 * This check can take any form the developer wants.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed  $value_to_check Value to check.
	 * @param mixed  $limit The list of limits in this modules.
	 * @param string $type Type for sub-checking.
	 * @return bool
	 */
	public function check($value_to_check, $limit, $type = '') {

		$theme = (object) $this->{$value_to_check};

		$types = [
			'visible'       => 'visible' === $theme->visibility,
			'hidden'        => 'hidden' === $theme->visibility,
			'available'     => 'available' === $theme->behavior,
			'not_available' => 'not_available' === $theme->behavior,
		];

		return wu_get_isset($types, $type, false);
	}

	/**
	 * Adds a magic getter for themes.
	 *
	 * @since 2.0.0
	 *
	 * @param string $theme_name The theme name.
	 * @return object
	 */
	public function __get($theme_name) {

		$theme = (object) wu_get_isset($this->get_limit(), $theme_name, $this->get_default_permissions($theme_name));

		return (object) wp_parse_args($theme, $this->get_default_permissions($theme_name));
	}

	/**
	 * Returns default permissions.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type Type for sub-checking.
	 * @return array
	 */
	public function get_default_permissions($type) {

		return [
			'visibility' => 'visible',
			'behavior'   => 'available',
		];
	}

	/**
	 * Checks if a theme exists on the current module.
	 *
	 * @since 2.0.0
	 *
	 * @param string $theme_name The theme name.
	 * @return bool
	 */
	public function exists($theme_name) {

		$results = wu_get_isset($this->get_limit(), $theme_name, []);

		return wu_get_isset($results, 'visibility', 'not-set') !== 'not-set' || wu_get_isset($results, 'behavior', 'not-set') !== 'not-set';
	}

	/**
	 * Get all themes.
	 *
	 * @since 2.0.0
	 * @return array List of theme stylesheets.
	 */
	public function get_all_themes() {

		$themes = (array) $this->get_limit();

		return array_keys($themes);
	}

	/**
	 * Get available themes.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_available_themes() {

		$limits = $this->get_limit();

		$available = [];

		foreach ($limits as $theme_slug => $theme_settings) {
			$theme_settings = (object) $theme_settings;

			if ('available' === $theme_settings->behavior) {
				$available[] = $theme_slug;
			}
		}

		return $available;
	}

	/**
	 * Get the forced active theme for the current limitations.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_forced_active_theme() {

		$active_theme = false;

		$limits = $this->get_limit();

		if (empty($limits)) {
			return $active_theme;
		}

		if (null !== $this->forced_active_theme) {
			return $this->forced_active_theme;
		}

		foreach ($limits as $theme_slug => $theme_settings) {
			$theme_settings = (object) $theme_settings;

			if ('force_active' === $theme_settings->behavior) {
				$active_theme = $theme_slug;
			}
		}

		$this->forced_active_theme = $active_theme;

		return $this->forced_active_theme;
	}

	/**
	 * Checks if the module is enabled.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type Type for sub-checking.
	 * @return boolean
	 */
	public function is_enabled($type = '') {

		return true;
	}
}
