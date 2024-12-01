<?php
/**
 * Adds the Site_Actions_Element UI to the Admin Panel.
 *
 * @package WP_Ultimo
 * @subpackage UI
 * @since 2.0.0
 */

namespace WP_Ultimo\UI;

use WP_Ultimo\UI\Base_Element;
use WP_Ultimo\Database\Memberships\Membership_Status;
use WP_Ultimo\Models\Site;
use WP_Ultimo\Models\Membership;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Adds the Checkout Element UI to the Admin Panel.
 *
 * @since 2.0.0
 */
class Site_Actions_Element extends Base_Element {

	use \WP_Ultimo\Traits\Singleton;

	/**
	 * The id of the element.
	 *
	 * Something simple, without prefixes, like 'checkout', or 'pricing-tables'.
	 *
	 * This is used to construct shortcodes by prefixing the id with 'wu_'
	 * e.g. an id checkout becomes the shortcode 'wu_checkout' and
	 * to generate the Gutenberg block by prefixing it with 'wp-ultimo/'
	 * e.g. checkout would become the block 'wp-ultimo/checkout'.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $id = 'site-actions';

	/**
	 * Controls if this is a public element to be used in pages/shortcodes by user.
	 *
	 * @since 2.0.24
	 * @var boolean
	 */
	protected $public = true;

	/**
	 * The current site.
	 *
	 * @since 2.2.0
	 * @var Site
	 */
	public $site;

	/**
	 * The current membership.
	 *
	 * @since 2.2.0
	 * @var Membership
	 */
	public $membership;

	/**
	 * Loads the required scripts.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_scripts() {

		add_wubox();

	} // end register_scripts;
	/**
	 * The icon of the UI element.
	 * e.g. return fa fa-search
	 *
	 * @since 2.0.0
	 * @param string $context One of the values: block, elementor or bb.
	 */
	public function get_icon($context = 'block'): string {

		if ($context === 'elementor') {

			return 'eicon-info-circle-o';

		} // end if;

		return 'fa fa-search';

	} // end get_icon;

