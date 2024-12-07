<?php
/**
 * License Handler
 *
 * Handles WP Multisite WaaS activation.
 *
 * @package WP_Ultimo
 * @subpackage License
 * @since 2.0.0
 */

namespace WP_Ultimo;

use WP_Site;
use WP_Ultimo\Dependencies\Psr\Log\LogLevel;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Handles WP Multisite WaaS activation.
 *
 * @since 2.0.0
 */
class License {

	use \WP_Ultimo\Traits\Singleton;

	/**
	 * The option key used to store the license data.
	 *
	 * @var string
	 */
	protected string $option_key = 'license_key';

	/**
	 * Instantiate the necessary hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init() {
		/*
		 * Register forms for license activation.
		 */
		$this->register_forms();

		$this->setup_activator();

	} // end init;
 /**
  * Request a support signature to the API.
  *
  * This confirms ownership of the license and allows us
  * to display past conversations with confidence that the
  * customer is who they say they is.
  *
  * @since 2.0.7
  * @return string|\WP_Error
  */
 public function request_support_signature() {

		$signature_url = wu_with_license_key('https://api.wpultimo.com/signature');

		$response = wp_remote_get($signature_url);

		if (is_wp_error($response)) {

			return $response;

		} // end if;

		$body = wp_remote_retrieve_body($response);

		$data = (array) json_decode($body, true);

		$signature = wu_get_isset($data, 'signature', 'no_signature');

		return $signature;

	} // end request_support_signature;

	/**
	 * Registers the form and handler to license activation.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_forms() {

		if (function_exists('wu_register_form')) {

			add_filter('removable_query_args', array($this, 'add_activation_to_removable_query_list'));

			add_action('load-wp-ultimo_page_wp-ultimo-settings', array($this, 'add_successful_activation_message'));

			wu_register_form('license_activation', array(
				'render'  => array($this, 'render_activation_form'),
				'handler' => array($this, 'handle_activation_form'),
			));

			wu_register_form('license_deactivation', array(
				'render'  => array($this, 'render_deactivation_form'),
				'handler' => array($this, 'handle_deactivation_form'),
			));

		} // end if;

	} // end register_forms;

	/**
	 * Adds our query arg to the removable list.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args The current list of removable query args.
	 * @return array
	 */
	public function add_activation_to_removable_query_list($args) {

		$args[] = 'wp-ultimo-activation';

		return $args;

	} // end add_activation_to_removable_query_list;

	/**
	 * Adds a successful message when activation is successful.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function add_successful_activation_message() {

		if (wu_request('wp-ultimo-activation') === 'success') {

			WP_Ultimo()->notices->add(__('WP Multisite WaaS successfully activated!', 'wp-ultimo'), 'success', 'network-admin', false, array());

		} // end if;

	} // end add_successful_activation_message;

	/**
	 * Render the license activation form.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render_activation_form() {

		$fields = array(
			'license_key'   => array(
				'type'        => 'text',
				'title'       => __('Your License Key', 'wp-ultimo'),
				'desc'        => __('Enter your license key here. You received your license key via email when you completed your purchase. Your license key usually starts with "sk_".', 'wp-ultimo'),
				'placeholder' => __('e.g. sk_******************', 'wp-ultimo'),
			),
			'submit_button' => array(
				'type'            => 'submit',
				'title'           => __('Activate', 'wp-ultimo'),
				'placeholder'     => __('Activate', 'wp-ultimo'),
				'value'           => 'save',
				'classes'         => 'button button-primary wu-w-full',
				'wrapper_classes' => 'wu-items-end',
				'html_attr'       => array(
					'v-bind:disabled' => '!confirmed',
				),
			),
		);

		$form = new \WP_Ultimo\UI\Form('total-actions', $fields, array(
			'views'                 => 'admin-pages/fields',
			'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
			'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
		));

		$form->render();

	} // end render_activation_form;

	/**
	 * Handle license activation form submission.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_activation_form() {

		$license = License::get_instance();

		$activation_results = $license->activate(wu_request('license_key'));

		if (isset($activation_results->error)) {

			$activation_results = new \WP_Error('error', $activation_results->error);

		} // end if;

		if (is_wp_error($activation_results)) {

			wp_send_json_error($activation_results);

		} // end if;

		wp_send_json_success(array(
			'redirect_url' => add_query_arg('wp-ultimo-activation', 'success', wu_get_current_url()),
		));

	} // end handle_activation_form;

	/**
	 * Render the license activation form.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render_deactivation_form() {

		$fields = array(
			'confirm'       => array(
				'type'      => 'toggle',
				'title'     => __('Confirm the remove of your license key', 'wp-ultimo'),
				'desc'      => __('This action can not be undone.', 'wp-ultimo'),
				'html_attr' => array(
					'v-model' => 'confirmed',
				),
			),
			'submit_button' => array(
				'type'            => 'submit',
				'title'           => __('Remove License', 'wp-ultimo'),
				'value'           => 'save',
				'classes'         => 'button button-primary wu-w-full',
				'wrapper_classes' => 'wu-items-end',
				'html_attr'       => array(
					'v-bind:disabled' => '!confirmed',
				),
			),
		);

		$form = new \WP_Ultimo\UI\Form('total-actions', $fields, array(
			'views'                 => 'admin-pages/fields',
			'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
			'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
			'html_attr'             => array(
				'data-wu-app' => 'true',
				'data-state'  => wu_convert_to_state(array(
					'confirmed' => false,
				)),
			),
		));

		$form->render();

	} // end render_deactivation_form;

	/**
	 * Handle license activation form submission.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_deactivation_form() {

		$license = License::get_instance();

		if (!$this->deactivate()) {

			$activation_results = new \WP_Error('error', __('Error deactivating license.', 'wp-ultimo'));

			wp_send_json_error($activation_results);

		} // end if;

		wp_send_json_success(array(
			'redirect_url' => wu_get_current_url(),
		));

	} // end handle_deactivation_form;

	/**
	 * Sets up the activator instance.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function setup_activator() {

		if (!defined('WP_ULTIMO_PLUGIN_DIR') || defined('WP_TESTS_MULTISITE')) {

			return;

		} // end if;

		if (!(is_main_site()) && !is_network_admin()) {

			return;

		} // end if;

		if (!$this->allowed() && defined('WP_ULTIMO_LICENSE_KEY') && WP_ULTIMO_LICENSE_KEY) {
			/**
			 * Checks if init has run. If so, delay execution.
			 *
			 * @since 2.0.11
			 */
			$action = did_action('init') ? 'shutdown' : 'init';

			add_action($action, function() {

				$this->activate(WP_ULTIMO_LICENSE_KEY);

			});

		} // end if;

	} // end setup_activator;
 /**
  * Tries to perform a license activation.
  *
  * @since 2.0.0
  *
  * @param string $license_key The customer license key.
  * @return bool|\WP_Error
  */
 public function activate($license_key) {

		if (!$license_key) {

			return new \WP_Error('missing-license', __('License key is required.', 'wp-ultimo'));

		} // end if;

		try {

			$response = $this->license_api_request('/activate', array(
				'license_key'   => $license_key,
				'instance_name' => defined('DOMAIN_CURRENT_SITE') ? DOMAIN_CURRENT_SITE : get_home_url(wu_get_main_site_id()),
			), 'POST');

			if ($response->error) {

				return new \WP_Error('invalid-license', $response->error);

			} // end if;

			wu_save_option($this->option_key, $response);

		} catch (\Throwable $e) {

			wu_log_add('license', $e->getMessage(), LogLevel::ERROR);

			return new \WP_Error('general-error', __('An unexpected error occurred.', 'wp-ultimo'));

		} // end try;

		return true;

	} // end activate;

	/**
	 * Deactivated the current license.
	 *
	 * @since 2.0.11
	 * @return bool
	 */
	public function deactivate(): bool {

		try {

			$license = wu_get_option($this->option_key);

			if (!$license) {

				return false;

			} // end if;

			$response = $this->license_api_request('/deactivate', array(
				'license_key' => $license->secret_key,
				'instance_id' => $license->instance,
			), 'POST');

			wu_save_option($this->option_key, false);

		} catch (\Throwable $e) {

			return false;

		} // end try;

		return true;

	} // end deactivate;

	/**
	 * Checks if this copy of the plugin was activated.
	 *
	 * @since 2.0.0
	 *
	 * @todo: Check if we should use the plan here and if we should use the product ID.
	 *
	 * @param string $plan Plan to check against.
	 * @return bool
	 */
	public function allowed($plan = 'wpultimo') {

		$license = $this->get_license();

		if ($this->get_license() === false) {

			return false;

		} // end if;

		return $license->valid;

	} // end allowed;

	/**
	 * Returns the customer of the current license.
	 *
	 * @since 2.0.0
	 * @return object|false
	 */
	public function get_customer() {

		$license = $this->get_license();

		if ($license === false) {

			return false;

		} // end if;

		return $license->customer;

	} // end get_customer;

	/**
	 * Returns the license object.
	 *
	 * @since 2.0.0
	 * @return object|false
	 */
	public function get_license() {

		$license = wu_get_option($this->option_key, null) ?? $this->get_fs_license();

		if (!$license) {

			return false;

		} // end if;

		if ($license->timestamp + 86400 < time()) {

			try {

				$validated_license = $this->license_api_request('/validate', array(
					'license_key' => $license->secret_key,
					'instance_id' => $license->instance,
				), 'POST');

			} catch (\Throwable $e) {

				wu_log_add('license', $e->getMessage(), LogLevel::ERROR);

				return false;

			} // end try;

			$validated_license->secret_key = $license->secret_key;
			$validated_license->instance   = $license->instance;

			wu_save_option($this->option_key, $validated_license);

			$license = $validated_license;

		} // end if;

		return $license;

	} // end get_license;

	/**
	 * Returns a license based om freemius license.
	 *
	 * @return object|false
	 */
	protected function get_fs_license() {

		$account = get_blog_option(get_main_site_id(), 'fs_accounts');

		if (empty($account) || !isset($account['sites']) || !isset($account['sites']['wp-ultimo'])) {

			return false;

		} // end if;

		$account_site = get_object_vars($account['sites']['wp-ultimo']);
		$license_id   = $account_site['license_id'];
		$fs_accounts  = get_site_option('fs_accounts', array());
		$fs_id        = 2963;

		if (empty($fs_accounts) || !isset($fs_accounts['all_licenses']) || !isset($fs_accounts['all_licenses'][$fs_id])) {

			return false;

		} // end if;

		$licenses = $fs_accounts['all_licenses'][$fs_id];

		foreach ($licenses as $fs_license) {

			$fs_license = get_object_vars($fs_license);

			if ($fs_license['id'] === $license_id) {

				$license = new \stdClass();

				$license->timestamp  = 0;
				$license->secret_key = $fs_license['secret_key'];
				$license->instance   = $fs_license['id'];

				return $license;

			} // end if;

		} // end foreach;

		return false;

	} // end get_fs_license;
 /**
  * Returns the license key used to activate this copy.
  *
  * @since 2.0.0
  * @return string|false
  */
 public function get_license_key() {

		$license = $this->get_license();

		return $license && $license->secret_key ? $license->secret_key : false;

	} // end get_license_key;

	/**
	 * Checks if the white-label mode was activated.
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	public function is_whitelabel() {

		$license = $this->get_license();

		return $license ? $license->is_whitelabeled : false;

	} // end is_whitelabel;

	/**
	 * Inverse of the is_whitelabel. Used in callbacks.
	 *
	 * @since 2.0.0
	 * @return boolean
	 */
	public function is_not_whitelabel() {

		return !$this->is_whitelabel();

	} // end is_not_whitelabel;
 /**
  * Returns the license key set as constant if it exists.
  *
  * @since 2.0.0
  * @return false|string
  */
 public function has_license_key_defined_as_constant() {

		return defined('WP_ULTIMO_LICENSE_KEY') ? WP_ULTIMO_LICENSE_KEY : false;

	} // end has_license_key_defined_as_constant;

	/**
	 * Sends a request to license API
	 *
	 * @since  2.4.0
	 * @param  string $endpoint Endpoint to send the call to.
	 * @param  array  $data     Array containing the params to the call.
	 * @param  string $method   HTTP method to use.
	 * @return object
	 */
	protected function license_api_request(string $endpoint, array $data, $method = 'GET') {

		$data['version'] = wu_get_isset($data, 'version', wu_get_version());

		$url = 'https://licenses.nextpress.us/api/licenses' . $endpoint;

		$post_fields = array(
			'blocking' => true,
			'timeout'  => 10,
			'method'   => $method,
			'body'     => $data,
			'headers'  => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Accept'       => 'application/json',
			),
		);

		if ($method === 'GET') {

			$url = add_query_arg($data, $url);
			$post_fields['body'] = null;

		} // end if;

		$response = wp_remote_request($url, $post_fields);

		if (is_wp_error($response)) {

			throw new \Exception($response->get_error_message());

		} // end if;

		$body = json_decode(wp_remote_retrieve_body($response));

		if (json_last_error() !== JSON_ERROR_NONE) {

			throw new \Exception(json_last_error_msg());

		} // end if;

		return $this->build_license_from_response($body);

	} // end license_api_request;

	/**
	 * Build a license object from the API response.
	 *
	 * @since 2.4.0
	 *
	 * @param object $response Response from the API.
	 * @return object
	 */
	protected function build_license_from_response(object $response): object {

		$status = array(
			'active',
			'trial',
			'lifetime',
			'golden-ticket',
		);

		$license = new \stdClass();
		$license->is_whitelabeled = false;
		$license->timestamp       = time();
		$license->secret_key      = property_exists($response, 'key') ? $response->key : null;
		$license->valid           = property_exists($response, 'status') ? in_array($response->status, $status, true) : false;
		$license->instance        = property_exists($response, 'instance') ? $response->instance : null;
		$license->error           = property_exists($response, 'error') ? $response->error : null;

		$name = explode(' ', property_exists($response, 'customer') ? $response->customer->name : '', 2);

		$license->customer        = new \stdClass();
		$license->customer->email = property_exists($response, 'customer') ? $response->customer->email : null;
		$license->customer->first = $name[0];
		$license->customer->last  = isset($name[1]) ? $name[1] : '';

		if (!$license->valid) {

			$response->status = property_exists($response, 'status') ? $response->status : null;

			switch ($response->status) {
       case 'expired':
           $license->error = __('License key expired', 'wp-ultimo');
           break;
       case 'limit-quota':
           $license->error = __('License key limit reached', 'wp-ultimo');
           break;
       default:
           $license->error = __('Invalid license key', 'wp-ultimo');
           break;
   }

		} // end if;

		return $license;

	} // end build_license_from_response;

} // end class License;
