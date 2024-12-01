<?php
/**
 * Session Functions
 *
 * @package WP_Ultimo\Functions
 * @since   2.0.0
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Gets or creates a Session object.
 *
 * @since 2.0.0
 *
 * @param string $session_key The session key.
 * @return \WP_Ultimo\Contracts\Session
 */
function wu_get_session($session_key) {

	global $wu_session;

	$wu_session = (array) $wu_session;

	$session = wu_get_isset($wu_session, $session_key, false);

	if ($session && is_a( $session, \WP_Ultimo\Session_Cookie::class)) {

		return $session;

	} // end if;

	$wu_session[$session_key] = new \WP_Ultimo\Session_Cookie($session_key);

	return $wu_session[$session_key];

} // end wu_get_session;