	/**
	 * The title of the UI element.
	 *
	 * This is used on the Blocks list of Gutenberg.
	 * You should return a string with the localized title.
	 * e.g. return __('My Element', 'wp-ultimo').
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_title() {

		return __('Actions', 'wp-ultimo');

	} // end get_title;

	/**
	 * The description of the UI element.
	 *
	 * This is also used on the Gutenberg block list
	 * to explain what this block is about.
	 * You should return a string with the localized title.
	 * e.g. return __('Adds a checkout form to the page', 'wp-ultimo').
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_description() {

		return __('Adds a checkout form block to the page.', 'wp-ultimo');

	} // end get_description;

	/**
	 * The list of fields to be added to Gutenberg.
	 *
	 * If you plan to add Gutenberg controls to this block,
	 * you'll need to return an array of fields, following
	 * our fields interface (@see inc/ui/class-field.php).
	 *
	 * You can create new Gutenberg panels by adding fields
	 * with the type 'header'. See the Checkout Elements for reference.
	 *
	 * @see inc/ui/class-checkout-element.php
	 *
	 * Return an empty array if you don't have controls to add.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function fields() {

		$fields = array();

		$fields['header'] = array(
			'title' => __('General', 'wp-ultimo'),
			'desc'  => __('General', 'wp-ultimo'),
			'type'  => 'header',
		);

		$fields['show_change_password'] = array(
			'type'    => 'toggle',
			'title'   => __('Show Change Password', 'wp-ultimo'),
			'desc'    => __('Toggle to show/hide the password link.', 'wp-ultimo'),
			'tooltip' => '',
			'value'   => 1,
		);

		$fields['show_change_default_site'] = array(
			'type'    => 'toggle',
			'title'   => __('Show Change Default Site', 'wp-ultimo'),
			'desc'    => __('Toggle to show/hide the change default site link.', 'wp-ultimo'),
			'tooltip' => '',
			'value'   => 1,
		);

		$fields['show_change_payment_method'] = array(
			'type'    => 'toggle',
			'title'   => __('Show Change Payment Method', 'wp-ultimo'),
			'desc'    => __('Toggle to show/hide the option to cancel the current payment method.', 'wp-ultimo'),
			'tooltip' => '',
			'value'   => 1,
		);

		$pages = get_pages(array(
			'exclude' => array(get_the_ID()),
		));

		$pages = $pages ? $pages : array();

		$pages_list = array(0 => __('Default', 'wp-ultimo'));

		foreach ($pages as $page) {

			$pages_list[$page->ID] = $page->post_title;

		} // end foreach;

		$fields['redirect_after_delete'] = array(
			'type'    => 'select',
			'title'   => __('Redirect After Delete', 'wp-ultimo'),
			'value'   => 0,
			'desc'    => __('The page to redirect user after delete current site.', 'wp-ultimo'),
			'tooltip' => '',
			'options' => $pages_list,
		);

		return $fields;

	} // end fields;

	/**
	 * The list of keywords for this element.
	 *
	 * Return an array of strings with keywords describing this
	 * element. Gutenberg uses this to help customers find blocks.
	 *
	 * e.g.:
	 * return array(
	 *  'WP Ultimo',
	 *  'Actions',
	 *  'Form',
	 *  'Cart',
	 * );
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function keywords() {

		return array(
			'WP Ultimo',
			'Actions',
			'Form',
			'Cart',
		);

	} // end keywords;

	/**
	 * List of default parameters for the element.
	 *
	 * If you are planning to add controls using the fields,
	 * it might be a good idea to use this method to set defaults
	 * for the parameters you are expecting.
	 *
	 * These defaults will be used inside a 'wp_parse_args' call
	 * before passing the parameters down to the block render
	 * function and the shortcode render function.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function defaults() {

		return array(
			'show_change_password'       => 1,
			'show_change_default_site'   => 1,
			'show_change_payment_method' => 1,
			'redirect_after_delete'      => 0,
		);

	} // end defaults;

	/**
	 * Runs early on the request lifecycle as soon as we detect the shortcode is present.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function setup() {

		$this->site = WP_Ultimo()->currents->get_site();

		if (!$this->site || !$this->site->is_customer_allowed()) {

			$this->site = false;

		} // end if;

		$this->membership = WP_Ultimo()->currents->get_membership();

		if (!$this->membership) {

			$this->set_display(false);

			return;

		} // end if;

	} // end setup;

	/**
	 * Allows the setup in the context of previews.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function setup_preview() {

		$this->site = wu_mock_site();

		$this->membership = wu_mock_membership();

	} // end setup_preview;

	/**
	 * Overload the init to add site-related forms.
	 *
	 * @since 2.0.21
	 * @return void
	 */
	public function init() {

		parent::init();

		$this->register_forms();

	} // end init;

	/**
	 * Register forms
	 *
	 * @since 2.0.21
	 * @return void
	 */
	public function register_forms() {

		wu_register_form('change_password', array(
			'render'     => array($this, 'render_change_password'),
			'handler'    => array($this, 'handle_change_password'),
			'capability' => 'exist',
		));

		wu_register_form('delete_site', array(
			'render'     => array($this, 'render_delete_site'),
			'handler'    => array($this, 'handle_delete_site'),
			'capability' => 'exist',
		));

		wu_register_form('change_default_site', array(
			'render'     => array($this, 'render_change_default_site'),
			'handler'    => array($this, 'handle_change_default_site'),
			'capability' => 'exist',
		));

		wu_register_form('cancel_payment_method', array(
			'render'     => array($this, 'render_cancel_payment_method'),
			'handler'    => array($this, 'handle_cancel_payment_method'),
			'capability' => 'exist',
		));

		wu_register_form('cancel_membership', array(
			'render'     => array($this, 'render_cancel_membership'),
			'handler'    => array($this, 'handle_cancel_membership'),
			'capability' => 'exist',
		));

	} // end register_forms;

