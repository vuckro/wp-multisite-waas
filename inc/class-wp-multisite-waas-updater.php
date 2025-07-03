<?php
/**
 * Updates add-ons based on the main plugin license.
 *
 * @package WP_Ultimo_WooCommerce
 * @subpackage Updater
 * @since 2.0.0
 */

namespace WP_Ultimo;

/**
 * Updates add-ons based on the main plugin license.
 *
 * @since 2.0.0
 */
class WP_Multisite_Waas_Updater {

	private string $authorization_header = '';
	private string $client_id;
	private string $client_secret;

	private string $plugin_slug;
	private string $plugin_file;

	/**
	 * Constructor for the WP_Multisite_Waas_Updater class.
	 *
	 * @param string $plugin_slug The slug identifier for the plugin.
	 * @param string $plugin_file The main plugin file path.
	 *
	 * @since 2.0.0
	 */
	public function __construct(string $plugin_slug, string $plugin_file) {
		$this->plugin_slug = $plugin_slug;
		$this->plugin_file = $plugin_file;
	}
	/**
	 * Add the main hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init() {
		add_action('core_upgrade_preamble', [$this, 'maybe_save_access_token']);
		add_action('init', array($this, 'enable_auto_updates'));
		add_filter('upgrader_pre_download', [$this,'upgrader_pre_download'], 10, 4);
	}

	/**
	 * @param string $data base64 encoded string.
	 *
	 * @return false|string
	 */
	private function decrypt_value($data) {
		// If the site doesn't have openssl, they just won't get auto updates.
		if ( ! function_exists('openssl_decrypt') || ! function_exists('openssl_cipher_iv_length')) {
			return '';
		}
		$key         = hash_file('sha256', __FILE__); // Hash of this file
		$data        = base64_decode($data); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$iv_length   = openssl_cipher_iv_length('aes-256-cbc');
		$iv          = substr($data, 0, $iv_length);
		$cipher_text = substr($data, $iv_length);
		return openssl_decrypt($cipher_text, 'aes-256-cbc', $key, 0, $iv);
	}

