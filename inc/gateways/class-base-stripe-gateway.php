<?php
/**
 * Base Gateway.
 *
 * Base Gateway class. Should be extended to add new payment gateways.
 *
 * @package WP_Ultimo
 * @subpackage Managers/Site_Manager
 * @since 2.0.0
 */

namespace WP_Ultimo\Gateways;

use \Psr\Log\LogLevel;
use \WP_Ultimo\Gateways\Base_Gateway;
use \WP_Ultimo\Gateways\Ignorable_Exception;
use \Stripe;
use \WP_Ultimo\Models\Membership;
use \WP_Ultimo\Database\Payments\Payment_Status;
use \WP_Ultimo\Checkout\Cart;
use \WP_Ultimo\Checkout\Line_Item;
use \WP_Ultimo\Models\Site;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Base Gateway class. Should be extended to add new payment gateways.
 *
 * @since 2.0.0
 */
class Base_Stripe_Gateway extends Base_Gateway {

	/**
     * Allow gateways to declare multiple additional ids.
     *
     * These ids can be retrieved alongside the main id,
     * via the method get_all_ids().
     *
     * @since 2.0.7
     * @var array
     */
	protected $other_ids = array('stripe', 'stripe-checkout');

	/**
	 * Backwards compatibility for the old notify ajax url.
	 *
	 * @since 2.0.4
	 * @var bool|string
	 */
	protected $backwards_compatibility_v1_id = 'stripe';

	/**
	 * Holds the publishable API key provided by Stripe.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected $publishable_key;

	/**
	 * Holds the secret API key provided by Stripe.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected $secret_key;

	/**
	 * If we should request the billing address.
	 *
	 * @since 2.2.0
	 * @var bool
	 */
	protected $request_billing_address;

	/**
	 * If we are in test mode.
	 *
	 * @since 2.2.0
	 * @var bool
	 */
	protected $test_mode;

	/**
	 * The webhook event id.
	 *
	 * @since 2.2.0
	 * @var string
	 */
	protected $webhook_event_id;

	/**
	 * Declares support to recurring payments.
	 *
	 * @since 2.0.0
	 * @return true
	 */
	public function supports_recurring(): bool {

		return true;

	} // end supports_recurring;

	/**
	 * Declares support to subscription amount updates.
	 *
	 * @since 2.1.2
	 * @return true
	 */
	public function supports_amount_update(): bool {

		return true;

	} // end supports_amount_update;

	/**
	 * Get things going
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function init() {

		$id = wu_replace_dashes($this->get_id());

		$this->request_billing_address = true;

		/**
		 * As the toggle return a string with a int value,
		 * we need to convert this first to int then to bool.
		 */
		$this->test_mode = (bool) (int) wu_get_setting("{$id}_sandbox_mode", true);

		$this->setup_api_keys($id);