	/**
	 * Returns the actions for the element. These can be filtered.
	 *
	 * @since 2.0.0
	 * @param array $atts Parameters of the block/shortcode.
	 * @return array
	 */
	public function get_actions($atts) {

		$actions = array();

		$all_blogs = get_blogs_of_user(get_current_user_id());

		$is_template_switching_enabled = wu_get_setting('allow_template_switching', true);

		if ($is_template_switching_enabled && $this->site) {

			$actions['template_switching'] = array(
				'label'        => __('Change Site Template', 'wp-ultimo'),
				'icon_classes' => 'dashicons-wu-edit wu-align-middle',
				'href'         => add_query_arg(array(
					'page' => 'wu-template-switching',
				), get_admin_url($this->site->get_id())),
			);

		} // end if;

		if (count($all_blogs) > 1 && wu_get_isset($atts, 'show_change_default_site')) {

			$actions['default_site'] = array(
				'label'        => __('Change Default Site', 'wp-ultimo'),
				'icon_classes' => 'dashicons-wu-edit wu-align-middle',
				'classes'      => 'wubox',
				'href'         => wu_get_form_url('change_default_site'),
			);

		} // end if;

		if (wu_get_isset($atts, 'show_change_password')) {

			$actions['change_password'] = array(
				'label'        => __('Change Password', 'wp-ultimo'),
				'icon_classes' => 'dashicons-wu-edit wu-align-middle',
				'classes'      => 'wubox',
				'href'         => wu_get_form_url('change_password'),
			);

		} // end if;

		$payment_gateway = $this->membership ? $this->membership->get_gateway() : false;

		if (wu_get_isset($atts, 'show_change_payment_method') && $payment_gateway) {

			$actions['cancel_payment_method'] = array(
				'label'        => __('Cancel Current Payment Method', 'wp-ultimo'),
				'icon_classes' => 'dashicons-wu-edit wu-align-middle',
				'classes'      => 'wubox',
				'href'         => wu_get_form_url('cancel_payment_method', array(
					'membership'   => $this->membership->get_hash(),
					'redirect_url' => wu_get_current_url(),
				)),
			);

		} // end if;

		return apply_filters('wu_element_get_site_actions', $actions, $atts, $this->site, $this->membership);

	} // end get_actions;

	/**
	 * Returns the danger actions actions for the element. These can be filtered.
	 *
	 * @since 2.0.21
	 * @param array $atts Parameters of the block/shortcode.
	 * @return array
	 */
	public function get_danger_zone_actions($atts) {

		$actions = array();

		if ($this->site) {

			$actions = array_merge(array(
				'delete_site' => array(
					'label'        => __('Delete Site', 'wp-ultimo'),
					'icon_classes' => 'dashicons-wu-edit wu-align-middle',
					'classes'      => 'wubox wu-text-red-500',
					'href'         => wu_get_form_url('delete_site', array(
						'site'         => $this->site->get_hash(),
						'redirect_url' => !$atts['redirect_after_delete'] ? false : get_page_link($atts['redirect_after_delete']),
					)),
				),
			), $actions);

		} // end if;

		if ($this->membership && $this->membership->is_recurring() && $this->membership->get_status() !== Membership_Status::CANCELLED) {

			$actions = array_merge(array(
				'cancel_membership' => array(
					'label'        => __('Cancel Membership', 'wp-ultimo'),
					'icon_classes' => 'dashicons-wu-edit wu-align-middle',
					'classes'      => 'wubox wu-text-red-500',
					'href'         => wu_get_form_url('cancel_membership', array(
						'membership'   => $this->membership->get_hash(),
						'redirect_url' => wu_get_current_url(),
					)),
				),
			), $actions);

		} // end if;

		return apply_filters('wu_element_get_danger_zone_site_actions', $actions);

	} // end get_danger_zone_actions;

	/**
	 * Renders the delete site modal.
	 *
	 * @since 2.0.21
	 * @return void
	 */
	public function render_delete_site() {

		$site = wu_get_site_by_hash(wu_request('site'));

		$error = '';

		if (!$site) {

			$error = __('Site not selected.', 'wp-ultimo');

		} // end if;

		$customer = wu_get_current_customer();

		if (!$customer || $customer->get_id() !== $site->get_customer_id()) {

			$error = __('You are not allowed to do this.', 'wp-ultimo');

		} // end if;

		if (!empty($error)) {

			$error_field = array(
				'error_message' => array(
					'type' => 'note',
					'desc' => $error,
				),
			);

			$form = new \WP_Ultimo\UI\Form('change_password', $error_field, array(
				'views'                 => 'admin-pages/fields',
				'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
				'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
			));

			$form->render();

			return;

		} // end if;

		$fields = array(
			'site'          => array(
				'type'  => 'hidden',
				'value' => wu_request('site'),
			),
			'redirect_url'  => array(
				'type'  => 'hidden',
				'value' => wu_request('redirect_url'),
			),
			'confirm'       => array(
				'type'      => 'toggle',
				'title'     => __('Confirm Site Deletion', 'wp-ultimo'),
				'desc'      => __('This action can not be undone.', 'wp-ultimo'),
				'html_attr' => array(
					'v-model' => 'confirmed',
				),
			),
			'submit_button' => array(
				'type'            => 'submit',
				'title'           => __('Delete Site', 'wp-ultimo'),
				'placeholder'     => __('Delete Site', 'wp-ultimo'),
				'value'           => 'save',
				'classes'         => 'button button-primary wu-w-full',
				'wrapper_classes' => 'wu-items-end',
				'html_attr'       => array(
					'v-bind:disabled' => '!confirmed',
				),
			),
		);

		$form = new \WP_Ultimo\UI\Form('change_password', $fields, array(
			'views'                 => 'admin-pages/fields',
			'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
			'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
			'html_attr'             => array(
				'data-wu-app' => 'change_password',
				'data-state'  => wu_convert_to_state(array(
					'confirmed' => false,
				)),
			),
		));

		$form->render();

	} // end render_delete_site;

