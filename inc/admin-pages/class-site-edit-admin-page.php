<?php
/**
 * Multisite Ultimate Site Edit New Admin Page.
 *
 * @package WP_Ultimo
 * @subpackage Admin_Pages
 * @since 2.0.0
 */

namespace WP_Ultimo\Admin_Pages;

// Exit if accessed directly
defined('ABSPATH') || exit;

use WP_Ultimo\Database\Sites\Site_Type;
use WP_Ultimo\Models\Site;

/**
 * Multisite Ultimate Site Edit New Admin Page.
 */
class Site_Edit_Admin_Page extends Edit_Admin_Page {

	/**
	 * Holds the ID for this page, this is also used as the page slug.
	 *
	 * @var string
	 */
	protected $id = 'wp-ultimo-edit-site';

	/**
	 * Is this a top-level menu or a submenu?
	 *
	 * @since 1.8.2
	 * @var string
	 */
	protected $type = 'submenu';

	/**
	 * Object ID being edited.
	 *
	 * @since 1.8.2
	 * @var string
	 */
	public $object_id = 'site';

	/**
	 * Is this a top-level menu or a submenu?
	 *
	 * @since 1.8.2
	 * @var string
	 */
	protected $parent = 'none';

	/**
	 * This page has no parent, so we need to highlight another sub-menu.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected $highlight_menu_slug = 'wp-ultimo-sites';

	/**
	 * If this number is greater than 0, a badge with the number will be displayed alongside the menu title
	 *
	 * @since 1.8.2
	 * @var integer
	 */
	protected $badge_count = 0;

	/**
	 * Holds the admin panels where this page should be displayed, as well as which capability to require.
	 *
	 * To add a page to the regular admin (wp-admin/), use: 'admin_menu' => 'capability_here'
	 * To add a page to the network admin (wp-admin/network), use: 'network_admin_menu' => 'capability_here'
	 * To add a page to the user (wp-admin/user) admin, use: 'user_admin_menu' => 'capability_here'
	 *
	 * @since 2.0.0
	 * @var array
	 */
	protected $supported_panels = [
		'network_admin_menu' => 'wu_edit_sites',
	];

	/**
	 * Registers the necessary scripts and styles for this admin page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_scripts(): void {

		parent::register_scripts();

		WP_Ultimo()->scripts->register_script('wu-screenshot-scraper', wu_get_asset('screenshot-scraper.js', 'js'), ['jquery']);

		wp_enqueue_script('wu-screenshot-scraper');

		wp_enqueue_media();

		wp_enqueue_editor();
	}

	/**
	 * Executes when the page is loaded - handles custom meta field deletion
	 *
	 * @return void
	 * @since 2.0.11
	 */
	public function page_loaded(): void {

		// Handle custom meta field deletion via GET parameters
		if (isset($_GET['delete_meta_key'], $_GET['_wpnonce'])) {
			$meta_key = sanitize_key($_GET['delete_meta_key']);
			
			if (wp_verify_nonce($_GET['_wpnonce'], 'delete_meta_' . $meta_key)) {
				$site_id = (int) wu_request('id');
				$result = wu_delete_site_meta($site_id, $meta_key);
				
				$redirect_url = wu_network_admin_url('wp-ultimo-edit-site', [
					'id' => $site_id,
					'options' => 'site_meta',
					'meta_deleted' => $result ? 1 : 0
				]);
				
				wp_safe_redirect($redirect_url);
				exit;
			}
		}

		// Call parent method to ensure normal form processing (including adding new fields)
		parent::page_loaded();
	}

	/**
	 * Register ajax forms that we use for site.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_forms(): void {
		/*
		 * Transfer site - Confirmation modal
		 */
		wu_register_form(
			'transfer_site',
			[
				'render'     => [$this, 'render_transfer_site_modal'],
				'handler'    => [$this, 'handle_transfer_site_modal'],
				'capability' => 'wu_transfer_sites',
			]
		);

		/*
		 * Delete Site - Confirmation modal
		 */

		add_filter(
			'wu_data_json_success_delete_site_modal',
			fn($unused_data_json) => [
				'redirect_url' => wu_network_admin_url('wp-ultimo-sites', ['deleted' => 1]),
			]
		);