		if (method_exists('Stripe', 'setAppInfo')) {

			Stripe\Stripe::setAppInfo('WordPress WP Multisite WaaS', wu_get_version(), esc_url(site_url()));

		} // end if;

	} // end init;

	/**
	 * Setup api keys for stripe.
	 *
	 * @since 2.0.7
	 *
	 * @param string $id The gateway stripe id.
	 * @return void
	 */
	public function setup_api_keys($id = false) {

		$id = $id ? $id : wu_replace_dashes($this->get_id());

		if ($this->test_mode) {

			$this->publishable_key = wu_get_setting("{$id}_test_pk_key", '');
			$this->secret_key      = wu_get_setting("{$id}_test_sk_key", '');

		} else {

			$this->publishable_key = wu_get_setting("{$id}_live_pk_key", '');
			$this->secret_key      = wu_get_setting("{$id}_live_sk_key", '');

		} // end if;

		if ($this->secret_key && Stripe\Stripe::getApiKey() !== $this->secret_key) {

			Stripe\Stripe::setApiKey($this->secret_key);

			Stripe\Stripe::setApiVersion('2019-05-16');

		} // end if;

	} // end setup_api_keys;

	/**
	 * Adds additional hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function hooks() {

		add_action('wu_after_save_settings', array($this, 'install_webhook'), 10, 3);

		add_action('wu_after_save_settings', array($this, 'check_keys_status'), 10, 3);

		add_filter('wu_pre_save_settings', array($this, 'fix_saving_settings'), 10, 3);

		add_filter('wu_element_get_site_actions', array($this, 'add_site_actions'), 10, 4);

		/**
		 * We need to check if we should redirect after instantiate the Currents
		 */
		add_action('init', array($this, 'maybe_redirect_to_portal'), 11);
		add_action('wp', array($this, 'maybe_redirect_to_portal'), 11);

	} // end hooks;

	/**
	 * Adds Stripe Billing Portal link to the site actions.
	 *
	 * @since 2.1.2
	 *
	 * @param array      $actions    The site actions.
	 * @param array      $atts       The widget attributes.
	 * @param Site       $site       The current site object.
	 * @param Membership $membership The current membership object.
	 * @return array
	 */
	function add_site_actions($actions, $atts, $site, $membership) {

		$gateway_id = wu_replace_dashes($this->id);

		if (!wu_get_setting("{$gateway_id}_enable_portal")) {

			return $actions;

		} // end if;

		$payment_gateway = $membership ? $membership->get_gateway() : false;

		if (wu_get_isset($atts, 'show_change_payment_method') && in_array($payment_gateway, $this->other_ids, true)) {

			$s_subscription_id = $membership->get_gateway_subscription_id();

			if (!empty($s_subscription_id)) {

				$actions['change_payment_method'] = array(
					'label'        => __('Change Payment Method', 'wp-ultimo'),
					'icon_classes' => 'dashicons-wu-edit wu-align-middle',
					'href'         => add_query_arg(array(
						'wu-stripe-portal' => true,
						'membership'       => $membership->get_hash(),
					)),
				);

			} // end if;

		} // end if;

		return $actions;

	} // end add_site_actions;

	/**
	 * Maybe redirect to the Stripe Billing Portal.
	 *
	 * @since 2.1.2
	 * @return void
	 */
	public function maybe_redirect_to_portal() {

		if (!wu_request('wu-stripe-portal')) {

			return;

		} // end if;

		$membership = WP_Ultimo()->currents->get_membership();

		if (!$membership) {

			return;

		} // end if;

		$customer = wu_get_current_customer();

		if (!is_super_admin() && (!$customer || $customer->get_id() !== $membership->get_customer_id())) {

			wp_die(__('You are not allowed to modify this membership.', 'wp-ultimo'));

		} // end if;

		$gateway_id = $membership->get_gateway();

		$gateway = wu_get_gateway($gateway_id);

		$allowed_payment_method_types = apply_filters('wu_stripe_checkout_allowed_payment_method_types', array(
			'card',
		), $gateway);

		$customer_id   = $membership->get_customer_id();
		$s_customer_id = $membership->get_gateway_customer_id();
		$return_url    = remove_query_arg('wu-stripe-portal', wu_get_current_url());

		// If customer is not set, get from checkout session
		if (empty($s_customer_id)) {

			$subscription_data = array(
				'payment_method_types'       => $allowed_payment_method_types,
				'mode'                       => 'setup',
				'success_url'                => $return_url,
				'cancel_url'                 => wu_get_current_url(),
				'billing_address_collection' => 'required',
				'client_reference_id'        => $customer_id,
				'customer'                   => $s_customer_id
			);

			$session       = Stripe\Checkout\Session::create($subscription_data);
			$s_customer_id = $session->subscript_ion_data['customer'];

		} // end if;

		$portal_config_id = get_site_option('wu_stripe_portal_config_id');

		if (!$portal_config_id) {

			$portal_config = Stripe\BillingPortal\Configuration::create(array(
				'features'         => array(
					'invoice_history'       => array(
						'enabled' => true,
					),
					'payment_method_update' => array(
						'enabled' => true,
					),
					'subscription_cancel'   => array(
						'enabled'             => true,
						'mode'                => 'at_period_end',
						'cancellation_reason' => array(
							'enabled' => true,
							'options' => array(
								'too_expensive',
								'missing_features',
								'switched_service',
								'unused',
								'customer_service',
								'too_complex',
								'other',
							),
						),
					),
				),
				'business_profile' => array(
					'headline' => __('Manage your membership payment methods.', 'wp-ultimo'),
				),
			));

			$portal_config_id = $portal_config->id;

			update_site_option('wu_stripe_portal_config_id', $portal_config_id);

		} // end if;

		$subscription_data = array(
			'return_url'    => $return_url,
			'customer'      => $s_customer_id,
			'configuration' => $portal_config_id,
		);

		$session = Stripe\BillingPortal\Session::create($subscription_data);

		wp_redirect($session->url);
		exit;

	} // end maybe_redirect_to_portal;

	/**
	 * Allows Gateways to override the gateway title.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_public_title() {

		$gateway_id = wu_replace_dashes($this->id);

		return wu_get_setting("{$gateway_id}_public_title", __('Credit Card', 'wp-ultimo'));

	} // end get_public_title;

	/**
	 * Adds the Stripe Gateway settings to the settings screen.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function settings() {

		$gateway_id = wu_replace_dashes($this->id);

		wu_register_settings_field('payment-gateways', "{$gateway_id}_enable_portal", array(
			'title'      => __('Use Stripe Billing Portal', 'wp-ultimo'),
			'desc'       => 'Add a link to the Billing Portal in the site actions widget so your customer can change the payment method used in Stripe (additional charges from Stripe could be applied).',
			'type'       => 'toggle',
			'default'    => 0,
			'capability' => 'manage_api_keys',
			'require'    => array(
				'active_gateways' => $this->get_id(),
			),
		));

	} // end settings;

	/**
	 * Checks if we already have a webhook listener installed.
	 *
	 * @since 2.0.0
	 * @return WebhookEndpoint|\WP_Error|false
	 */
	public function has_webhook_installed() {

		try {

			$webhook_url = $this->get_webhook_listener_url();

			$this->setup_api_keys();

			$search_webhook = Stripe\WebhookEndpoint::all(array(
				'limit' => 100,
			));

			$set_webhook_endpoint = false;

			foreach ($search_webhook as $webhook_endpoint) {

				if ($webhook_endpoint->url === $webhook_url) {

					return $webhook_endpoint;

				} // end if;

			} // end foreach;

		} catch (\Throwable $e) {

			$error_code = $e->getCode();

			// WP Error did not handle empty error code
			if (empty($error_code)) {

				if (method_exists($e, 'getHttpStatus')) {

					$error_code = $e->getHttpStatus();

				} else {

					$error_code = 500;

				} // end if;

			} // end if;

			return new \WP_Error($error_code, $e->getMessage());

		} // end try;

		return false;

	} // end has_webhook_installed;

	/**
	 * Fix stripe settings
	 *
	 * @since 2.0.18
	 *
	 * @param array $settings The final settings array being saved, containing ALL options.
	 * @param array $settings_to_save Array containing just the options being updated.
	 * @param array $saved_settings Array containing the original settings.
	 * @return array
	 */
	public function fix_saving_settings($settings, $settings_to_save, $saved_settings) {

		$id = wu_replace_dashes($this->get_id());

		$active_gateways = (array) wu_get_isset($settings_to_save, 'active_gateways', array());

		if (!in_array($this->get_id(), $active_gateways, true)) {

			return $settings;

		} // end if;

		if (!isset($settings_to_save["{$id}_sandbox_mode"])) {

			$settings["{$id}_sandbox_mode"] = false;

		} // end if;

		// Unset webhook url to show the get_webhook_listener_url value to customer
		unset($settings["{$id}_webhook_listener_explanation"]);

		return $settings;

	} // end fix_saving_settings;

	/**
	 * Check stripe API keys
	 *
	 * @since 2.0.18
	 *
	 * @param array $settings The final settings array being saved, containing ALL options.
	 * @param array $settings_to_save Array containing just the options being updated.
	 * @param array $saved_settings Array containing the original settings.
	 * @return void
	 */
	public function check_keys_status($settings, $settings_to_save, $saved_settings) {

		$id = wu_replace_dashes($this->get_id());

		$active_gateways = (array) wu_get_isset($settings_to_save, 'active_gateways', array());

		if (!in_array($this->get_id(), $active_gateways, true)) {

			return;

		} // end if;

		$stripe_mode = (bool) (int) $settings["{$id}_sandbox_mode"] ? 'test' : 'live';

		/*
		 * Checked if the Stripe Settings changed, so we can install webhooks.
		 */
		$changed_settings = array(
			$settings["{$id}_sandbox_mode"],
			$settings["{$id}_{$stripe_mode}_pk_key"],
			$settings["{$id}_{$stripe_mode}_sk_key"],
		);

		$original_settings = array(
			$saved_settings["{$id}_sandbox_mode"],
			$saved_settings["{$id}_{$stripe_mode}_pk_key"],
			$saved_settings["{$id}_{$stripe_mode}_sk_key"],
		);

		if ($changed_settings == $original_settings) { // phpcs:ignore

			return;

		} // end if;

		try {

			Stripe\Stripe::setApiKey($settings["{$id}_{$stripe_mode}_sk_key"]);

			Stripe\Token::create(array(
				'card' => array(
					'number'    => '4242424242424242',
					'exp_month' => 7,
					'exp_year'  => 2028,
					'cvc'       => '314',
				),
			));

			wu_save_setting("{$id}_{$stripe_mode}_sk_key_status", '');

		} catch (\Throwable $e) {

			if (strncmp($e->getMessage(), 'Invalid API Key provided', strlen('Invalid API Key provided')) === 0) {
				/**
				 *  The secret key is invalid;
				 */
				$t = "{$id}_{$stripe_mode}_sk_key_status";
				wu_save_setting("{$id}_{$stripe_mode}_sk_key_status", __('Invalid API Key provided', 'wp-ultimo'));

			} // end if;

		} // end try;

		try {

			Stripe\Stripe::setApiKey($settings["{$id}_{$stripe_mode}_pk_key"]);

			Stripe\Token::create(array(
				'card' => array(
					'number'    => '4242424242424242',
					'exp_month' => 7,
					'exp_year'  => 2028,
					'cvc'       => '314',
				),
			));

			wu_save_setting("{$id}_{$stripe_mode}_pk_key_status", '');

		} catch (\Throwable $e) {

			if (strncmp($e->getMessage(), 'Invalid API Key provided', strlen('Invalid API Key provided')) === 0) {
				/**
				 *  The public key is invalid;
				 */
				wu_save_setting("{$id}_{$stripe_mode}_pk_key_status", __('Invalid API Key provided', 'wp-ultimo'));

			} // end if;

		} // end try;

	} // end check_keys_status;

	/**
	 * Installs webhook urls onto Stripe.
	 *
	 * WP Multisite WaaS will call this whenever settings for this api changes.
	 * That being said, it might be a good idea to check if the webhook already exists
	 * before trying to re-create it.
	 *
	 * Return true for success, or a \WP_Error instance in case of failure.
	 *
	 * @since 2.0.0
	 *
	 * @param array $settings The final settings array being saved, containing ALL options.
	 * @param array $settings_to_save Array containing just the options being updated.
	 * @param array $saved_settings Array containing the original settings.
	 * @return bool|\WP_Error
	 */
	public function install_webhook($settings, $settings_to_save, $saved_settings) {

		$id = wu_replace_dashes($this->get_id());

		$active_gateways = (array) wu_get_isset($settings_to_save, 'active_gateways', array());

		if (!in_array($this->get_id(), $active_gateways, true)) {

			return false;

		} // end if;

		/*
		 * Checked if the Stripe Settings changed, so we can install webhooks.
		 */
		$changed_settings = array(
			$settings["{$id}_sandbox_mode"],
			$settings["{$id}_test_pk_key"],
			$settings["{$id}_test_sk_key"],
			$settings["{$id}_live_pk_key"],
			$settings["{$id}_live_sk_key"],
		);

		$original_settings = array(
			$saved_settings["{$id}_sandbox_mode"],
			$saved_settings["{$id}_test_pk_key"],
			$saved_settings["{$id}_test_sk_key"],
			$saved_settings["{$id}_live_pk_key"],
			$saved_settings["{$id}_live_sk_key"],
		);

		if ($changed_settings == $original_settings) { // phpcs:ignore

			return false;

		} // end if;

		$webhook_url = $this->get_webhook_listener_url();

		$existing_webhook = $this->has_webhook_installed();

		if (is_wp_error($existing_webhook)) {

			return $existing_webhook;

		} // end if;

		$this->setup_api_keys($id);

		try {
			/*
			 * If already exists, checks for status
			 */
			if ($existing_webhook) {

				if ($existing_webhook->status === 'disabled') {

					$status = Stripe\WebhookEndpoint::update($existing_webhook->id, array(
						'status' => 'enabled',
					));

				} // end if;

				return true;

			} // end if;

			/*
			 * Otherwise, create it.
			 */
			Stripe\WebhookEndpoint::create(array(
				'enabled_events' => array('*'),
				'url'            => $webhook_url,
				'description'    => 'Added by WP Multisite WaaS. Required to correctly handle changes in subscription status.',
			));

			return true;

		} catch (\Throwable $e) {

			$error_code = $e->getCode();

			// WP Error did not handle empty error code
			if (empty($error_code)) {

				if (method_exists($e, 'getHttpStatus')) {

					$error_code = $e->getHttpStatus();

				} else {

					$error_code = 500;

				} // end if;

			} // end if;

			return new \WP_Error($error_code, $e->getMessage());

		} // end try;

	} // end install_webhook;

	/**
	 * Reflects membership changes on the gateway.
	 *
	 * By default, this method will process tha cancellation of current gateway subscription
	 *
	 * @since 2.1.3
	 *
	 * @param \WP_Ultimo\Models\Membership $membership The membership object.
	 * @param \WP_Ultimo\Models\Customer   $customer   The customer object.
	 * @return bool|\WP_Error true if it's all done or error object if something went wrong.
	 */
	public function process_membership_update(&$membership, $customer) {

		$gateway_subscription_id = $membership->get_gateway_subscription_id();

		if (empty($gateway_subscription_id)) {

			return new \WP_Error('wu_stripe_no_subscription_id', __('Error: No gateway subscription ID found for this membership.', 'wp-ultimo'));

		} // end if;

		$this->setup_api_keys();

		try {

			$subscription = Stripe\Subscription::retrieve($gateway_subscription_id);

			/**
			 * Generate a temporary wu payment so we can get the correct line items and amounts.
			 * It's important to note that we should only get recurring payments so we can correctly update the subscription.
			 */
			$temp_payment = wu_membership_create_new_payment($membership, false, true, false);

			$line_items = $temp_payment->get_line_items();

			$recurring_items = array();

			$credits = array();

			$s_coupon = '';

			foreach ($line_items as $line_item) {

				if ($line_item->get_total() < 0) {

					$credits[] = array(
						'amount'      => $line_item->get_total(),
						'description' => $line_item->get_title(),
					);

					continue;

				} // end if;

				$sub_total = $line_item->get_quantity() * $line_item->get_unit_price();
				$discounts = $line_item->calculate_discounts($sub_total);

				$discounted_subtotal = $sub_total - $discounts;

				// We will probably never enter here but just in case.
				if ($discounted_subtotal < 0) {

					continue;

				} // end if;

				$tax_behavior = '';
				$s_tax_rate   = false;

				if ($line_item->is_taxable() && !empty($line_item->get_tax_rate())) {

					$tax_behavior = $line_item->get_tax_inclusive() ? 'inclusive' : 'exclusive';

					$tax_args = array(
						'country'   => $membership->get_billing_address()->billing_country,
						'tax_rate'  => $line_item->get_tax_rate(),
						'type'      => $line_item->get_tax_type(),
						'title'     => $line_item->get_tax_label(),
						'inclusive' => $line_item->get_tax_inclusive(),
					);

					$s_tax_rate = $this->maybe_create_tax_rate($tax_args);

				} // end if;

				$s_price = $this->maybe_create_price(
					$line_item->get_title(),
					$discounted_subtotal,
					$membership->get_currency(),
					$line_item->get_quantity(),
					$membership->get_duration(),
					$membership->get_duration_unit(),
					$tax_behavior
				);

				$recurring_item = array(
					'price' => $s_price,
				);

				if ($s_tax_rate) {

					$recurring_item['tax_rates'] = array($s_tax_rate);

				} // end if;

				$recurring_items[] = $recurring_item;

			} // end foreach;

			if (!empty($credits)) {

				if (count($credits) > 1) {

					$credit = array(
						'amount'      => array_sum(wp_list_pluck($credits, 'amount')),
						'description' => __('Amount adjustment based on custom deal.', 'wp-ultimo'),
					);

				} else {

					$credit = $credits[0];

				} // end if;

				$s_amount = - round($credit['amount'] * wu_stripe_get_currency_multiplier());

				if ($s_amount >= 1) {

					$currency = strtolower($membership->get_currency());

					$coupon_data = array(
						'id'         => sprintf('%s-%s-%s', $s_amount, $currency, 'forever'),
						'name'       => $credit['description'],
						'amount_off' => $s_amount,
						'duration'   => 'forever',
						'currency'   => $currency,
					);

					$s_coupon = $this->get_stripe_coupon($coupon_data);

				}

			} // end if;

			$existing_items = array_map(fn($item) => array(
				'id'      => $item->id,
				'deleted' => true
			), $subscription->items->data);

			$update_data = array(
				'items'              => array_merge($recurring_items, $existing_items),
				'proration_behavior' => 'none',
				'coupon'             => $s_coupon,
			);

			$subscription = Stripe\Subscription::update($gateway_subscription_id, $update_data);

			if (empty($s_coupon) && !empty($subscription->discount)) {

				$stripe = new Stripe\StripeClient($this->secret_key);
				$stripe->subscriptions->deleteDiscount($gateway_subscription_id);

			} // end if;

		} catch (\Throwable $e) {

			return new \WP_Error('wu_stripe_update_error', $e->getMessage());

		} // end try;

		return true;

	} // end process_membership_update;

	/**
	 * Run preparations before checkout processing.
	 *
	 * This runs during the checkout form validation
	 * and it is a great chance to do preflight stuff
	 * if the gateway requires it.
	 *
	 * If you return an array here, Ultimo
	 * will append the key => value of that array
	 * as hidden fields to the checkout field,
	 * and those get submitted with the rest of the form.
	 *
	 * As an example, this is how we create payment
	 * intents for Stripe to make the experience more
	 * streamlined.
	 *
	 * @since 2.0.0
	 * @return void|array
	 */
	public function run_preflight() {} // end run_preflight;
 /**
  * Get or create Stripe Customer.
  *
  * @since 2.0.0
  *
  * @param integer $customer_id WP Multisite WaaS customer ID.
  * @param integer $user_id The WordPress user ID.
  * @param integer $stripe_customer_id The Stripe Customer ID.
  * @return \Stripe\Customer|\WP_Error
  */
 public function get_or_create_customer($customer_id = 0, $user_id = 0, $stripe_customer_id = 0) {
		/*
		 * Sets flag to control if we need
		 * to create a new customer or not.
		 */
		$customer_exists = false;

		/*
		 * Use the WP Multisite WaaS customer ID to search on the
		 * database for an existing Stripe customer id.
		 */
		if (empty($stripe_customer_id)) {

			$stripe_customer_id = wu_get_customer_gateway_id($customer_id, array('stripe', 'stripe-checkout'));

		} // end if;

		/**
		 * Ensure the correct api keys are set
		 */
		$this->setup_api_keys();

		/*
		 * We found a Stripe Customer ID!
		 *
		 * Now we have to use it to try and retrieve a
		 * stripe customer object.
		 */
		if ($stripe_customer_id) {

			try {

				$stripe_customer = Stripe\Customer::retrieve($stripe_customer_id);

				/*
				 * If the customer was deleted, we
				 * cannot use it again...
				 */
				if (!isset($stripe_customer->deleted) || !$stripe_customer->deleted) {

					$customer_exists = true;

				} // end if;

			} catch (\Exception $e) {

				/**
				 * Silence is golden.
				 */

			} // end try;

		} // end if;

		/*
		 * No customer found.
		 *
		 * In this scenario, we'll need to create a new one.
		 */
		if (empty($customer_exists)) {

			try {
				/*
				 * Pass the name and email to stripe.
				 */
				$customer_args = array(
					'email'   => $this->customer->get_email_address(),
					'name'    => $this->customer->get_display_name(),
					'address' => $this->convert_to_stripe_address($this->customer->get_billing_address()),
				);

				/*
				 * Filters the customer creation arguments.
				 */
				$customer_args = apply_filters('wu_stripe_customer_create_args', $customer_args, $this);

				/*
				 * Finally, try to create it.
				 */
				$stripe_customer = Stripe\Customer::create($customer_args);

			} catch (\Exception $e) {

				$error_code = $e->getCode();

				// WP Error did not handle empty error code
				if (empty($error_code)) {

					if (method_exists($e, 'getHttpStatus')) {

						$error_code = $e->getHttpStatus();

					} else {

						$error_code = 500;

					} // end if;

				} // end if;

				return new \WP_Error($error_code, $e->getMessage());

			} // end try;

		} // end if;

		return $stripe_customer;

	} // end get_or_create_customer;

	/**
	 * Convert our billing address to the format Stripe is expecting.
	 *
	 * @since 2.0.11
	 *
	 * @param \WP_Ultimo\Objects\Billing_Address $billing_address The WP Multisite WaaS billing address.
	 * @return array
	 */
	public function convert_to_stripe_address($billing_address) {

		return array(
			'city'        => $billing_address->billing_city,
			'country'     => $billing_address->billing_country,
			'line1'       => $billing_address->billing_address_line_1,
			'line2'       => $billing_address->billing_address_line_2,
			'postal_code' => $billing_address->billing_zip_code,
			'state'       => $billing_address->billing_state,
		);

	} // end convert_to_stripe_address;

	/**
	 * Returns an array with customer meta data.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function get_customer_metadata() {

		$meta_data = array(
			'key'           => $this->membership->get_id(),
			'email'         => $this->customer->get_email_address(),
			'membership_id' => $this->membership->get_id(),
			'customer_id'   => $this->customer->get_id(),
			'payment_id'    => $this->payment->get_id(),
		);

		return $meta_data;

	} // end get_customer_metadata;

	/**
	 * Process a checkout.
	 *
	 * It takes the data concerning
	 * a new checkout and process it.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Ultimo\Models\Payment    $payment The payment associated with the checkout.
	 * @param \WP_Ultimo\Models\Membership $membership The membership.
	 * @param \WP_Ultimo\Models\Customer   $customer The customer checking out.
	 * @param \WP_Ultimo\Checkout\Cart     $cart The cart object.
	 * @param string                       $type The checkout type. Can be 'new', 'retry', 'upgrade', 'downgrade', 'addon'.
	 *
	 * @throws \Exception When a stripe API error is caught.
	 *
	 * @return void
	 */
	public function process_checkout($payment, $membership, $customer, $cart, $type) {} // end process_checkout;

	/**
	 * Create a recurring subscription in Stripe.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Ultimo\Models\Membership $membership The membership.
	 * @param \WP_Ultimo\Checkout\Cart     $cart The cart object.
	 * @param Stripe\PaymentMethod         $payment_method The save payment method on Stripe.
	 * @param Stripe\Customer              $s_customer The Stripe customer.
	 *
	 * @return Stripe\Subscription|bool The Stripe subscription object or false if the creation is running in another process.
	 */
	protected function create_recurring_payment($membership, $cart, $payment_method, $s_customer) {
		/**
		 * First we need to ensure that this process is not running in another place.
		 */
		$internal_key = "wu_stripe_recurring_creation_{$membership->get_id()}";

		$has_transient = get_site_transient($internal_key);

		if ($has_transient) {
			/**
			 * Process already start at another point (webhook or sync call).
			 */
			return false;

		} // end if;

		/**
		 * Set transient to avoid multiple calls.
		 */
		set_site_transient($internal_key, true, 120);

		/*
		 * We need to create a cart description that Stripe understands.
		 */
		$stripe_cart = $this->build_stripe_cart($cart);

		/*
		 * The cart creation process might run into
		 * errors, and in that case, it will
		 * return a WP_Error object.
		 */
		if (is_object($stripe_cart) && is_wp_error($stripe_cart)) {

			throw new \Exception($stripe_cart->get_error_message());

		} // end if;

		// Otherwise, use the calculated expiration date of the membership, modified to current time instead of 23:59.
		$billing_date = $cart->get_billing_start_date();
		$base_date    = $billing_date ? $billing_date : $cart->get_billing_next_charge_date();
		$datetime     = \DateTime::createFromFormat('U', $base_date);
		$current_time = getdate();

		$datetime->setTime($current_time['hours'], $current_time['minutes'], $current_time['seconds']);

		$start_date = $datetime->getTimestamp() - HOUR_IN_SECONDS; // Reduce by 60 seconds to account for inaccurate server times.

		if (empty($payment_method)) {

			throw new \Exception(__('Invalid payment method', 'wp-ultimo'));

		} // end if;

		/*
		 * Subscription arguments for Stripe
		 */
		$sub_args = array(
			'items'                  => array_values($stripe_cart),
			'default_payment_method' => $payment_method->id,
			'prorate'                => false,
			'metadata'               => $this->get_customer_metadata(),
		);

		/*
		 * Now determine if we use `trial_end` or `billing_cycle_anchor` to schedule the start of the
		 * subscription.
		 *
		 * If this is an actual trial, then we use `trial_end`.
		 *
		 * Otherwise, billing cycle anchor is preferable because that works with Stripe MRR.
		 * However, the anchor date cannot be further in the future than a normal billing cycle duration.
		 * If that's the case, then we have to use trial end instead.
		 */
		$stripe_max_anchor = $this->get_stripe_max_billing_cycle_anchor($cart->get_duration(), $cart->get_duration_unit(), 'now');

		if ($cart->has_trial() || $start_date > $stripe_max_anchor->getTimestamp()) {

			$sub_args['trial_end'] = $start_date;

		} else {

			$sub_args['billing_cycle_anchor'] = $start_date;

		} // end if;

		/*
		 * Sets the billing anchor.
		 */
		$set_anchor = isset($sub_args['billing_cycle_anchor']);

		/**
		 *  If we have a nun recurring discount code we need to add here to use in first payment.
		 */
		if ($cart->has_trial()) {
			/**
			 * If we have pro-rata credit (in case of an upgrade, for example)
			 * try to create a custom coupon.
			 */
			$s_coupon = $this->get_credit_coupon($cart);

			if ($s_coupon) {

				$sub_args['coupon'] = $s_coupon;

			} // end if;

		} // end if;

		/*
		 * Filters the Stripe subscription arguments.
		 */
		$sub_args = apply_filters('wu_stripe_create_subscription_args', $sub_args, $this);

		/*
		 * If we have a `billing_cycle_anchor` AND a `trial_end`, then we need to unset whichever one
		 * we set, and leave the customer's custom one in tact.
		 *
		 * This is done to account for people who filter the arguments to customize the next bill
		 * date. If `trial_end` is used in conjunction with `billing_cycle_anchor` then it will create
		 * unexpected results and the next bill date will not be what they want.
		 *
		 * This may not be completely perfect but it's the best way to try to account for any errors.
		 */
		if (!empty($sub_args['trial_end']) && !empty($sub_args['billing_cycle_anchor'])) {
			/*
			 * If we set an anchor, remove that, because
			 * this means the customer has set their own `trial_end`.
			 */
			if ($set_anchor) {

				unset($sub_args['billing_cycle_anchor']);

			} else {
				/*
				 * We set a trial, which means the customer
				 * has set their own `billing_cycle_anchor`.
				 */
				unset($sub_args['trial_end']);

			} // end if;

		} // end if;

		$sub_options = apply_filters('wu_stripe_create_subscription_options', array(
			'idempotency_key' => wu_stripe_generate_idempotency_key($sub_args),
		));

		try {
			/*
			 * Tries to create the subscription
			 * on Stripe!
			 */
			$subscription = $s_customer->subscriptions->create($sub_args, $sub_options);

		} catch (Stripe\Exception\IdempotencyException $exception) {
			/**
			 * In this case, the subscription is being created by another call.
			 */
			return false;

		} // end try;

		// If we have a trial we need to add fees to next invoice.
		if ($cart->has_trial()) {

			$currency = strtolower($cart->get_currency());

			$fees = array_filter($cart->get_line_items_by_type('fee'), fn($fee) => !$fee->is_recurring());

			$s_fees = array();

			foreach ($fees as $fee) {

				$amount = $fee->get_quantity() * $fee->get_unit_price();

				$tax_behavior = '';
				$s_tax_rate   = false;

				if ($fee->is_taxable() && !empty($fee->get_tax_rate())) {

					$tax_behavior = $fee->get_tax_inclusive() ? 'inclusive' : 'exclusive';

					$tax_args = array(
						'country'   => $membership->get_billing_address()->billing_country,
						'tax_rate'  => $fee->get_tax_rate(),
						'type'      => $fee->get_tax_type(),
						'title'     => $fee->get_tax_label(),
						'inclusive' => $fee->get_tax_inclusive(),
					);

					$s_tax_rate = $this->maybe_create_tax_rate($tax_args);

				} // end if;

				$s_price = $this->maybe_create_price(
					$fee->get_title(),
					$amount,
					$currency,
					1,
					false,
					false,
					$tax_behavior,
				);

				$s_fee = array(
					'price' => $s_price,
				);

				if ($s_tax_rate) {

					$s_fee['tax_rates'] = array($s_tax_rate);

				} // end if;

				$s_fees[] = $s_fee;

			} // end foreach;

			if (!empty($s_fees)) {

				$options = array(
					'add_invoice_items' => $s_fees,
				);

				$sub_options = array(
					'idempotency_key' => wu_stripe_generate_idempotency_key(array_merge(array('s_subscription' => $subscription->id), $options)),
				);

				try {

					$subscription = Stripe\Subscription::update($subscription->id, $options, $sub_options);

				} catch (Stripe\Exception\IdempotencyException $exception) {
					/**
					 * In this case, the subscription is being updated by another call.
					 */
					return false;

				} // end try;

			} // end if;

		} // end if;

		return $subscription;

	} // end create_recurring_payment;
 /**
  * Checks if we need to create a pro-rate/credit coupon based on the cart data.
  *
  * Will return an array with coupon arguments for stripe if
  * there is credit to be added and false if not.
  *
  * @since 2.0.0
  *
  * @param \WP_Ultimo\Checkout\Cart $cart The current cart.
  * @return string|false
  */
 protected function get_credit_coupon($cart) {

		$amount = 0;

		foreach ($cart->get_line_items() as $line_item) {

			if ($line_item->get_total() < 0) {

				$amount += $line_item->get_total();

			} elseif (!$line_item->should_apply_discount_to_renewals()) {

				$amount += - $line_item->get_discount_total();

			} // end if;

		} // end foreach;

		if (empty($amount)) {

			return false;

		} // end if;

		$s_amount = - round($amount * wu_stripe_get_currency_multiplier());
		$currency = strtolower($cart->get_currency());

		$coupon_data = array(
			'id'         => sprintf('%s-%s-%s', $s_amount, $currency, 'once'),
			'name'       => __('Account credit and other discounts', 'wp-ultimo'),
			'amount_off' => $s_amount,
			'duration'   => 'once',
			'currency'   => $currency,
		);

		return $this->get_stripe_coupon($coupon_data);

	} // end get_credit_coupon;

	/**
	 * Checks to see if the coupon exists, and if so, returns the ID of
	 * that coupon. If not, a new coupon is created.
	 *
	 * @since 2.0.18
	 *
	 * @param array $coupon_data The cart/order object.
	 * @return string
	 */
	protected function get_stripe_coupon($coupon_data) {

		// First check to see if a coupon exists with this ID. If so, return that.
		try {

			$coupon = Stripe\Coupon::retrieve($coupon_data['id']);

			Stripe\Coupon::update($coupon->id, array(
				'name' => $coupon_data['name'],
			));

			return $coupon->id;

		} catch (\Exception $e) {

			// silence is golden

		} // end try;

		// Otherwise, create a new plan.
		try {

			$coupon = Stripe\Coupon::create($coupon_data);

			return $coupon->id;

		} catch (\Exception $e) {

			$error_code = $e->getCode();

			// WP Error did not handle empty error code
			if (empty($error_code)) {

				if (method_exists($e, 'getHttpStatus')) {

					$error_code = $e->getHttpStatus();

				} else {

					$error_code = 500;

				} // end if;

			} // end if;

			wu_log_add('stripe', sprintf('Error creating Stripe coupon. Code: %s; Message: %s', $error_code, $e->getMessage()), LogLevel::ERROR);

			throw $e;

		} // end try;

	} // end get_stripe_coupon;
	/**
	 * Builds the non-recurring list of items to be paid on Stripe.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Ultimo\Checkout\Cart $cart The cart/order object.
	 * @param bool                     $include_recurring_products If we should include recurring items as non-recurring.
	 */
	protected function build_non_recurring_cart($cart, $include_recurring_products = false): array {

		$cart_items = array();

		foreach ($cart->get_line_items() as $line_item) {
			/*
			 * Skip recurring items
			 */
			if ($line_item->is_recurring() && $include_recurring_products === false) {

				continue;

			} // end if;

			/*
			 * Skip negative items.
			 * In cases like this, we need to generate a coupon code.
			 */
			if ($line_item->get_unit_price() < 0) {

				continue;

			} // end if;

			$cart_items[$line_item->get_id()] = array(
				'name'     => $line_item->get_title(),
				'quantity' => $line_item->get_quantity(),
				'amount'   => $line_item->get_unit_price() * wu_stripe_get_currency_multiplier(),
				'currency' => strtolower($cart->get_currency()),
			);

			$description = $line_item->get_description();

			if (!empty($description)) {

				$cart_items[$line_item->get_id()]['description'] = $description;

			} // end if;

			/*
			 * Now, we handle the taxable status
			 * of the payment.
			 *
			 * We might need to create tax rates on
			 * Stripe and apply it on the subscription cart.
			 */
			if ($line_item->is_taxable() && !empty($line_item->get_tax_rate())) {

				$tax_args = array(
					'country'   => $this->membership->get_billing_address()->billing_country,
					'tax_rate'  => $line_item->get_tax_rate(),
					'type'      => $line_item->get_tax_type(),
					'title'     => $line_item->get_tax_label(),
					'inclusive' => $line_item->get_tax_inclusive(),
				);

				$cart_items[$line_item->get_id()]['tax_rates'] = array($this->maybe_create_tax_rate($tax_args));

			} // end if;

		} // end foreach;

		return array_values($cart_items);

	} // end build_non_recurring_cart;

	/**
	 * Converts the WP Multisite WaaS cart into Stripe Sub arguments.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Ultimo\Checkout\Cart $cart The cart object.
	 * @return array
	 */
	protected function build_stripe_cart($cart) {
		/*
		 * Set up a recurring subscription in Stripe with
		 * a delayed start date.
		 *
		 * All start dates are delayed one cycle because we use a
		 * one-time payment for the first charge.
		 */
		$plans = array();

		$all_products = $cart->get_all_products();

		foreach ($cart->get_line_items() as $line_item) {

			$product = $line_item->get_product();

			if (!$product) {

				continue;

			} // end if;

			/*
			 * Exclude products that are not recurring.
			 */
			if (!$product->is_recurring()) {

				continue;

			} // end if;

			$amount = $product->get_amount();

			$discount_code = $cart->get_discount_code();

			if ($discount_code) {

				if ($discount_code->should_apply_to_renewals() && $cart->get_cart_type() !== 'renewal') {

					$amount = wu_get_discounted_price($amount, $discount_code->get_value(), $discount_code->get_type(), false);

				} // end if;

			} // end if;

			try {
				/*
				 * We might need to create the plan on Stripe.
				 * Otherwise, we'll get the stripe plan id in here.
				 */
				$plan_id = $this->maybe_create_plan(array(
					'name'           => $product->get_name(),
					'price'          => $amount,
					'interval'       => $product->get_duration_unit(),
					'interval_count' => $product->get_duration(),
				));

				if (is_wp_error($plan_id)) {

					return $plan_id;

				} // end if;

				/*
				 * Adds the new plan ID to the subscription cart.
				 */
				$plans[$plan_id] = array(
					'plan' => $plan_id,
				);

			} catch (\Exception $e) {

				$error_message = sprintf('Failed to create subscription for membership #%d. Message: %s', $this->membership->get_id(), $e->getMessage());

				return new \WP_Error('plan-creation-failed', $error_message);

			} // end try;

			/*
			 * Now, we handle the taxable status
			 * of the payment.
			 *
			 * We might need to create tax rates on
			 * Stripe and apply it on the subscription cart.
			 */
			if ($line_item->is_taxable() && !empty($line_item->get_tax_rate())) {

				$tax_args = array(
					'country'   => $this->membership->get_billing_address()->billing_country,
					'tax_rate'  => $line_item->get_tax_rate(),
					'type'      => $line_item->get_tax_type(),
					'title'     => $line_item->get_tax_label(),
					'inclusive' => $line_item->get_tax_inclusive(),
				);

				$plans[$plan_id]['tax_rates'] = array($this->maybe_create_tax_rate($tax_args));

			} // end if;

		} // end foreach;

		return $plans;

	} // end build_stripe_cart;

	/**
	 * Converts the Stripe invoice line items into WP Multisite WaaS line items.
	 *
	 * @since 2.0.19
	 *
	 * @param Stripe\InvoiceLineItem[] $invoice_line_items The line items array.
	 * @return array
	 */
	protected function get_ultimo_line_items_from_invoice($invoice_line_items) {

		$line_items = array();

		$membership_products = array();

		if ($this->membership) {

			$m_products = $this->membership->get_all_products();

			foreach ($m_products as $array) {

				$membership_products[$array['product']->get_name()] = $array['product'];

			} // end foreach;

		} // end if;

		foreach ($invoice_line_items as $s_line_item) {

			$currency = strtoupper((string) $s_line_item->currency);

			$currency_multiplier = wu_stripe_get_currency_multiplier($currency);

			$quantity = $s_line_item->quantity;

			$description_pattern = "/{$quantity} × (.*) - .*/";

			$title = preg_replace($description_pattern, '$1', (string) $s_line_item->description);

			$line_item_data = array(
				'title'         => $title,
				'description'   => $s_line_item->description,
				'tax_inclusive' => $s_line_item->amount !== $s_line_item->amount_excluding_tax,
				'unit_price'    => $s_line_item->unit_amount_excluding_tax / $currency_multiplier,
				'quantity'      => $quantity,
			);

			if (wu_get_isset($membership_products, $title)) {

				$line_item_data['product'] = wu_get_isset($membership_products, $title);

			} // end if;

			$line_item = new Line_Item($line_item_data);

			$subtotal  = $s_line_item->amount_excluding_tax / $currency_multiplier;
			$tax_total = ($s_line_item->amount - $s_line_item->amount_excluding_tax) / $currency_multiplier;
			$total     = $s_line_item->amount / $currency_multiplier;

			// Set this values after generate the line item to bypass the recalculate_totals
			$line_item->attributes(array(
				'discount_total' => 0,
				'subtotal'       => $subtotal,
				'tax_total'      => $tax_total,
				'total'          => $total,
			));

			$line_items[] = $line_item;

		} // end foreach;

		return $line_items;

	} // end get_ultimo_line_items_from_invoice;

	/**
	 * Saves a payment method to a customer on Stripe.
	 *
	 * @since 2.0.0
	 *
	 * @param  Stripe\Payment_Intent $payment_intent The payment intent.
	 * @param  Stripe\Customer       $s_customer The stripe customer.
	 * @return Stripe\Payment_Method
	 */
	protected function save_payment_method($payment_intent, $s_customer) {

		$payment_method = false;

		try {

			$payment_method = Stripe\PaymentMethod::retrieve($payment_intent->payment_method);

			if (empty($payment_method->customer)) {

				$payment_method->attach(array(
					'customer' => $s_customer->id
				));

			} // end if;

			/*
			 * Update remote payment methods.
			 */
			Stripe\Customer::update($s_customer->id, array(
				'invoice_settings' => array(
					'default_payment_method' => $payment_intent->payment_method,
				),
			));

			/*
			 * De-dupe payment methods.
			 *
			 * If someone re-registers with the same card details they've used in the past, Stripe
			 * will actually create a whole new payment method object with the same fingerprint.
			 * This could result in the same card being added to the customer's payment methods in
			 * Stripe, which is kind of annoying. So we de-dupe them to make sure one customer only
			 * has each payment method listed once. Hopefully Stripe will handle this automatically
			 * in the future.
			 */
			$customer_payment_methods = Stripe\PaymentMethod::all(array(
				'customer' => $s_customer->id,
				'type'     => 'card'
			));

			if (!empty($customer_payment_methods->data)) {

				foreach ($customer_payment_methods->data as $existing_method) {
					/*
					 * Detach if the fingerprint matches but payment method ID is different.
					 */
					if ($existing_method->card->fingerprint === $payment_method->card->fingerprint && $existing_method->id !== $payment_method->id) {

						$existing_method->detach();

					} // end if;

				} // end foreach;

			} // end if;

		} catch (\Exception $e) {

			$error = sprintf('Stripe Gateway: Failed to attach payment method to customer while activating membership #%d. Message: %s', 0, $e->getMessage());

			wu_log_add('stripe', $error, LogLevel::ERROR);

		} // end try;

		return $payment_method;

	} // end save_payment_method;

	/**
	 * Maybe cancel old subscriptions.
	 *
	 * @since 2.0.0
	 *
	 * @param Stripe\Customer $s_customer The stripe customer.
	 * @return void
	 */
	public function maybe_cancel_old_subscriptions($s_customer) {

		$allow_multiple_membership = wu_multiple_memberships_enabled();

		try {

			// Set up array of subscriptions we cancel below so we don't try to cancel the same one twice.
			$cancelled_subscriptions = array();

			// Clean up any past due or unpaid subscriptions. We do this to ensure we don't end up with duplicates.
			$subscriptions = $s_customer->subscriptions->all();

			foreach ( $subscriptions->data as $subscription ) {

				// Cancel subscriptions with the RCP metadata present and matching member ID.
				if ( !empty( $subscription->metadata ) ) {

					$customer_id = (int) $subscription->metadata['customer_id'];

					// Legacy WP Multisite WaaS uses user_id
					$user_id = (int) $subscription->metadata['user_id'];

					if ($customer_id === 0 && $user_id === 0) {

						continue;

					} // end if;

					if ($customer_id !== $this->customer->get_id() && $user_id !== $this->customer->get_user_id()) {

						continue;

					} // end if;

					$membership_id = (int) $subscription->metadata['membership_id'];

					if ($allow_multiple_membership && $membership_id !== $this->membership->get_id()) {

						continue;

					} // end if;

					if ($membership_id === 0 && $customer_id === 0) {
						/**
						 * If we do not have a $membership_id it can be a legacy subscription.
						 * The best way to check this is checking if the plan in Stripe haves
						 * a plan_id on metadata (value used on legacy)
						 */

						$stop_here = true;

						// Check if it is not a ultimo subscription
						foreach ($subscription->items->data as $item) {

							if ( !empty( $item->plan ) && !empty($item->plan->metadata) && isset($item->plan->metadata['plan_id'])) {

								if ( wu_get_product_by('migrated_from_id', $item->plan->metadata['plan_id']) ) {

									$stop_here = false;

									break;

								} // end if;

							} // end if;

						} // end foreach;

						if ($stop_here) {

							continue;

						} // end if;

					} // end if;

					// Check if membership exist and is from this customer before delete subscription
					if ($membership_id !== 0 && $membership_id !== $this->membership->get_id()) {

						$membership_from_s = wu_get_membership($membership_id);

						if (!$membership_from_s || $membership_from_s->get_customer_id() !== $customer_id) {

							continue;

						} // end if;

					} // end if;

					$subscription->cancel();

					$cancelled_subscriptions[] = $subscription->id;

					wu_log_add('stripe', sprintf('Stripe Gateway: Cancelled Stripe subscription %s.', $subscription->id));

					continue;

				} // end if;

			} // end foreach;

		} catch ( \Exception $e ) {

			wu_log_add('stripe', sprintf( 'Stripe Gateway: Subscription cleanup failed for customer #%d. Message: %s', $this->customer->get_id(), $e->getMessage()), LogLevel::ERROR);

		} // end try;

	} // end maybe_cancel_old_subscriptions;

	/**
	 * Process a refund.
	 *
	 * It takes the data concerning
	 * a refund and process it.
	 *
	 * @since 2.0.0
	 *
	 * @param float                        $amount The amount to refund.
	 * @param \WP_Ultimo\Models\Payment    $payment The payment associated with the checkout.
	 * @param \WP_Ultimo\Models\Membership $membership The membership.
	 * @param \WP_Ultimo\Models\Customer   $customer The customer checking out.
	 * @return void|bool
	 */
	public function process_refund($amount, $payment, $membership, $customer): bool {

		$gateway_payment_id = $payment->get_gateway_payment_id();

		if (empty($gateway_payment_id)) {

			throw new \Exception(__('Gateway payment ID not found. Cannot process refund automatically.', 'wp-ultimo'));

		} // end if;

		/**
		 * Ensure the correct api keys are set
		 */
		$this->setup_api_keys();

		/*
		 * Check if we have an invoice,
		 * or a charge at hand.
		 */
		if (strncmp((string) $gateway_payment_id, 'ch_', strlen('ch_')) === 0) {

			$charge_id = $gateway_payment_id;

		} elseif (strncmp((string) $gateway_payment_id, 'in_', strlen('in_')) === 0) {

			$invoice = Stripe\Invoice::retrieve($gateway_payment_id);

			$gateway_payment_id = $invoice->charge;

		} else {

			throw new Exception(__('Gateway payment ID not valid.', 'wp-ultimo'));

		} // end if;

		/*
		 * We need to normalize the value
		 * for Stripe, which usually works
		 * in cents.
		 */
		$normalize_amount = $amount * wu_stripe_get_currency_multiplier();

		Stripe\Refund::create(array(
			'charge' => $charge_id,
			'amount' => $normalize_amount,
		));

		/*
		 * You might be asking why we are not
		 * calling $payment->refund($amount) to
		 * update the payment status.
		 *
		 * We will do that once Stripe tells us
		 * that the refund was successful.
		 */
		return true;

	} // end process_refund;

	/**
	 * Process a cancellation.
	 *
	 * It takes the data concerning
	 * a membership cancellation and process it.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Ultimo\Models\Membership $membership The membership.
	 * @param \WP_Ultimo\Models\Customer   $customer The customer checking out.
	 * @return void|bool
	 */
	public function process_cancellation($membership, $customer) {

		$subscription_id = $membership->get_gateway_subscription_id();

		if (!empty($subscription_id)) {
			/**
			 * Ensure the correct api keys are set
			 */
			$this->setup_api_keys();

			try {

				$subscription = Stripe\Subscription::retrieve($subscription_id);

				if ($subscription->status !== 'canceled') {

					$subscription->cancel();

				} // end if;

			} catch (\Exception $e) {

				wu_log_add('stripe', sprintf('Stripe Gateway: Failed to cancel subscription %s. Message: %s', $subscription_id, $e->getMessage()), LogLevel::ERROR);

				return false;

			} // end try;

		} // end if;

	} // end process_cancellation;

	/**
	 * Attempt to guess the maximum `billing_cycle_anchor` Stripe will allow us to set, given a signup date
	 * and billing cycle interval.
	 *
	 * @param int    $interval      Billing cycle interval.
	 * @param string $interval_unit Billing cycle interval unit.
	 * @param string $signup_date   Signup date that can be parsed by `strtotime()`. Will almost always be
	 *                              `now`, but can be overridden for help in unit tests.
	 *
	 * @since 2.0.0
	 * @return DateTime
	 */
	public function get_stripe_max_billing_cycle_anchor($interval, $interval_unit, $signup_date = 'now') {

		try {

			$signup_date = new \DateTimeImmutable($signup_date);

		} catch (Exception $exception) {

			$signup_date = new \DateTimeImmutable();

		} // end try;

		$stripe_max_anchor = $signup_date->modify(sprintf('+%d %s', $interval, $interval_unit));

		$proposed_next_bill_date = new \DateTime();

		$proposed_next_bill_date->setTimestamp($signup_date->getTimestamp());

		// Set to first day of the month so we're not dealing with mismatching days of the month.
		$proposed_next_bill_date->setDate($proposed_next_bill_date->format('Y'), $proposed_next_bill_date->format('m'), 1);

		// Now we can safely add 1 interval and still be in the expected month.
		$proposed_next_bill_date->modify(sprintf('+ %d %s', $interval, $interval_unit));

		/*
		 * If the day of the month in the signup date exceeds the total number of days in the proposed month,
		 * set the anchor to the last day of the proposed month - whatever that is.
		 */
		if (date('j', $signup_date->getTimestamp()) > date('t', $proposed_next_bill_date->getTimestamp())) { // phpcs:ignore

			try {

				$stripe_max_anchor = new \DateTime(date('Y-m-t H:i:s', $proposed_next_bill_date->getTimestamp())); // phpcs:ignore

			} catch (\Exception $exception) {

				// Silence is golden

			} // end try;

		} // end if;

		return $stripe_max_anchor;

	} // end get_stripe_max_billing_cycle_anchor;

	/**
	 * Get Stripe error from exception
	 *
	 * This converts the exception into a WP_Error object with a localized error message.
	 *
	 * @param Error\Base $e The stripe error object.
	 *
	 * @since 2.0.0
	 * @return \WP_Error
	 */
	protected function get_stripe_error($e) {

		$wp_error = new \WP_Error();

		if (method_exists($e, 'getJsonBody')) {

			$body  = $e->getJsonBody();
			$error = $body['error'];

			$wp_error->add($error['code'], $this->get_localized_error_message($error['code'], $e->getMessage()));

		} else {

			$wp_error->add('unknown_error', __('An unknown error has occurred.', 'wp-ultimo'));

		} // end if;

		return $wp_error;

	} // end get_stripe_error;

	/**
	 * Localize common Stripe error messages so they're available for translation.
	 *
	 * @link https://stripe.com/docs/error-codes
	 *
	 * @param string $error_code    Stripe error code.
	 * @param string $error_message Original Stripe error message. This will be returned if we don't have a localized version of
	 *                              the error code.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function get_localized_error_message($error_code, $error_message = '') {

		$errors = wu_stripe_get_localized_error_messages();

		if (!empty($errors[$error_code])) {

			return $errors[$error_code];

		} else {

			// translators: 1 is the error code and 2 the message.
			return sprintf(__('An error has occurred (code: %1$s; message: %2$s).', 'wp-ultimo'), $error_code, $error_message);

		} // end if;

	} // end get_localized_error_message;

	/**
	 * Gives gateways a chance to run things before backwards compatible webhooks are run.
	 *
	 * @since 2.0.8
	 * @return void
	 */
	public function before_backwards_compatible_webhook() {

		if (empty($this->secret_key)) {

			$other_id = $this->get_id() === 'stripe' ? 'stripe-checkout' : 'stripe';

			/*
			 * If we don't have stripe anymore, and only stripe checkout,
			 * We might want to use the keys from stripe checkout here
			 * or vice-versa.
			 */
			$this->setup_api_keys($other_id);

		} // end if;

	} // end before_backwards_compatible_webhook;

	/**
	 * Process webhooks
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function process_webhooks() {

		wu_log_add('stripe', 'Receiving Stripe webhook...');

		/*
		 * PHP Input as object
		 */
		$received_event = wu_get_input();

		// for extra security, retrieve from the Stripe API
		if (!isset($received_event->id)) {

			throw new \Exception(__('Event ID not found.', 'wp-ultimo'));

		} // end if;

		// Set the right mode for this request
		if (isset($received_event->livemode) && !$received_event->livemode !== $this->test_mode) {

			$this->test_mode = !$received_event->livemode;

		} // end if;

		/**
		 * Ensure the correct api keys are set
		 */
		$this->setup_api_keys();

		$event_id = $received_event->id;

		$event         = Stripe\Event::retrieve($event_id);
		$payment_event = $event->data->object;

		$membership   = false;
		$payment      = false;
		$customer     = false;
		$invoice      = false;
		$subscription = false;

		/*
		 * Check if we have a customer present.
		 */
		if (empty($payment_event->customer)) {

			return;

		} // end if;

		/*
		 * Try to get an invoice object from the payment event.
		 */
		if (!empty($payment_event->object) && 'invoice' === $payment_event->object) {

			$invoice = $payment_event;

		} elseif (!empty($payment_event->invoice)) {

			$invoice = Stripe\Invoice::retrieve($payment_event->invoice);

		} // end if;

		/*
		 * Now try to get a subscription from the invoice object.
		 */
		if (!empty($invoice->subscription)) {

			$subscription = Stripe\Subscription::retrieve($invoice->subscription);

		} // end if;

		/*
		 * We can also get the subscription by the
		 * object ID in some circumstances.
		 */
		if (empty($subscription) && strpos((string) $payment_event->id, 'sub_') !== false) {

			$subscription = Stripe\Subscription::retrieve($payment_event->id);

		} // end if;

		/*
		 * Retrieve the membership by subscription ID.
		 */
		if (!empty($subscription)) {

			$membership = wu_get_membership_by('gateway_subscription_id', $subscription->id);

		} // end if;

		// Retrieve the membership by payment meta (one-time charges only).
		if (!empty($payment_event->metadata->membership_id)) {

			$membership = wu_get_membership($payment_event->metadata->membership_id);

		} // end if;

		/**
		 * Last ditch effort to retrieve a valid membership.
		 */
		if (empty($membership) && !empty($invoice)) {

			$amount = $invoice->amount_paid / wu_stripe_get_currency_multiplier();

			$membership = wu_get_membership_by_customer_gateway_id($payment_event->customer, array('stripe', 'stripe-checkout'), $amount);

		} // end if;

		/**
		 * Filters the membership record associated with this webhook.
		 *
		 * This filter was introduced due to conflicts that may arise when the same Stripe customer may
		 * be used on different sites.
		 *
		 * @param \WP_Ultimo\Models\Membership|false $membership The membership object.
		 * @param Stripe\Event                       $event The event received.
		 */
		$membership = apply_filters('wu_stripe_webhook_membership', $membership, $event);

		$this->membership = $membership;

		if (!is_a($membership, '\WP_Ultimo\Models\Membership')) {
			/**
			 * If we don't have a membership, we can't do anything
			 * and this is not an error.
			 */
			return;

		} // end if;

		/**
		 *  Ensure the membership is using the current gateway
		 */
		if ($this->get_id() !== $membership->get_gateway()) {

			// translators: %s is the customer ID.
			throw new Ignorable_Exception(sprintf(__('Exiting Stripe webhook - This call must be handled by %s webhook', 'wp-ultimo'), $membership->get_gateway()));

		} // end if;

		/*
		 * Set the WP Multisite WaaS customer.
		 */
		$customer = $membership->get_customer();

		$this->customer = $customer;

		/*
		 * Now, we actually get to handle
		 * webhook messages.
		 *
		 * We'll handle 4 cases:
		 *
		 * 1. Customer subscription created - For Stripe Gateway;
		 * 2. Charge Succeeded & Invoice Payment Succeed;
		 * 3. Payment failed;
		 * 4. Subscription deleted.
		 *
		 * First, we'll start customer subscription created.
		 */
		if ($event->type === 'customer.subscription.created') {

			do_action('wu_webhook_recurring_payment_profile_created', $membership, $this);

		} // end if;

		/*
		 * Deal with Stripe Checkouts case.
		 *
		 * On Stripe Checkout, we rely entirely on
		 * the webhook call to change the status of things.
		 */
		if ($event->type === 'checkout.session.completed') {

			$membership->set_gateway_customer_id($payment_event->customer);

			$membership->set_gateway_subscription_id($payment_event->subscription);

			$membership->set_gateway($this->get_id());

			$membership->save();

			return true;

		} // end if;

		/*
		 * Next, let's deal with charges that went through!
		 */
		if ($event->type === 'charge.succeeded' || $event->type === 'invoice.payment_succeeded') {
			/**
			 * Here we need to handle invoice.payment_succeeded
			 * events due subscriptions with trials and we need
			 * to handle charge.succeeded for payments without
			 * stripe invoices.
			 */

			$payment_data = array(
				'status'  => Payment_Status::COMPLETED,
				'gateway' => $this->get_id(),
			);

			if ($event->type === 'charge.succeeded') {
				/*
				 * Successful one-time payment
				 */
				if (empty($payment_event->invoice)) {

					$payment_data['total']              = $payment_event->amount / wu_stripe_get_currency_multiplier();
					$payment_data['gateway_payment_id'] = $payment_event->id;

				/*
				 * Subscription payment received.
				 */
				} else {

					$payment_data['total']              = $invoice->total / wu_stripe_get_currency_multiplier();
					$payment_data['subtotal']           = ($invoice->total_excluding_tax / wu_stripe_get_currency_multiplier()) - $payment_data['discount_total'];
					$payment_data['tax_total']          = $invoice->tax / wu_stripe_get_currency_multiplier();
					$payment_data['gateway_payment_id'] = $payment_event->id;

					if (!empty($payment_event->discount)) {

						$payment_data['discount_code'] = $payment_event->discount->coupon_id;

					} // end if;

				} // end if;

			} // end if;

			/*
			 * Let's check if we have the payment
			 * created already. We only want to create a new
			 * one if we don't have one already.
			 */
			$gateway_payment_id = $payment_event->id;

			$payment = wu_get_payment_by('gateway_payment_id', $gateway_payment_id);

			$expiration = false;

			/*
			 * Payment does not exist.
			 */
			if (!empty($gateway_payment_id) && !$payment) {
				/*
				 * Checks if we have the data about a subscription.
				 */
				if (!empty($subscription)) {

					$membership->set_recurring(true);

					$membership->set_gateway_subscription_id($subscription->id);

					/*
					 * Set the new expiration date.
					 * We use the `current_period_end` as our base and force the time to be 23:59:59 that day.
					 * However, this must be at least two hours after `current_period_end` to ensure there's
					 * plenty of time between the next invoice being generated and actually being paid/finalized.
					 * Stripe usually does this within 1 hour, but we're using 2 to be on the safe side and
					 * account for delays.
					*/
					$renewal_date = new \DateTime();
					$renewal_date->setTimestamp($subscription->current_period_end);
					$renewal_date->setTime(23, 59, 59);

					/*
					 * Estimated charge date is 2 hours
					 * after `current_period_end`.
					 */
					$stripe_estimated_charge_timestamp = $subscription->current_period_end + (2 * HOUR_IN_SECONDS);

					if ($stripe_estimated_charge_timestamp > $renewal_date->getTimestamp()) {

						$renewal_date->setTimestamp($stripe_estimated_charge_timestamp);

					} // end if;

					/*
					 * Set the expiration.
					 */
					$expiration = $renewal_date->format('Y-m-d H:i:s');

				} // end if;

				/*
				 * Checks for a pending payment on the membership.
				 */
				$pending_payment = $membership->get_last_pending_payment();

				if (!empty($pending_payment)) {
					/*
					 * Completing a pending payment.
					 */
					$pending_payment->attributes($payment_data);

					$payment = $pending_payment;

				} elseif ($event->type === 'charge.succeeded') {
					/**
					 * These must be retrieved after the status
					 * is set to active in order for upgrades to work properly
					 */

					// We need to get the line items from the invoice.
					$line_items = $this->get_ultimo_line_items_from_invoice($invoice->lines->data);

					// If we have a tax_total let's add it to line items.
					if (!empty($payment_data['tax_total'])) {

						foreach ($line_items as &$line_item) {

							$current_total       = $line_item->get_total();
							$percent_of_subtotal = $current_total / $payment_data['subtotal'];

							$item_tax_total = $payment_data['tax_total'] * $percent_of_subtotal;
							$item_total     = $current_total + $item_tax_total;
							$item_tax_rate  = round(($item_tax_total / $current_total) * 100, 2);

							$line_item->set_tax_total($item_tax_total);
							$line_item->set_tax_rate($item_tax_rate);
							$line_item->set_total($item_total);

						} // end foreach;

					} // end if;

					/*
					 * As we add the discount code value into plan price we need
					 * to add the discount code from membership if it exist.
					 */
					$discount_code = $membership->get_discount_code();

					if ($discount_code && $discount_code->should_apply_to_renewals()) {

						$type = $discount_code->get_type();

						$old_subtotal = $payment_data['subtotal'];

						if ($type === 'percentage') {

							$payment_data['subtotal'] = $old_subtotal / (1 - ($discount_code->get_value() / 100));

							$discount_total = $payment_data['subtotal'] - $old_subtotal;

						} elseif ($type === 'absolute') {

							$discount_total = $discount_code->get_value();

							$payment_data['subtotal'] = $payment_data['subtotal'] - $discount_total;

						} // end if;

						// Now we apply this discount to the line items.
						foreach ($line_items as &$line_item) {

							$current_item_subtotal = $line_item->get_subtotal();
							$percent_of_subtotal   = $current_item_subtotal / $old_subtotal;

							$line_item->set_discount_total($discount_total * $percent_of_subtotal);
							$line_item->set_subtotal($line_item->get_discount_total() + $current_item_subtotal);

						} // end foreach;

					} // end if;

					$payment_data['transaction_type'] = 'renewal';
					$payment_data['customer_id']      = $customer->get_id();
					$payment_data['membership_id']    = $membership->get_id();
					$payment_data['line_items']       = $line_items;
					$payment                          = wu_create_payment($payment_data);

				} else {
					/**
					 *  We do not have a payment to change
					 */
					return true;

				} // end if;

				$this->payment = $payment;

				if ($payment_event->object === 'charge' && !$subscription && $this->get_id() === 'stripe') {

					$cart = $payment->get_meta('wu_original_cart');

					$payment_intent_id = (string) $payment->get_meta('stripe_payment_intent_id');

					// We handle setup intents from process_checkout.
					$is_setup_intent = strncmp($payment_intent_id, 'seti_', strlen('seti_')) === 0;

					if ($cart && $cart->should_auto_renew() && $cart->has_recurring() && !$is_setup_intent) {

						$s_customer     = Stripe\Customer::retrieve($payment_event->customer);
						$payment_method = Stripe\PaymentMethod::retrieve($payment_event->payment_method);

						$subscription = $this->create_recurring_payment($membership, $cart, $payment_method, $s_customer);

						// If we receive a subscription, we need to update the membership.
						if ($subscription) {

							$membership->set_gateway_subscription_id($subscription->id);

							$renewal_date = new \DateTime();
							$renewal_date->setTimestamp($subscription->current_period_end);
							$renewal_date->setTime(23, 59, 59);

							$stripe_estimated_charge_timestamp = $subscription->current_period_end + (2 * HOUR_IN_SECONDS);

							if ($stripe_estimated_charge_timestamp > $renewal_date->getTimestamp()) {

								$renewal_date->setTimestamp($stripe_estimated_charge_timestamp);

							} // end if;

							$expiration = $renewal_date->format('Y-m-d H:i:s');

						} else {

							return true;

						} // end if;

					} // end if;

				} // end if;

				/**
				 * Renewals the membership
				 */
				$membership->add_to_times_billed(1);
				$membership->renew($membership->is_recurring(), 'active', $expiration);

				/**
				 * We need to save here to ensure that we are not saving more than once.
				 *
				 * @see process_checkout method
				 */
				$payment->save();

				/*
				 * Tell the gateway to do their stuff.
				 */
				$this->trigger_payment_processed($payment, $membership);

				return true;

			} elseif (!empty($gateway_payment_id) && $payment) {
				/*
				 * The payment already exists.
				 *
				 * Throws to inform that
				 * we have a duplicate payment.
				 */
				throw new Ignorable_Exception(__('Duplicate payment.', 'wp-ultimo'));

			} // end if;

		} // end if;

		/*
		 * Next, let's deal with charges that went through!
		 */
		if ($event->type === 'charge.refunded') {

			$payment_data = array(
				'gateway' => 'stripe',
			);

			$payment_id = $payment_event->metadata->payment_id;

			$payment = wu_get_payment($payment_id);

			if (empty($payment)) {

				throw new Ignorable_Exception(__('Payment not found on refund webhook call.', 'wp-ultimo'));

			} // end if;

			$is_refundable = in_array($payment->get_status(), wu_get_refundable_payment_types(), true);

			if (!$is_refundable) {

				throw new Ignorable_Exception(__('Payment is not refundable.', 'wp-ultimo'));

			} // end if;

			/*
			 * Let's address the type.
			 */
			$amount = $payment_event->amount_refunded / wu_stripe_get_currency_multiplier();

			/*
			 * Actually process the refund
			 * using the helper method.
			 */
			$status = $payment->refund($amount);

			return $status;

		} // end if;

		/*
		 * Failed payments.
		 */
		if ($event->type === 'invoice.payment_failed') {

			$this->webhook_event_id = $event->id;

			// Make sure this invoice is tied to a subscription and is the user's current subscription.
			if (!empty($event->data->object->subscription) && $event->data->object->subscription === $membership->get_gateway_subscription_id()) {

				do_action('wu_recurring_payment_failed', $membership, $this);

			} // end if;

			do_action('wu_stripe_charge_failed', $payment_event, $event, $membership);

			return true;

		} // end if;

		/*
		 * Cancelled / failed subscription.
		 */
		if ($event->type === 'customer.subscription.deleted') {

			wu_log_add('stripe', 'Processing Stripe customer.subscription.deleted webhook.');

			if ($payment_event->id === $membership->get_gateway_subscription_id()) {
				/*
				 * If this is a completed payment plan,
				 * we can skip any cancellation actions.
				 */
				if (!$membership->is_forever_recurring() && $membership->at_maximum_renewals()) {

					return;

				} // end if;

				if ($membership->is_active()) {

					$membership->cancel();

					$membership->add_note(__('Membership cancelled via Stripe webhook.', 'wp-ultimo'));

				} else {

					wu_log_add('stripe', sprintf('Membership #%d is not active - not cancelling account.', $membership->get_id()));

				} // end if;

				return true;

			} else {

				wu_log_add('stripe', sprintf('Payment event ID (%s) doesn\'t match membership\'s merchant subscription ID (%s).', $payment_event->id, $membership->get_gateway_subscription_id()), true);

			} // end if;

		} // end if;

	} // end process_webhooks;

	/**
	 * Get saved card options for this customers.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_saved_card_options() {

		if (!is_user_logged_in()) {

			return array();

		} // end if;

		$options = array();

		$user_id = isset($this->customer) && $this->customer ? $this->customer->get_user_id() : false;

		$saved_payment_methods = $this->get_user_saved_payment_methods($user_id);

		foreach ($saved_payment_methods as $saved_payment_method) {

			$options[$saved_payment_method->id] = sprintf(
				// translators: 1 is the card brand (e.g. VISA), and 2 is the last 4 digits.
				__('%1$s ending in %2$s', 'wp-ultimo'),
				strtoupper((string) $saved_payment_method->card->brand),
				$saved_payment_method->card->last4
			);

		} // end foreach;

		return $options;

	} // end get_saved_card_options;
	/**
	 * Add credit card fields.
	 *
	 * @since 2.0.0
	 */
	public function fields(): string {

		return '';

	} // end fields;

	/**
	 * Load fields for the Update Billing Card form.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function update_card_fields() { // phpcs:disable ?>

		<div class="wu-gateway-new-card-fields">

			<fieldset id="wu-card-name-wrapper" class="wu_card_fieldset">
				<p id="wu_card_name_wrap">
					<label for="wu-update-card-name"><?php _e('Name on Card', 'wp-ultimo'); ?></label>
					<input type="text" size="20" id="wu-update-card-name" name="wu_card_name" class="wu_card_name card-name" />
				</p>
			</fieldset>

			<fieldset id="wu-card-wrapper" class="wu_card_fieldset">
				<div id="wu_card_wrap">
					<div id="wu-card-element"></div>
				</div>
			</fieldset>

		</div>

		<div id="wu-card-element-errors"></div>

		<?php // phpcs:enable

	} // end update_card_fields;

	/**
	 * Register stripe scripts.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_scripts() {

		$active_gateways = (array) wu_get_setting('active_gateways', array());

		if (empty($this->publishable_key) || !in_array($this->get_id(), $active_gateways, true)) {

			return;

		} // end if;

		wp_register_script('wu-stripe-sdk', 'https://js.stripe.com/v3/', false, 'v3');

		wp_register_script("wu-{$this->get_id()}", wu_get_asset("gateways/{$this->get_id()}.js", 'js'), array('wu-checkout', 'wu-stripe-sdk'), wu_get_version(), true);

		$saved_cards = $this->get_saved_card_options();

		$obj_name = 'wu_' . str_replace('-', '_', (string) $this->get_id());

		wp_localize_script("wu-{$this->get_id()}", $obj_name, array(
			'pk_key'                  => $this->publishable_key,
			'request_billing_address' => $this->request_billing_address,
			'add_new_card'            => empty($saved_cards),
			'payment_method'          => empty($saved_cards) ? 'add-new' : current(array_keys($saved_cards)),
		));

		wp_enqueue_script("wu-{$this->get_id()}");

	} // end register_scripts;

	/**
	 * Maybe create a new tax rate on Stripe
	 *
	 * @since 2.0.0
	 *
	 * @param array $args The tax rate arguments.
	 * @return string
	 */
	public function maybe_create_tax_rate($args) {

		$slug = strtolower(sprintf('%s-%s-%s', $args['country'], $args['tax_rate'], $args['type']));

		if (wu_get_isset($args, 'inclusive')) {

			$slug .= '-inclusive';

		} // end if;

		static $cache = array();

		if (wu_get_isset($cache, $slug)) {

			return wu_get_isset($cache, $slug);

		} // end if;

		$stripe_tax_rates = Stripe\TaxRate::all();

		foreach ($stripe_tax_rates as $stripe_tax_rate) {

			if (isset($stripe_tax_rate->metadata->tax_rate_id) && $stripe_tax_rate->metadata->tax_rate_id === $slug) {

				$cache[$slug] = $stripe_tax_rate->id;

				return $stripe_tax_rate->id;

			} // end if;

		} // end foreach;

		$args = array(
			'display_name' => $args['title'],
			'description'  => $args['title'],
			'jurisdiction' => $args['country'],
			'percentage'   => absint($args['tax_rate']),
			'inclusive'    => wu_get_isset($args, 'inclusive'),
			'metadata'     => array(
				'tax_rate_id' => $slug,
			),
		);

		try {

			$tax_rate = Stripe\TaxRate::create($args);

			return $tax_rate->id;

		} catch (Exception $exception) {

			// Silence is golden.
			return '';

		} // end try;

	} // end maybe_create_tax_rate;

	/**
	 * Checks to see if a plan exists with the provided arguments, and if so, returns the ID of
	 * that plan. If not, a new plan is created.
	 *
	 * This method differs from create_plan() and plan_exists() because it doesn't expect
	 * a membership level ID number. This allows for the creation of plans that may not be
	 * exactly based on a membership level's parameters.
	 *
	 * @param array $args           {
	 *                              Array of arguments.
	 *
	 * @type string $name           Required. Name of the plan.
	 * @type float  $price          Required. Price each interval.
	 * @type string $interval       Optional. Billing interval (i.e ."day", "month", "year"). Default is "month".
	 * @type int    $interval_count Optional. Interval count. Default is "1".
	 * @type string $currency       Optional. Currency. Defaults to site currency.
	 * @type string $id             Optional. Plan ID. Automatically generated based on arguments.
	 *                    }
	 *
	 * @since 2.0.0
	 * @return string|\WP_Error Plan ID on success or WP_Error on failure.
	 */
	public function maybe_create_plan($args) {

		$args = wp_parse_args($args, array(
			'name'           => '',
			'price'          => 0.00,
			'interval'       => 'month',
			'interval_count' => 1,
			'currency'       => strtolower((string) wu_get_setting('currency_symbol', 'USD')),
			'id'             => '',
		));

		// Name and price are required.
		if (empty($args['name']) || empty($args['price'])) {

			return new \WP_Error('missing_name_price', __('Missing plan name or price.', 'wp-ultimo'));

		} // end if;

		/*
		 * Create a new object that looks like a membership level object.
		 * We do this because generate_plan_id() expects a membership level object but we
		 * don't actually have one.
		 */
		if (empty($args['id'])) {

			$plan_level                = new \stdClass();
			$plan_level->name          = $args['name'];
			$plan_level->price         = $args['price'];
			$plan_level->duration      = $args['interval_count'];
			$plan_level->duration_unit = $args['interval'];
			$plan_level->currency      = $args['currency'];
			$plan_id                   = $this->generate_plan_id($plan_level);

		} else {

			$plan_id = $args['id'];

		} // end if;

		if (empty($plan_id)) {

			return new \WP_Error('empty_plan_id', __('Empty plan ID.', 'wp-ultimo'));

		} // end if;

		// Convert price to Stripe format.
		$price = round($args['price'] * wu_stripe_get_currency_multiplier(), 0);

		// First check to see if a plan exists with this ID. If so, return that.
		try {

			$membership_level = isset($plan_level) ? $plan_level : new \stdClass();

			/**
			 * Filters the ID of the plan to check for. If this exists, the new subscription will
			 * use this plan.
			 *
			 * @param string $plan_id          ID of the Stripe plan to check for.
			 * @param object $membership_level Membership level object.
			 */
			$existing_plan_id = apply_filters('wu_stripe_existing_plan_id', $plan_id, $membership_level);

			$plan = Stripe\Plan::retrieve($existing_plan_id);

			return $plan->id;

		} catch (\Exception $e) {

			// silence is golden

		} // end try;

		// Otherwise, create a new plan.
		try {

			$product = Stripe\Product::create(array(
				'name' => $args['name'] . ' - ' . $args['currency'],
				'type' => 'service'
			) );

			$plan = Stripe\Plan::create(array(
				'amount'         => $price,
				'interval'       => $args['interval'],
				'interval_count' => $args['interval_count'],
				'currency'       => $args['currency'],
				'id'             => $plan_id,
				'product'        => $product->id
			) );

			// plan successfully created
			return $plan->id;

		} catch (\Exception $e) {

			$error_code = $e->getCode();

			// WP Error did not handle empty error code
			if (empty($error_code)) {

				if (method_exists($e, 'getHttpStatus')) {

					$error_code = $e->getHttpStatus();

				} else {

					$error_code = 500;

				} // end if;

			} // end if;

			wu_log_add('stripe', sprintf('Error creating Stripe plan. Code: %s; Message: %s', $error_code, $e->getMessage()), LogLevel::ERROR);

			return new \WP_Error('stripe_exception', sprintf('Error creating Stripe plan. Code: %s; Message: %s', $error_code, $e->getMessage()));

		} // end try;

	} // end maybe_create_plan;

	/**
	 * Checks to see if a product exists with the provided arguments, and if so, returns the ID of
	 * that product. If not, a new product is created.
	 *
	 * @since 2.1.1
	 *
	 * @param string $name Product name.
	 * @param string $id   Optional. Product ID. Automatically generated based on arguments.
	 * @return string|\WP_Error Product ID on success or WP_Error on failure.
	 */
	private function maybe_create_product($name, $id = '') {

		// Name are required.
		if (empty($name)) {

			return new \WP_Error('missing_name', __('Missing product name.', 'wp-ultimo'));

		} // end if;

		if (empty($id)) {

			$product_id = strtolower(str_replace(' ', '', sanitize_title_with_dashes($name)));
			$product_id = sprintf('wu-%s', $product_id);
			$product_id = preg_replace('/[^a-z0-9_\-]/', '-', $product_id);

		} else {

			$product_id = $id;

		} // end if;

		if (empty($product_id)) {

			return new \WP_Error('empty_product_id', __('Empty product ID.', 'wp-ultimo'));

		} // end if;

		// First check to see if a product exists with this ID. If so, return that.
		try {
			/**
			 * Filters the ID of the product to check for. If this exists, the new subscription will
			 * use this product.
			 *
			 * @param string $product_id ID of the Stripe product to check for.
			 * @param object $name       Product name.
			 */
			$existing_product_id = apply_filters('wu_stripe_existing_product_id', $product_id, $name);

			$product = Stripe\Product::retrieve($existing_product_id);

			return $product->id;

		} catch (\Exception $e) {

			// silence is golden

		} // end try;

		// Otherwise, create a new product.
		try {

			$product = Stripe\Product::create(array(
				'id'   => $product_id,
				'name' => $name,
			));

			// product successfully created
			return $product->id;

		} catch (\Exception $e) {

			$error_code = $e->getCode();

			// WP Error did not handle empty error code
			if (empty($error_code)) {

				if (method_exists($e, 'getHttpStatus')) {

					$error_code = $e->getHttpStatus();

				} else {

					$error_code = 500;

				} // end if;

			} // end if;

			wu_log_add('stripe', sprintf('Error creating Stripe product. Code: %s; Message: %s', $error_code, $e->getMessage()), LogLevel::ERROR);

			return new \WP_Error('stripe_exception', sprintf('Error creating Stripe product. Code: %s; Message: %s', $error_code, $e->getMessage()));

		} // end try;

	} // end maybe_create_product;

	/**
	 * Checks to see if a price exists with the provided arguments,
	 * and if so, returns the ID of that price. If not, a new price is created.
	 *
	 * @since 2.1.3
	 * @param string $title         Price title.
	 * @param float  $amount        Amount.
	 * @param string $currency      Currency.
	 * @param int    $quantity      Quantity.
	 * @param int    $duration      Duration.
	 * @param string $duration_unit Duration unit.
	 * @param string $tax_behavior  Tax behavior.
	 * @return string|\WP_Error Price ID on success or WP_Error on failure.
	 */
	function maybe_create_price($title, $amount, $currency, $quantity = 1, $duration = false, $duration_unit = false, $tax_behavior = '') {

		$name = $quantity === 1 ? $title : "x$quantity $title";

		$currency = strtolower($currency);
		$s_amount = round($amount * wu_stripe_get_currency_multiplier());

		$s_product = $this->maybe_create_product($name);

		$s_price_data = array(
			'lookup_key'  => "$s_product-$s_amount-$currency",
			'unit_amount' => $s_amount,
			'currency'    => $currency,
			'product'     => $s_product,
		);

		if ($duration && $duration_unit) {

			$s_price_data['recurring'] = array(
				'interval'       => $duration_unit,
				'interval_count' => $duration,
			);

			$s_price_data['lookup_key'] .= "-$duration-$duration_unit";

		} // end if;

		if (!empty($tax_behavior)) {

			$s_price_data['tax_behavior'] = $tax_behavior;
			$s_price_data['lookup_key']  .= "-$tax_behavior";

		} // end if;

		// check if price already exists
		$existing = Stripe\Price::all(array(
			'lookup_keys' => array($s_price_data['lookup_key']),
			'limit'       => 1,
		));

		if (!empty($existing->data)) {

			return $existing->data[0]->id;

		} // end if;

		$s_price = Stripe\Price::create($s_price_data);

		return $s_price->id;

	} // end maybe_create_price;

	/**
	 * Generate a Stripe plan ID string based on a membership level
	 *
	 * The plan name is set to {levelname}-{price}-{duration}{duration unit}
	 * Strip out invalid characters such as '@', '.', and '()'.
	 * Similar to WP core's sanitize_html_class() & sanitize_key() functions.
	 *
	 * @param object $product_info The product info object.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	private function generate_plan_id($product_info) {

		$product_name = strtolower(str_replace(' ', '', sanitize_title_with_dashes($product_info->name)));

		$plan_id = sprintf('%s-%s-%s-%s', $product_name, $product_info->price, $product_info->currency, $product_info->duration . $product_info->duration_unit);

		$plan_id = preg_replace('/[^a-z0-9_\-]/', '-', $plan_id);

		return $plan_id;

	} // end generate_plan_id;

	/**
	 * Get the saved Stripe payment methods for a given user ID.
	 *
	 * @since 2.0.0
	 *
	 * @throws \Exception, When info is wrong.
	 * @throws \Exception When info is wrong 2.
	 * @return PaymentMethod[]|array
	 */
	public function get_user_saved_payment_methods() {

		$customer = wu_get_current_customer();

		if (!$customer) {

			return array();

		} // end if;

		$customer_id = $customer->get_id();

		/**
		 * Ensure the correct api keys are set
		 */
		$this->setup_api_keys();

		try {
			/*
			 * Declare static to prevent multiple calls.
			 */
			static $existing_payment_methods;

			if (!is_null($existing_payment_methods) && array_key_exists($customer_id, $existing_payment_methods)) {

				return $existing_payment_methods[$customer_id];

			} // end if;

			$customer_payment_methods = array();

			$stripe_customer_id = \WP_Ultimo\Models\Membership::query(array(
				'customer_id' => $customer_id,
				'search'      => 'cus_*',
				'fields'      => array('gateway_customer_id'),
			));

			$stripe_customer_id = current(array_column($stripe_customer_id, 'gateway_customer_id'));

			$payment_methods = Stripe\PaymentMethod::all(array(
				'customer' => $stripe_customer_id,
				'type'     => 'card'
			));

			foreach ($payment_methods->data as $payment_method) {

				$customer_payment_methods[$payment_method->id] = $payment_method;

			} // end foreach;

			$existing_payment_methods[$customer_id] = $customer_payment_methods;

			return $existing_payment_methods[$customer_id];

		} catch (\Throwable $exception) {

			return array();

		} // end try;

	} // end get_user_saved_payment_methods;

	/**
	 * Returns the external link to view the payment on the payment gateway.
	 *
	 * Return an empty string to hide the link element.
	 *
	 * @since 2.0.0
	 *
	 * @param string $gateway_payment_id The gateway payment id.
	 * @return string.
	 */
	public function get_payment_url_on_gateway($gateway_payment_id): string {

		$route = $this->test_mode ? '/test' : '/';

		$path = 'payments';

		if (strncmp($gateway_payment_id, 'in_', strlen('in_')) === 0) {

			$path = 'invoices';

		} // end if;

		return sprintf('https://dashboard.stripe.com%s/%s/%s', $route, $path, $gateway_payment_id);

	} // end get_payment_url_on_gateway;

	/**
	 * Returns the external link to view the membership on the membership gateway.
	 *
	 * Return an empty string to hide the link element.
	 *
	 * @since 2.0.0
	 *
	 * @param string $gateway_subscription_id The gateway subscription id.
	 * @return string.
	 */
	public function get_subscription_url_on_gateway($gateway_subscription_id): string {

		$route = $this->test_mode ? '/test' : '/';

		return sprintf('https://dashboard.stripe.com%s/subscriptions/%s', $route, $gateway_subscription_id);

	} // end get_subscription_url_on_gateway;

	/**
	 * Returns the external link to view the customer on the gateway.
	 *
	 * Return an empty string to hide the link element.
	 *
	 * @since 2.0.7
	 *
	 * @param string $gateway_customer_id The gateway customer id.
	 * @return string.
	 */
	public function get_customer_url_on_gateway($gateway_customer_id): string {

		$route = $this->test_mode ? '/test' : '/';

		return sprintf('https://dashboard.stripe.com%s/customers/%s', $route, $gateway_customer_id);

	} // end get_customer_url_on_gateway;

} // end class Base_Stripe_Gateway;