	/**
	 * Handles the delete site modal.
	 *
	 * @since 2.0.21
	 *
	 * @return void|WP_Error Void or WP_Error.
	 */
	public function handle_delete_site() {

		global $wpdb;

		$site = wu_get_site_by_hash(wu_request('site'));

		if (!$site || !$site->is_customer_allowed()) {

			return new \WP_Error('error', __('An unexpected error happened.', 'wp-ultimo'));

		} // end if;

		$customer = wu_get_current_customer();

		if (!$customer || $customer->get_id() !== $site->get_customer_id()) {

			return new \WP_Error('error', __('You are not allowed to do this.', 'wp-ultimo'));

		} // end if;

		$wpdb->query('START TRANSACTION');

		try {

			$saved = $site->delete();

			if (is_wp_error($saved)) {

				$wpdb->query('ROLLBACK');

				return $saved;

			} // end if;

		} catch (\Throwable $e) {

			$wpdb->query('ROLLBACK');

			return new \WP_Error('exception', $e->getMessage());

		} // end try;

		$wpdb->query('COMMIT');

		$redirect_url = wu_request('redirect_url');

		$redirect_url = add_query_arg(array(
			'site_deleted' => true,
		), wu_request('redirect_url') ?? user_admin_url());

		wp_send_json_success(array(
			'redirect_url' => $redirect_url,
		));

	} // end handle_delete_site;

	/**
	 * Renders the change password modal.
	 *
	 * @since 2.0.21
	 * @return void
	 */
	public function render_change_password() {

		$fields = array(
			'password'          => array(
				'type'        => 'password',
				'title'       => __('Current Password', 'wp-ultimo'),
				'placeholder' => __('******', 'wp-ultimo'),
			),
			'new_password'      => array(
				'type'        => 'password',
				'title'       => __('New Password', 'wp-ultimo'),
				'placeholder' => __('******', 'wp-ultimo'),
				'meter'       => true,
			),
			'new_password_conf' => array(
				'type'        => 'password',
				'placeholder' => __('******', 'wp-ultimo'),
				'title'       => __('Confirm New Password', 'wp-ultimo'),
			),
			'submit_button'     => array(
				'type'            => 'submit',
				'title'           => __('Reset Password', 'wp-ultimo'),
				'value'           => 'save',
				'classes'         => 'button button-primary wu-w-full',
				'wrapper_classes' => 'wu-items-end',
				'html_attr'       => array(
					// 'v-bind:disabled' => '!confirmed',
				),
			),
		);

		$form = new \WP_Ultimo\UI\Form('change_password', $fields, array(
			'views'                 => 'admin-pages/fields',
			'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
			'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
			'html_attr'             => array(
				'data-wu-app' => 'change_password',
				'data-state'  => wu_convert_to_state(),
			),
		));

		$form->render();

	} // end render_change_password;

	/**
	 * Handles the password reset form.
	 *
	 * @since 2.0.21
	 * @return void
	 */
	public function handle_change_password() {

		$user = wp_get_current_user();

		if (!$user) {

			$error = new \WP_Error('user-dont-exist', __('Something went wrong.', 'wp-ultimo'));

			wp_send_json_error($error);

		} // end if;

		$current_password = wu_request('password');

		if (!wp_check_password($current_password, $user->user_pass, $user->ID)) {

			$error = new \WP_Error('wrong-password', __('Your current password is wrong.', 'wp-ultimo'));

			wp_send_json_error($error);

		} // end if;

		$new_password      = wu_request('new_password');
		$new_password_conf = wu_request('new_password_conf');

		if (!$new_password || strlen((string) $new_password) < 6) {

			$error = new \WP_Error('password-min-length', __('The new password must be at least 6 characters long.', 'wp-ultimo'));

			wp_send_json_error($error);

		} // end if;

		if ($new_password !== $new_password_conf) {

			$error = new \WP_Error('passwords-dont-match', __('New passwords do not match.', 'wp-ultimo'));

			wp_send_json_error($error);

		} // end if;

		reset_password($user, $new_password);

		// Log-in again.
		wp_set_auth_cookie($user->ID);
		wp_set_current_user($user->ID);
		do_action('wp_login', $user->user_login, $user); // PHPCS:ignore

		wp_send_json_success(array(
			'redirect_url' => add_query_arg('updated', 1, $_SERVER['HTTP_REFERER']),
		));

	} // end handle_change_password;

