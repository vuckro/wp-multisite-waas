<?php
/**
 * Adds a validation rules for states
 *
 * @package WP_Ultimo
 * @subpackage Helpers/Validation_Rules
 * @since 2.0.11
 */

namespace WP_Ultimo\Helpers\Validation_Rules;

// Exit if accessed directly
defined('ABSPATH') || exit;

use Rakit\Validation\Rule;

/**
 * Validates template sites.
 *
 * @since 2.0.4
 */
class State extends Rule {

	/**
	 * Parameters that this rule accepts.
	 *
	 * @since 2.0.4
	 * @var array
	 */
	protected $fillableParams = ['country']; // phpcs:ignore
	/**
	 * Performs the actual check.
	 *
	 * @since 2.0.11
	 *
	 * @param mixed $state The state value detected.
	 */
    public function check($state) : bool { // phpcs:ignore

		$check = true;

		$country = $this->parameter('country') ?? wu_request('billing_country');

		if ($country && $state) {
			$state = strtoupper((string) $state);

			$allowed_states = array_keys(wu_get_country_states(strtoupper((string) $country), false));

			if (! empty($allowed_states)) {
				$check = in_array($state, $allowed_states, true);
			}
		}

		return $check;
	}
}