		add_filter("wu_page_{$this->id}_load", [$this, 'add_new_site_template_warning_message']);
	}

	/**
	 * Adds the new site_template warning.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function add_new_site_template_warning_message(): void {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking if we need to show a message
		if (wu_request('wu-new-model')) {
			if ( ! $this->get_object() || $this->get_object()->get_type() !== Site_Type::SITE_TEMPLATE) {
				return;
			}

			\WP_Ultimo\UI\Tours::get_instance()->create_tour(
				'new_site_template_warning',
				[
					[
						'id'       => 'new-site-template-warning',
						'title'    => __('On adding a new Site Template...', 'multisite-ultimate'),
						'text'     => [
							__("You just successfully added a new site template to your Multisite Ultimate network and that's awesome!", 'multisite-ultimate'),
							__('Keep in mind that newly created site templates do not appear automatically in your checkout forms.', 'multisite-ultimate'),
							__('To make a site template available on registration, you will need to manually add it to the template selection field of your checkout forms.', 'multisite-ultimate'),
						],
						'buttons'  => [
							[
								'classes' => 'button wu-text-xs sm:wu-normal-case wu-float-left',
								'text'    => __('Go to Checkout Forms', 'multisite-ultimate'),
								'url'     => wu_network_admin_url('wp-ultimo-checkout-forms'),
							],
						],
						'attachTo' => [
							'element' => '#message.updated',
							'on'      => 'top',
						],
					],
				]
			);
		}
	}

	/**
	 * Renders the transfer confirmation form.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render_transfer_site_modal(): void {

		$site = wu_get_site(wu_request('id'));

		if ( ! $site) {
			return;
		}

		$fields = [
			'confirm'              => [
				'type'      => 'toggle',
				'title'     => __('Confirm Transfer', 'multisite-ultimate'),
				'desc'      => __('This will start the transfer of assets from one membership to another.', 'multisite-ultimate'),
				'html_attr' => [
					'v-model' => 'confirmed',
				],
			],
			'submit_button'        => [
				'type'            => 'submit',
				'title'           => __('Start Transfer', 'multisite-ultimate'),
				'placeholder'     => __('Start Transfer', 'multisite-ultimate'),
				'value'           => 'save',
				'classes'         => 'button button-primary wu-w-full',
				'wrapper_classes' => 'wu-items-end',
				'html_attr'       => [
					'v-bind:disabled' => '!confirmed',
				],
			],
			'id'                   => [
				'type'  => 'hidden',
				'value' => $site->get_id(),
			],
			'target_membership_id' => [
				'type'  => 'hidden',
				'value' => wu_request('target_membership_id'),
			],
		];

		$form = new \WP_Ultimo\UI\Form(
			'total-actions',
			$fields,
			[
				'views'                 => 'admin-pages/fields',
				'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
				'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
				'html_attr'             => [
					'data-wu-app' => 'transfer_site',
					'data-state'  => wp_json_encode(
						[
							'confirmed' => false,
						]
					),
				],
			]
		);

		$form->render();
	}

	/**
	 * Handles the transfer of site.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_transfer_site_modal(): void {

		global $wpdb;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in the form handler
		$site = wu_get_site(wu_request('id'));

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in the form handler
		$target_membership = wu_get_membership(wu_request('target_membership_id'));

		if ( ! $site) {
			wp_send_json_error(new \WP_Error('not-found', __('Site not found.', 'multisite-ultimate')));
		}

		if ( ! $target_membership) {
			wp_send_json_error(new \WP_Error('not-found', __('Membership not found.', 'multisite-ultimate')));
		}

		$site->set_membership_id($target_membership->get_id());

		$site->set_customer_id($target_membership->get_customer_id());

		$site->set_type('customer_owned');

		$saved = $site->save();

		if (is_wp_error($saved)) {
			wp_send_json_error($saved);
		}

		wp_send_json_success(
			[
				'redirect_url' => wu_network_admin_url(
					'wp-ultimo-edit-site',
					[
						'id'      => $site->get_id(),
						'updated' => 1,
					]
				),
			]
		);
	}

	/**
	 * Allow child classes to register widgets, if they need them.
	 *
	 * @since 1.8.2
	 * @return void
	 */
	public function register_widgets(): void {

		parent::register_widgets();

		$label = $this->get_object()->get_type_label();

		$class = $this->get_object()->get_type_class();

		$tag = "<span class='wu-bg-gray-200 wu-py-1 wu-px-2 wu-rounded-sm wu-text-xs wu-font-mono $class'>{$label}</span>";

		$this->add_fields_widget(
			'at_a_glance',
			[
				'title'                 => __('At a Glance', 'multisite-ultimate'),
				'position'              => 'normal',
				'classes'               => 'wu-overflow-hidden wu-m-0 wu--mt-1 wu--mx-3 wu--mb-3',
				'field_wrapper_classes' => 'wu-w-1/4 wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t-0 wu-border-l-0 wu-border-r wu-border-b-0 wu-border-gray-300 wu-border-solid wu-float-left wu-relative',
				'html_attr'             => [
					'style' => 'margin-top: -6px;',
				],
				'fields'                => [
					'type' => [
						'type'          => 'text-display',
						'title'         => __('Site Type', 'multisite-ultimate'),
						'display_value' => $tag,
						'tooltip'       => '',
					],
					'id'   => [
						'type'          => 'text-display',
						'copy'          => true,
						'title'         => __('Site ID', 'multisite-ultimate'),
						'display_value' => $this->get_object()->get_id(),
						'tooltip'       => '',
					],
				],
			]
		);

		$this->add_fields_widget(
			'description',
			[
				'title'    => __('Description', 'multisite-ultimate'),
				'position' => 'normal',
				'fields'   => [
					'description' => [
						'type'        => 'textarea',
						'title'       => __('Site Description', 'multisite-ultimate'),
						'placeholder' => __('Tell your customers what this site is about.', 'multisite-ultimate'),
						'value'       => $this->get_object()->get_option_blogdescription(),
						'html_attr'   => [
							'rows' => 3,
						],
					],
				],
			]
		);

		$this->add_tabs_widget(
			'options',
			[
				'title'    => __('Site Options', 'multisite-ultimate'),
				'position' => 'normal',
				'sections' => $this->get_site_option_sections(),
			]
		);

		$this->add_list_table_widget(
			'domains',
			[
				'title'        => __('Mapped Domains', 'multisite-ultimate'),
				'table'        => new \WP_Ultimo\List_Tables\Sites_Domain_List_Table(),
				'query_filter' => [$this, 'domain_query_filter'],
			]
		);

		if ($this->get_object()->get_type() === 'customer_owned') {
			$this->add_list_table_widget(
				'membership',
				[
					'title'        => __('Linked Membership', 'multisite-ultimate'),
					'table'        => new \WP_Ultimo\List_Tables\Customers_Membership_List_Table(),
					'query_filter' => function ($query) {

						$query['id'] = $this->get_object()->get_membership_id();

						return $query;
					},
				]
			);

			$this->add_list_table_widget(
				'customer',
				[
					'title'        => __('Linked Customer', 'multisite-ultimate'),
					'table'        => new \WP_Ultimo\List_Tables\Site_Customer_List_Table(),
					'query_filter' => function ($query) {

						$query['id'] = $this->get_object()->get_customer_id();

						return $query;
					},
				]
			);
		}

		$this->add_list_table_widget(
			'events',
			[
				'title'        => __('Events', 'multisite-ultimate'),
				'table'        => new \WP_Ultimo\List_Tables\Inside_Events_List_Table(),
				'query_filter' => [$this, 'query_filter'],
			]
		);

		$membership_selected = $this->get_object()->get_membership() ? $this->get_object()->get_membership()->to_search_results() : '';
		$template_selected   = $this->get_object()->get_template() ? $this->get_object()->get_template()->to_search_results() : '';

		$this->add_fields_widget(
			'save',
			[
				'html_attr' => [
					'data-wu-app' => 'site_type',
					'data-state'  => wp_json_encode(
						[
							'type'                   => $this->get_object()->get_type(),
							'original_membership_id' => $this->get_object()->get_membership_id(),
							'membership_id'          => $this->get_object()->get_membership_id(),
						]
					),
				],
				'fields'    => [
					// Fields for price
					'type_main'     => [
						'type'              => 'text-display',
						'title'             => __('Site Type', 'multisite-ultimate'),
						'display_value'     => __('Main Site', 'multisite-ultimate'),
						'tooltip'           => __('You can\'t change the main site type.', 'multisite-ultimate'),
						'wrapper_html_attr' => [
							'v-cloak' => '1',
							'v-show'  => 'type === "main"',
						],
					],
					'type'          => [
						'type'              => 'select',
						'title'             => __('Site Type', 'multisite-ultimate'),
						'placeholder'       => __('Select Site Type', 'multisite-ultimate'),
						'desc'              => __('Different site types have different options and settings.', 'multisite-ultimate'),
						'value'             => $this->get_object()->get_type(),
						'tooltip'           => '',
						'options'           => [
							'default'        => __('Regular WordPress', 'multisite-ultimate'),
							'site_template'  => __('Site Template', 'multisite-ultimate'),
							'customer_owned' => __('Customer-owned', 'multisite-ultimate'),
						],
						'html_attr'         => [
							'v-model' => 'type',
						],
						'wrapper_html_attr' => [
							'v-cloak' => '1',
							'v-show'  => 'type !== "main"',
						],
					],
					'categories'    => [
						'type'              => 'select',
						'title'             => __('Template Categories', 'multisite-ultimate'),
						'placeholder'       => __('e.g.: Landing Page, Health...', 'multisite-ultimate'),
						'desc'              => __('Customers will be able to filter by categories during signup.', 'multisite-ultimate'),
						'value'             => $this->get_object()->get_categories(),
						'options'           => Site::get_all_categories(),
						'html_attr'         => [
							'data-selectize-categories' => 1,
							'multiple'                  => 1,
						],
						'wrapper_html_attr' => [
							'v-show'  => "type === 'site_template'",
							'v-cloak' => '1',
						],
					],
					'membership_id' => [
						'type'              => 'model',
						'title'             => __('Associated Membership', 'multisite-ultimate'),
						'placeholder'       => __('Search Membership...', 'multisite-ultimate'),
						'desc'              => __('The membership that owns this site.', 'multisite-ultimate'),
						'value'             => $this->get_object()->get_membership_id(),
						'tooltip'           => '',
						'wrapper_html_attr' => [
							'v-show'  => "type === 'customer_owned'",
							'v-cloak' => 1,
						],
						'html_attr'         => [
							'data-model'        => 'membership',
							'data-value-field'  => 'id',
							'data-label-field'  => 'reference_code',
							'data-search-field' => 'reference_code',
							'data-max-items'    => 1,
							'data-selected'     => wp_json_encode($membership_selected),
						],
					],
					'transfer_note' => [
						'type'              => 'note',
						'desc'              => __('Changing the membership will transfer the site and all its assets to the new membership.', 'multisite-ultimate'),
						'classes'           => 'wu-p-2 wu-bg-red-100 wu-text-red-600 wu-rounded wu-w-full',
						'wrapper_html_attr' => [
							'v-show'  => '(original_membership_id != membership_id) && membership_id',
							'v-cloak' => '1',
						],
					],
					'submit_save'   => [
						'type'              => 'submit',
						'title'             => __('Save Site', 'multisite-ultimate'),
						'placeholder'       => __('Save Site', 'multisite-ultimate'),
						'value'             => 'save',
						'classes'           => 'button button-primary wu-w-full',
						'wrapper_html_attr' => [
							'v-show'  => 'original_membership_id == membership_id || !membership_id',
							'v-cloak' => 1,
						],
					],
					'transfer'      => [
						'type'              => 'link',
						'display_value'     => __('Transfer Site', 'multisite-ultimate'),
						'wrapper_classes'   => 'wu-bg-gray-200',
						'classes'           => 'button wubox wu-w-full wu-text-center',
						'wrapper_html_attr' => [
							'v-show'  => 'original_membership_id != membership_id && membership_id',
							'v-cloak' => '1',
						],
						'html_attr'         => [
							'v-bind:href' => "'" . wu_get_form_url(
								'transfer_site',
								[
									'id'                   => $this->get_object()->get_id(),
									'target_membership_id' => '',
								]
							) . "=' + membership_id",
							'title'       => __('Transfer Site', 'multisite-ultimate'),
						],
					],
				],
			]
		);

		$this->add_fields_widget(
			'active',
			[
				'title'  => __('Active', 'multisite-ultimate'),
				'fields' => [
					'active' => [
						'type'  => 'toggle',
						'title' => __('Active', 'multisite-ultimate'),
						'desc'  => __('Use this option to manually enable or disable this site.', 'multisite-ultimate'),
						'value' => $this->get_object()->is_active(),
					],
				],
			]
		);

		$this->add_fields_widget(
			'image',
			[
				'title'  => __('Site Image', 'multisite-ultimate'),
				'fields' => [
					'featured_image_id' => [
						'type'    => 'image',
						'stacked' => true,
						'title'   => __('Site Image', 'multisite-ultimate'),
						'desc'    => __('This image is used on lists of sites and other places. It can be automatically generated by the screenshot scraper.', 'multisite-ultimate'),
						'value'   => $this->get_object()->get_featured_image_id(),
						'img'     => $this->get_object()->get_featured_image(),
					],
					'scraper_note'      => [
						'type'            => 'note',
						'desc'            => __('You need to save the site for the change to take effect.', 'multisite-ultimate'),
						'wrapper_classes' => 'wu-hidden wu-scraper-note',
					],
					'scraper_error'     => [
						'type'            => 'note',
						'desc'            => '<span class="wu-scraper-error-message wu-p-2 wu-bg-red-100 wu-text-red-600 wu-rounded wu-block"></span>',
						'wrapper_classes' => 'wu-hidden wu-scraper-error',
					],
					'scraper_message'   => [
						'type'            => 'note',
						'desc'            => sprintf('<span class="wu-p-2 wu-bg-red-100 wu-text-red-600 wu-rounded wu-block">%s</span>', __('We detected that this network might be running locally. If that\'s the case, Multisite Ultimate will not be able to take a screenshot of the site. A site needs to be publicly available to the outside world in order for this feature to work.', 'multisite-ultimate')),
						'wrapper_classes' => \WP_Ultimo\Domain_Mapping\Helper::is_development_mode() ? '' : 'wu-hidden',
					],
					'scraper'           => [
						'type'    => 'submit',
						'title'   => __('Take Screenshot', 'multisite-ultimate'),
						'classes' => 'button wu-w-full',
					],
				],
			]
		);
	}

	/**
	 * Returns the list of sections and its fields for the site page.
	 *
	 * Can be filtered via 'wu_site_options_sections'.
	 *
	 * @see inc/managers/class-limitation-manager.php
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function get_site_option_sections() {

		$sections = [];

		$sections = apply_filters('wu_site_options_sections', $sections, $this->get_object());

		return $sections;
	}

	/**
	 * Returns the title of the page.
	 *
	 * @since 2.0.0
	 * @return string Title of the page.
	 */
	public function get_title() {

		return $this->edit ? __('Edit Site', 'multisite-ultimate') : __('Add new Site', 'multisite-ultimate');
	}

	/**
	 * Returns the title of menu for this page.
	 *
	 * @since 2.0.0
	 * @return string Menu label of the page.
	 */
	public function get_menu_title() {

		return __('Edit Site', 'multisite-ultimate');
	}

	/**
	 * Returns the action links for that page.
	 *
	 * @since 1.8.2
	 * @return array
	 */
	public function action_links() {

		return [
			[
				'url'   => network_admin_url('site-settings.php?id=' . $this->get_object()->get_id()),
				'label' => __('Go to the Default Edit Screen', 'multisite-ultimate'),
				'icon'  => 'wu-cog',
			],
			[
				'url'   => get_site_url($this->get_object()->get_id()),
				'label' => __('Visit Site', 'multisite-ultimate'),
				'icon'  => 'wu-link',
			],
			[
				'url'   => get_admin_url($this->get_object()->get_id()),
				'label' => __('Dashboard', 'multisite-ultimate'),
				'icon'  => 'dashboard',
			],
		];
	}

	/**
	 * Returns the labels to be used on the admin page.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_labels() {

		return [
			'edit_label'          => __('Edit Site', 'multisite-ultimate'),
			'add_new_label'       => __('Add new Site', 'multisite-ultimate'),
			'updated_message'     => __('Site updated with success!', 'multisite-ultimate'),
			'title_placeholder'   => __('Enter Site Name', 'multisite-ultimate'),
			'title_description'   => __('This name will be used as the site title.', 'multisite-ultimate'),
			'save_button_label'   => __('Save Site', 'multisite-ultimate'),
			'save_description'    => '',
			'delete_button_label' => __('Delete Site', 'multisite-ultimate'),
			'delete_description'  => __('Be careful. This action is irreversible.', 'multisite-ultimate'),
		];
	}

	/**
	 * Filters the list table to return only relevant events.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Query args passed to the list table.
	 * @return array Modified query args.
	 */
	public function domain_query_filter($args) {

		$extra_args = [
			'blog_id' => absint($this->get_object()->get_id()),
		];

		return array_merge($args, $extra_args);
	}

	/**
	 * Filters the list table to return only relevant events.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Query args passed to the list table.
	 * @return array Modified query args.
	 */
	public function query_filter($args) {

		$extra_args = [
			'object_type' => 'site',
			'object_id'   => absint($this->get_object()->get_id()),
		];

		return array_merge($args, $extra_args);
	}

	/**
	 * Returns the object being edit at the moment.
	 *
	 * @since 2.0.0
	 * @return \WP_Ultimo\Models\Site
	 */
	public function get_object() {

		if (null !== $this->object) {
			return $this->object;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just getting the object ID from the URL
		$item_id = wu_request('id', 0);

		$item = wu_get_site($item_id);

		if ( ! $item) {
			wp_safe_redirect(wu_network_admin_url('wp-ultimo-sites'));

			exit;
		}

		$this->object = $item;

		return $this->object;
	}

	/**
	 * Sites have titles.
	 *
	 * @since 2.0.0
	 */
	public function has_title(): bool {

		return true;
	}

	/**
	 * Should implement the processes necessary to save the changes made to the object.
	 *
	 * @since 2.0.23
	 * @return true
	 */
	public function handle_save() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in parent::handle_save()
		$_POST['categories'] = wu_get_isset($_POST, 'categories', []);

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in parent::handle_save()
		if (isset($_POST['type']) && Site_Type::CUSTOMER_OWNED !== $_POST['type']) {
			$_POST['membership_id'] = false;
			$_POST['customer_id']   = false;
		}

		$object = $this->get_object();

		/**
		 * Deal with custom site metadata
		 */
		$custom_meta_keys = wu_get_all_site_meta($object->get_id(), true);

		foreach ($custom_meta_keys as $key => $value) {
			wu_update_site_meta(
				$object->get_id(),
				$key,
				wu_request("meta_key_$key"),
				$value['type'],
				$value['title'],
				wu_get_isset($value, 'form', null),
				wu_get_isset($value, 'step', null),
				wu_get_isset($value, 'description', null),
				wu_get_isset($value, 'tooltip', null),
				wu_get_isset($value, 'options', [])
			);
		}

		foreach (wu_get_isset($_POST, 'new_meta_fields', []) as $meta_field) {
			$slug = sanitize_key(wu_get_isset($meta_field, 'slug', ''));

			if (empty($slug)) {
				continue;
			}

			$title = ! empty($meta_field['title']) ? $meta_field['title'] : wu_slug_to_name($slug);
			$type  = ! empty($meta_field['type']) ? $meta_field['type'] : 'text';
			$value = wu_get_isset($meta_field, 'value', '');

			wu_update_site_meta($object->get_id(), $slug, $value, $type, $title);
		}

		unset($_POST['new_meta_fields']);

		return parent::handle_save();
	}
}