	/**
	 * Renders the change current site modal.
	 *
	 * @since 2.0.21
	 * @return void
	 */
	public function render_change_default_site() {

		$all_blogs = get_blogs_of_user(get_current_user_id());

		$option_blogs = array();

		foreach ($all_blogs as $key => $blog) {

			$option_blogs[$blog->userblog_id] = get_home_url($blog->userblog_id);

		} // end foreach;

		$primary_blog = get_user_meta(get_current_user_id(), 'primary_blog', true);

		$fields = array(
			'new_primary_site' => array(
				'type'      => 'select',
				'title'     => __('Primary Site', 'wp-ultimo'),
				'desc'      => __('Change the primary site of your network.', 'wp-ultimo'),
				'options'   => $option_blogs,
				'value'     => $primary_blog,
				'html_attr' => array(
					'v-model' => 'new_primary_site',
				),
			),
			'submit_button'    => array(
				'type'            => 'submit',
				'title'           => __('Change Default Site', 'wp-ultimo'),
				'value'           => 'save',
				'classes'         => 'button button-primary wu-w-full',
				'wrapper_classes' => 'wu-items-end',
				'html_attr'       => array(
					'v-bind:disabled' => 'new_primary_site === "' . $primary_blog . '"',
				),
			),
		);

		$form = new \WP_Ultimo\UI\Form('change_default_site', $fields, array(
			'views'                 => 'admin-pages/fields',
			'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
			'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
			'html_attr'             => array(
				'data-wu-app' => 'change_default_site',
				'data-state'  => wu_convert_to_state(array(
					'new_primary_site' => $primary_blog
				)),
			),
		));

		$form->render();

	} // end render_change_default_site;

	/**
	 * Handles the change default site form.
	 *
	 * @since 2.0.21
	 * @return void
	 */
	public function handle_change_default_site() {

		$new_primary_site = wu_request('new_primary_site');

		if ($new_primary_site) {

			update_user_meta(get_current_user_id(), 'primary_blog', $new_primary_site);

			wp_send_json_success(array(
				'redirect_url' => add_query_arg('updated', 1, $_SERVER['HTTP_REFERER']),
			));

		} // end if;

		$error = new \WP_Error('no-site-selected', __('You need to select a new primary site.', 'wp-ultimo'));

		wp_send_json_error($error);

	} // end handle_change_default_site;

	/**
	 * Renders the cancel payment method modal.
	 *
	 * @since 2.1.2
	 * @return void
	 */
	public function render_cancel_payment_method() {

		$membership = wu_get_membership_by_hash(wu_request('membership'));

		$error = '';

		if (!$membership) {

			$error = __('Membership not selected.', 'wp-ultimo');

		} // end if;

		$customer = wu_get_current_customer();

		if (!is_super_admin() && (!$customer || $customer->get_id() !== $membership->get_customer_id())) {

			$error = __('You are not allowed to do this.', 'wp-ultimo');

		} // end if;

		if (!empty($error)) {

			$error_field = array(
				'error_message' => array(
					'type' => 'note',
					'desc' => $error,
				),
			);

			$form = new \WP_Ultimo\UI\Form('cancel_payment_method', $error_field, array(
				'views'                 => 'admin-pages/fields',
				'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
				'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
			));

			$form->render();

			return;

		} // end if;

		$fields = array(
			'membership'    => array(
				'type'  => 'hidden',
				'value' => wu_request('membership'),
			),
			'redirect_url'  => array(
				'type'  => 'hidden',
				'value' => wu_request('redirect_url'),
			),
			'confirm'       => array(
				'type'      => 'toggle',
				'title'     => __('Confirm Payment Method Cancellation', 'wp-ultimo'),
				'desc'      => __('This action can not be undone.', 'wp-ultimo'),
				'html_attr' => array(
					'v-model' => 'confirmed',
				),
			),
			'submit_button' => array(
				'type'            => 'submit',
				'title'           => __('Cancel Payment Method', 'wp-ultimo'),
				'placeholder'     => __('Cancel Payment Method', 'wp-ultimo'),
				'value'           => 'save',
				'classes'         => 'button button-primary wu-w-full',
				'wrapper_classes' => 'wu-items-end',
				'html_attr'       => array(
					'v-bind:disabled' => '!confirmed',
				),
			),
		);

		$form = new \WP_Ultimo\UI\Form('cancel_payment_method', $fields, array(
			'views'                 => 'admin-pages/fields',
			'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
			'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
			'html_attr'             => array(
				'data-wu-app' => 'cancel_payment_method',
				'data-state'  => wu_convert_to_state(array(
					'confirmed' => false,
				)),
			),
		));

		$form->render();

	} // end render_cancel_payment_method;