	/**
	 * Adds the auto-update hooks
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enable_auto_updates() {

		$url = add_query_arg(
			[
				'update_slug'   => $this->plugin_slug,
				'update_action' => 'get_metadata',
			],
			WP_MULTISITE_WAAS_UPDATE_URL
		);

		/** @var $update_checker \Puc_v4p11_Plugin_UpdateChecker */
		$update_checker = \Puc_v4_Factory::buildUpdateChecker(
			$url,
			$this->plugin_file,
			$this->plugin_slug
		);
	}

	/**
	 * Gets the client ID from stored encrypted values.
	 *
	 * @return string The decrypted client ID.
	 * @since 2.0.0
	 */
	private function get_client_id() {
		if (isset($this->client_id)) {
			return $this->client_id;
		}
		$stuff               = include __DIR__ . '/stuff.php';
		$this->client_id     = $this->decrypt_value($stuff[0]);
		$this->client_secret = $this->decrypt_value($stuff[1]);
		return $this->client_id;
	}

	/**
	 * Gets the client secret from stored encrypted values.
	 *
	 * @return string The decrypted client secret.
	 * @since 2.0.0
	 */
	private function get_client_secret() {
		if (isset($this->client_secret)) {
			return $this->client_secret;
		}
		$stuff               = include __DIR__ . '/stuff.php';
		$this->client_id     = $this->decrypt_value($stuff[0]);
		$this->client_secret = $this->decrypt_value($stuff[1]);
		return $this->client_secret;
	}

	/**
	 * @param bool         $reply Whether to bail without returning the package.
	 * @param string       $package The package file name.
	 * @param \WP_Upgrader $upgrader The WP_Upgrader instance.
	 * @param array        $hook_extra Extra arguments passed to hooked filters.
	 */
	public function upgrader_pre_download(bool $reply, $package, \WP_Upgrader $upgrader, $hook_extra) {
		if (str_starts_with($package, WP_MULTISITE_WAAS_UPDATE_URL)) {
			$refresh_token = wu_get_option('wu-refresh-token');

			if ($refresh_token) {
				$access_token = get_transient('wu-access-token');

				if ( ! $access_token) {
					$url     = WP_MULTISITE_WAAS_UPDATE_URL . 'oauth/token';
					$data    = [
						'grant_type'    => 'refresh_token',
						'client_id'     => $this->get_client_id(),
						'client_secret' => $this->get_client_secret(),
						'refresh_token' => $refresh_token,
					];
					$request = wp_remote_post($url, ['body' => $data]);
					$body    = wp_remote_retrieve_body($request);
					$code    = wp_remote_retrieve_response_code($request);
					$message = wp_remote_retrieve_response_message($request);

					if (200 === absint($code) && 'OK' === $message) {
						$response     = json_decode($body, true);
						$access_token = $response['access_token'];
						set_transient('wu-access-token', $response['access_token'], $response['expires_in']);
					}
				}
			}

			if (empty($access_token)) {
				$oauth_url = add_query_arg(
					[
						'response_type' => 'code',
						'client_id'     => $this->get_client_id(),
						'redirect_uri'  => add_query_arg('_wpnonce', wp_create_nonce('wu_auth_nonce'), self_admin_url('update-core.php')),
					],
					WP_MULTISITE_WAAS_UPDATE_URL . 'oauth/authorize'
				);

				return new \WP_Error('noauth', sprintf('You must <a href="%s" target="_parent">Login</a> first.', $oauth_url));
			}
			$this->authorization_header = 'Bearer ' . $access_token;

			add_filter('http_request_args', [$this, 'set_update_download_headers'], 10, 2);

			$response = wp_remote_get(
				$package,
				[
					'method' => 'HEAD',
				]
			);

			$code = wp_remote_retrieve_response_code($response);
			if (is_wp_error($response)) {
				return $response;
			}

			if (200 !== absint($code)) {
				return new \WP_Error('http_request_failed', esc_html__('Failed to connect to the update server. Please try again later.', 'multisite-ultimate'));
			}
		}
		return $reply;
	}

	/**
	 * Attempts to save the access token if an authorization code is present in the URL.
	 *
	 * Checks for the 'code' parameter in $_GET and if present, sanitizes it and
	 * initiates the token saving process via save_access_token().
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function maybe_save_access_token() {
		if ( empty($_GET['code']) || ! isset($_GET['_wpnonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wu_auth_nonce') ) {
			return;
		}

		$code         = sanitize_text_field(wp_unslash($_GET['code']));
		$redirect_url = self_admin_url('update-core.php');
		$this->save_access_token($code, $redirect_url);
	}

	/**
	 * Saves the OAuth access token after successful authorization.
	 *
	 * @param string $code The authorization code received from OAuth provider.
	 * @param string $redirect_url The URL to redirect after authorization.
	 *
	 * @return void
	 * @throws \Exception If the request fails or returns an error.
	 * @since 2.0.0
	 */
	public function save_access_token($code, $redirect_url) {

		$url     = WP_MULTISITE_WAAS_UPDATE_URL . 'oauth/token';
		$data    = array(
			'code'          => $code,
			'redirect_uri'  => $redirect_url,
			'grant_type'    => 'authorization_code',
			'client_id'     => $this->get_client_id(),
			'client_secret' => $this->get_client_secret(),
		);
		$request = \wp_remote_post(
			$url,
			[
				'body' => $data,
			]
		);

		$body    = wp_remote_retrieve_body($request);
		$code    = wp_remote_retrieve_response_code($request);
		$message = wp_remote_retrieve_response_message($request);

		if (is_wp_error($request)) {
			throw new \Exception(esc_html($request->get_error_message()), esc_html($request->get_error_code()));
		}

		if (200 === absint($code) && 'OK' === $message) {
			$response = json_decode($body, true);

			set_transient('wu-access-token', $response['access_token'], $response['expires_in']);
			wu_save_option('wu-refresh-token', $response['refresh_token']);
			wp_admin_notice(
				__('Successfully connected your site to WPMultisiteWaaS.org.', 'multisite-ultimate'),
				[
					'type'        => 'success',
					'dismissible' => true,
				]
			);
		} else {
			wp_admin_notice(
				__('Failed to authenticate with WPMultisiteWaaS.org.', 'multisite-ultimate'),
				[
					'type'        => 'error',
					'dismissible' => true,
				]
			);
		}
	}

	/**
	 * @param array  $parsed_args option for the request.
	 * @param string $url url requested.
	 *
	 * @return array
	 */
	public function set_update_download_headers($parsed_args, $url = '') {
		if (str_starts_with($url, WP_MULTISITE_WAAS_UPDATE_URL) && $this->authorization_header) {
			$parsed_args['headers']['Authorization'] = $this->authorization_header;
		}
		return $parsed_args;
	}
}