	/**
	 * Handles the payment method cancellation.
	 *
	 * @since 2.1.2
	 * @return void
	 */
	public function handle_cancel_payment_method() {

		$membership = wu_get_membership_by_hash(wu_request('membership'));

		if (!$membership) {

			$error = new \WP_Error('error', __('An unexpected error happened.', 'wp-ultimo'));

			wp_send_json_error($error);

			return;

		} // end if;

		$customer = wu_get_current_customer();

		if (!is_super_admin() && (!$customer || $customer->get_id() !== $membership->get_customer_id())) {

			$error = new \WP_Error('error', __('You are not allowed to do this.', 'wp-ultimo'));

			wp_send_json_error($error);

			return;

		} // end if;

		$membership->set_gateway('');
		$membership->set_gateway_subscription_id('');
		$membership->set_gateway_customer_id('');
		$membership->set_auto_renew(false);

		$membership->save();

		$redirect_url = wu_request('redirect_url');

		$redirect_url = add_query_arg(array(
			'payment_gateway_cancelled' => true,
		), $redirect_url ?? user_admin_url());

		wp_send_json_success(array(
			'redirect_url' => $redirect_url,
		));

	} // end handle_cancel_payment_method;

	/**
	 * Renders the cancel payment method modal.
	 *
	 * @since 2.1.2
	 * @return void
	 */
	public function render_cancel_membership() {

		$membership = wu_get_membership_by_hash(wu_request('membership'));

		$error = '';

		if (!$membership) {

			$error = __('Membership not selected.', 'wp-ultimo');

		} // end if;

		$customer = wu_get_current_customer();

		if (!is_super_admin() && (!$customer || $customer->get_id() !== $membership->get_customer_id())) {

			$error = __('You are not allowed to do this.', 'wp-ultimo');

		} // end if;

		if (!empty($error)) {

			$error_field = array(
				'error_message' => array(
					'type' => 'note',
					'desc' => $error,
				),
			);

			$form = new \WP_Ultimo\UI\Form('cancel_membership', $error_field, array(
				'views'                 => 'admin-pages/fields',
				'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
				'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
			));

			$form->render();

			return;

		} // end if;

		$fields = array(
			'membership'               => array(
				'type'  => 'hidden',
				'value' => wu_request('membership'),
			),
			'redirect_url'             => array(
				'type'  => 'hidden',
				'value' => wu_request('redirect_url'),
			),
			'cancellation_reason'      => array(
				'type'      => 'select',
				'title'     => __('Please tell us why you are cancelling.', 'wp-ultimo'),
				'desc'      => __('We would love your feedback.', 'wp-ultimo'),
				'html_attr' => array(
					'v-model' => 'cancellation_reason',
				),
				'default'   => '',
				'options'   => array(
					''                 => __('Select a reason', 'wp-ultimo'),
					'unused'           => __('I no longer need it', 'wp-ultimo'),
					'too_expensive'    => __('It\'s too expensive', 'wp-ultimo'),
					'missing_features' => __('I need more features', 'wp-ultimo'),
					'switched_service' => __('Switched to another service', 'wp-ultimo'),
					'customer_service' => __('Customer support is less than expected', 'wp-ultimo'),
					'too_complex'      => __('Too complex', 'wp-ultimo'),
					'other'            => __('Other', 'wp-ultimo'),
				),
			),
			'cancellation_explanation' => array(
				'type'              => 'textarea',
				'title'             => __('Please provide additional details.', 'wp-ultimo'),
				'wrapper_html_attr' => array(
					'v-show' => 'cancellation_reason === "other"',
				),
			),
			'confirm'                  => array(
				'type'      => 'text',
				'title'     => __('Type <code class="wu-text-red-600">CANCEL</code> to confirm this membership cancellation.', 'wp-ultimo'),
				'html_attr' => array(
					'v-model' => 'confirmation',
				),
			),
		);

		$next_charge = false;

		if ($membership->is_recurring() && ($membership->is_active() || $membership->get_status() === Membership_Status::TRIALING)) {

			$next_charge = strtotime($membership->get_date_expiration());

		} // end if;

		if ($next_charge && $next_charge > time()) {

			$fields['next_charge'] = array(
				'type' => 'note',
				// translators: %s: Next charge date.
				'desc' => sprintf(__('Your sites will stay working until %s.', 'wp-ultimo'), date_i18n(get_option('date_format'), $next_charge)),
			);

		} // end if;

		$fields['submit_button'] = array(
			'type'            => 'submit',
			'title'           => __('Cancel Membership', 'wp-ultimo'),
			'placeholder'     => __('Cancel Membership', 'wp-ultimo'),
			'value'           => 'save',
			'classes'         => 'button button-primary wu-w-full',
			'wrapper_classes' => 'wu-items-end',
			'html_attr'       => array(
				'v-bind:disabled' => 'confirmation !== "' . __('CANCEL', 'wp-ultimo') . '" || cancellation_reason === ""',
			),
		);

		$form = new \WP_Ultimo\UI\Form('cancel_membership', $fields, array(
			'views'                 => 'admin-pages/fields',
			'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
			'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
			'html_attr'             => array(
				'data-wu-app' => 'cancel_membership',
				'data-state'  => wu_convert_to_state(array(
					'confirmation'        => '',
					'cancellation_reason' => '',
				)),
			),
		));

		$form->render();

	} // end render_cancel_membership;

	/**
	 * Handles the payment method cancellation.
	 *
	 * @since 2.1.2
	 * @return void
	 */
	public function handle_cancel_membership() {

		$membership = wu_get_membership_by_hash(wu_request('membership'));

		if (!$membership) {

			$error = new \WP_Error('error', __('An unexpected error happened.', 'wp-ultimo'));

			wp_send_json_error($error);

			return;

		} // end if;

		$customer = wu_get_current_customer();

		if (!is_super_admin() && (!$customer || $customer->get_id() !== $membership->get_customer_id())) {

			$error = new \WP_Error('error', __('You are not allowed to do this.', 'wp-ultimo'));

			wp_send_json_error($error);

			return;

		} // end if;

		$cancellation_options = array(
			'unused'           => __('I no longer need it', 'wp-ultimo'),
			'too_expensive'    => __('It\'s too expensive', 'wp-ultimo'),
			'missing_features' => __('I need more features', 'wp-ultimo'),
			'switched_service' => __('Switched to another service', 'wp-ultimo'),
			'customer_service' => __('Customer support is less than expected', 'wp-ultimo'),
			'too_complex'      => __('Too complex', 'wp-ultimo'),
			'other'            => wu_request('cancellation_explanation'),
		);

		$reason = wu_get_isset($cancellation_options, wu_request('cancellation_reason'), '');

		$membership->cancel($reason);

		$redirect_url = wu_request('redirect_url');

		$redirect_url = add_query_arg(array(
			'payment_gateway_cancelled' => true,
		), !empty($redirect_url) ? $redirect_url : user_admin_url());

		wp_send_json_success(array(
			'redirect_url' => $redirect_url,
		));

	} // end handle_cancel_membership;

	/**
	 * The content to be output on the screen.
	 *
	 * Should return HTML markup to be used to display the block.
	 * This method is shared between the block render method and
	 * the shortcode implementation.
	 *
	 * @since 2.0.0
	 *
	 * @param array       $atts Parameters of the block/shortcode.
	 * @param string|null $content The content inside the shortcode.
	 * @return string
	 */
	public function output($atts, $content = null) {

		$atts['actions'] = $this->get_actions($atts);

		$atts['danger_zone_actions'] = $this->get_danger_zone_actions($atts);

		return wu_get_template_contents('dashboard-widgets/site-actions', $atts);

	} // end output;

} // end class Site_Actions_Element;
