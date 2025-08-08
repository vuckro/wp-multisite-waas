<?php
/**
 * Site Functions
 *
 * @package WP_Ultimo\Functions
 * @since   2.0.0
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Returns the current site.
 *
 * @since 2.0.0
 * @return \WP_Ultimo\Models\Site
 */
function wu_get_current_site() {

	static $sites = array();
	$blog_id      = get_current_blog_id();

	if ( ! isset( $sites[ $blog_id ] ) ) {
		$sites[ $blog_id ] = new \WP_Ultimo\Models\Site(get_blog_details($blog_id));
	}
	return $sites[ $blog_id ];
}

/**
 * Returns the site object
 *
 * @since 2.0.0
 *
 * @param int $id The id of the site.
 * @return \WP_Ultimo\Models\Site|false
 */
function wu_get_site($id) {

	return \WP_Ultimo\Models\Site::get_by_id($id);
}

/**
 * Gets a site based on the hash.
 *
 * @since 2.0.0
 *
 * @param string $hash The hash for the payment.
 * @return \WP_Ultimo\Models\Site|false
 */
function wu_get_site_by_hash($hash) {

	return \WP_Ultimo\Models\Site::get_by_hash($hash);
}

/**
 * Queries sites.
 *
 * @since 2.0.0
 *
 * @param array $query Query arguments.
 * @return \WP_Ultimo\Models\Site[]
 */
function wu_get_sites($query = []) {

	if ( ! empty($query['search'])) {
		$domain_ids = wu_get_domains(
			[
				'number' => -1,
				'search' => '*' . $query['search'] . '*',
				'fields' => ['blog_id'],
			]
		);

		$domain_ids = array_column($domain_ids, 'blog_id');

		if ( ! empty($domain_ids)) {
			$query['blog_id__in'] = $domain_ids;

			unset($query['search']);
		}
	}

	return \WP_Ultimo\Models\Site::query($query);
}

/**
 * Returns the list of Site Templates.
 *
 * @since 2.0.0
 *
 * @param array $query Query arguments.
 * @return array
 */
function wu_get_site_templates($query = []) {

	$query = wp_parse_args(
		$query,
		[
			'number' => 9999, // By default, we try to get ALL available templates.
		]
	);

	return \WP_Ultimo\Models\Site::get_all_by_type('site_template', $query);
}

/**
 * Parses a URL and breaks it into different parts
 *
 * @since 2.0.0
 *
 * @param string $domain The domain to break up.
 * @return object
 */
function wu_handle_site_domain($domain) {

	global $current_site;

	if (! str_contains($domain, 'http')) {
		$domain = "https://{$domain}";
	}

	$parsed = wp_parse_url($domain);

	return (object) $parsed;
}

/**
 * Creates a new site.
 *
 * @since 2.0.0
 *
 * @param array $site_data Site data.
 * @return \WP_Error|\WP_Ultimo\Models\Site
 */
function wu_create_site($site_data) {

	$current_site = get_current_site();

	$site_data = wp_parse_args(
		$site_data,
		[
			'domain'                => $current_site->domain,
			'path'                  => '/',
			'title'                 => false,
			'type'                  => false,
			'template_id'           => false,
			'featured_image_id'     => 0,
			'duplication_arguments' => false,
			'public'                => true,
		]
	);

	$site = new \WP_Ultimo\Models\Site($site_data);

	$site->set_public($site_data['public']);

	$saved = $site->save();

	return is_wp_error($saved) ? $saved : $site;
}

/**
 * Returns the correct domain/path combination when creating a new site.
 *
 * @since 2.0.0
 *
 * @param string      $path_or_subdomain The site path.
 * @param string|bool $base_domain The domain selected.
 * @return object Object with a domain and path properties.
 */
function wu_get_site_domain_and_path($path_or_subdomain = '/', $base_domain = false) {

	global $current_site;

	$path_or_subdomain = trim($path_or_subdomain, '/');

	$domain = $base_domain ?: $current_site->domain;

	$d = new \stdClass();

	if (is_multisite() && is_subdomain_install()) {
		/*
		 * Treat for the www. case.
		 */
		$domain = str_replace('www.', '', (string) $domain);

		$d->domain = "{$path_or_subdomain}.{$domain}";

		$d->path = '/';

		return $d;
	}

	$d->domain = $domain;

	$d->path = "/{$path_or_subdomain}";

	/**
	 * Allow developers to manipulate the domain/path pairs.
	 *
	 * This can be useful for a number of things, such as implementing some
	 * sort of staging solution, different servers, etc.
	 *
	 * @since 2.0.0
	 * @param object $d The current object containing a domain and path keys.
	 * @param string $path_or_subdomain The original path/subdomain passed to the function.
	 * @return object An object containing a domain and path keys.
	 */
	return apply_filters('wu_get_site_domain_and_path', $d, $path_or_subdomain);
}

/**
 * Returns all the meta data keys present on a site.
 *
 * @since 2.0.11
 *
 * @param int     $site_id The site id.
 * @param boolean $include_unset If we should include fields that exist but are not set
 *                               for this particular site.
 * @return array
 */
function wu_get_all_site_meta($site_id, $include_unset = true) {

	$all_meta = [];

	$site = wu_get_site($site_id);

	if ( ! $site) {
		return $all_meta;
	}

	$meta_keys = $site->get_meta('wu_custom_meta_keys', []);

	if ($meta_keys) {
		foreach ($meta_keys as $key => $meta_data) {
			$meta_data['exists'] = true;
			$all_meta[$key] = $meta_data;
		}
	}

	return $all_meta;
}

/**
 * Returns a site meta.
 *
 * @since 2.0.11
 *
 * @param int    $site_id  The local (wu) site id.
 * @param string $meta_key     The key to use on meta value.
 * @param bool   $default      The default value to be passed.
 * @param bool   $single       To return single values or not.
 * @return mixed
 */
function wu_get_site_meta($site_id, $meta_key, $default = false, $single = true) {

	$site = wu_get_site($site_id);

	if ( ! $site) {
		return $default;
	}

	return $site->get_meta($meta_key, $default, $single);
}

/**
 * Updates a site meta.
 *
 * @since 2.0.11
 *
 * @param int    $site_id  The local (wu) site id.
 * @param string $key          The key to use on meta value.
 * @param mixed  $value        The new meta value.
 * @param string $type         The data type.
 * @param string $title        The data title.
 * @return int|bool  The new meta field ID if a field with the given
 *                   key didn't exist and was therefore added, true on
 *                   successful update, false if site did not exist
 *                   or on failure or if the value passed to the function
 *                   is the same as the one that is already in the database.
 */
function wu_update_site_meta($site_id, $key, $value, $type = null, $title = null, $form_slug = null, $step_slug = null, $description = null, $tooltip = null, $options = []) {

	$site = wu_get_site($site_id);

	if ( ! $site) {
		return false;
	}

	if ($type) {
		$custom_keys = $site->get_meta('wu_custom_meta_keys', []);

		$meta_info = [
			'type'  => $type,
			'title' => $title,
		];

		// Store form reference if provided
		if ($form_slug) {
			$meta_info['form'] = $form_slug;
		}

		// Store additional info if provided
		if ($step_slug) {
			$meta_info['step'] = $step_slug;
		}

		if ($description) {
			$meta_info['description'] = $description;
		}

		if ($tooltip) {
			$meta_info['tooltip'] = $tooltip;
		}

		if (!empty($options)) {
			$meta_info['options'] = $options;
		}

		$custom_keys = array_merge(
			$custom_keys,
			[
				$key => $meta_info,
			]
		);

		$site->update_meta('wu_custom_meta_keys', $custom_keys);
	}

	return $site->update_meta($key, $value);
}

/**
 * Deletes a site meta with a custom type field.
 *
 * @since 2.0.11
 *
 * @param int    $site_id  The local (wu) site id.
 * @param string $meta_key     The key to use on meta value.
 * @return bool
 */
function wu_delete_site_meta($site_id, $meta_key) {

	$site = wu_get_site($site_id);

	if ( ! $site) {
		return false;
	}

	$custom_keys = $site->get_meta('wu_custom_meta_keys', []);

	if (isset($custom_keys[ $meta_key ])) {
		unset($custom_keys[ $meta_key ]);

		$site->update_meta('wu_custom_meta_keys', $custom_keys);
	}

	return (bool) $site->delete_meta($meta_key);
}

/**
 * Add site meta section to site options.
 *
 * @param array $sections Existing sections.
 * @param object $site Site object.
 * @return array Modified sections.
 */
function wu_add_site_meta_options_section($sections, $site) {

	$custom_meta_keys = wu_get_all_site_meta($site->get_id(), true);

	$final_fields = [];

	// Add existing custom fields
	foreach ($custom_meta_keys as $key => $value) {
		$form = wu_get_isset($value, 'form');

		if ($form) {
			$field_location_breadcrumbs = [
				$form,
				wu_get_isset($value, 'step'),
				wu_get_isset($value, 'id'),
			];
		} else {
			$field_location_breadcrumbs = [
				__('Custom field', 'multisite-ultimate'),
			];
		}

		$location = sprintf(
			'<small><strong>%s</strong> %s</small>',
			__('Location:', 'multisite-ultimate'),
			implode(' &rarr; ', array_filter($field_location_breadcrumbs))
		);

		// Prepare delete button for custom fields (fields without a form)
		$delete_button = '';
		if (!$form) {
			$delete_url = wu_network_admin_url('wp-ultimo-edit-site', [
				'id' => $site->get_id(),
				'delete_meta_key' => $key,
				'_wpnonce' => wp_create_nonce('delete_meta_' . $key)
			]);
			
			$delete_button = sprintf(
				'<span style="float: right;"> <a href="%s" class="wu-text-red-600 wu-no-underline" onclick="return confirm(\'%s\')" title="%s">%s</a></span>',
				esc_url($delete_url),
				esc_attr(__('Are you sure you want to delete this custom field?', 'multisite-ultimate')),
				esc_attr(__('Delete Field', 'multisite-ultimate')),
				__('Delete', 'multisite-ultimate')
			);
		}

		$field_data = [
			'title'             => wu_get_isset($value, 'title', wu_slug_to_name($key)),
			'type'              => wu_get_isset($value, 'type', 'text'),
			'desc'              => wu_get_isset($value, 'description', '') . $location . $delete_button,
			'tooltip'           => wu_get_isset($value, 'tooltip', ''),
			'value'             => wu_get_site_meta($site->get_id(), $key),
		];

		if ('image' === $field_data['type']) {
			$image_attributes  = wp_get_attachment_image_src((int) $field_data['value'], 'full');
			$field_data['img'] = $image_attributes ? $image_attributes[0] : '';
		}

		$final_fields["meta_key_$key"] = $field_data;
	}

	// Always show message if no fields exist yet
	if (empty($final_fields)) {
		$final_fields['empty'] = [
			'type'    => 'note',
			'desc'    => __('No custom meta fields found. You can add new ones below.', 'multisite-ultimate'),
			'classes' => 'wu-text-center',
		];
	}

	$final_fields['display_new_meta_repeater'] = [
		'title'           => __('Manually add custom meta fields', 'multisite-ultimate'),
		'desc'            => __('Add new custom meta fields to this site.', 'multisite-ultimate'),
		'type'            => 'toggle',
		'wrapper_classes' => 'wu-bg-gray-100',
		'html_attr'       => [
			'v-model' => 'new_meta_fields_show',
		],
	];

	$default_meta_value = fn(string $type, $value = '', bool $is_default = false) => [
		'title'             => __('Value', 'multisite-ultimate'),
		'type'              => $type,
		'value'             => $value,
		'wrapper_classes'   => 'wu-w-1/4 wu-ml-2',
		'wrapper_html_attr' => [
			'v-show' => ($is_default ? '!new_meta_field.type || ' : '') . "new_meta_field.type === '$type'",
		],
		'html_attr'         => [
			'v-model'     => 'new_meta_field.value',
			'v-bind:name' => '"new_meta_fields[" + index + "][value]"',
		],
	];

	$new_meta_fields = [
		'new_meta_fields' => [
			'type'              => 'group',
			'wrapper_classes'   => 'wu-relative',
			'wrapper_html_attr' => [
				'v-for' => '(new_meta_field, index) in new_meta_fields',
			],
			'fields'            => [
				'new_meta_remove'         => [
					'type'            => 'note',
					'desc'            => sprintf(
						'<a title="%s" class="wu-no-underline wu-inline-block wu-text-gray-600" href="#" @click.prevent="() => new_meta_fields.splice(index, 1)"><span class="dashicons-wu-squared-cross"></span></a>',
						__('Remove', 'multisite-ultimate')
					),
					'wrapper_classes' => 'wu-absolute wu-top-0 wu-right-0',
				],
				'new_meta_slug'           => [
					'title'           => __('Slug', 'multisite-ultimate'),
					'type'            => 'text',
					'value'           => '',
					'wrapper_classes' => 'wu-w-1/4',
					'html_attr'       => [
						'v-on:input'  => "new_meta_field.slug = \$event.target.value.toLowerCase().replace(/[^a-z0-9-_]+/g, '')",
						'v-model'     => 'new_meta_field.slug',
						'v-bind:name' => '"new_meta_fields[" + index + "][slug]"',
					],
				],
				'new_meta_title'          => [
					'title'           => __('Title', 'multisite-ultimate'),
					'type'            => 'text',
					'value'           => '',
					'wrapper_classes' => 'wu-w-1/4 wu-ml-2',
					'html_attr'       => [
						'v-bind:name' => '"new_meta_fields[" + index + "][title]"',
					],
				],
				'new_meta_type'           => [
					'title'           => __('Type', 'multisite-ultimate'),
					'type'            => 'select',
					'options'         => [
						'text'     => __('Text', 'multisite-ultimate'),
						'textarea' => __('Textarea', 'multisite-ultimate'),
						'checkbox' => __('Checkbox', 'multisite-ultimate'),
						'color'    => __('Color', 'multisite-ultimate'),
						'image'    => __('Image', 'multisite-ultimate'),
					],
					'wrapper_classes' => 'wu-w-1/4 wu-ml-2',
					'html_attr'       => [
						'v-model'     => 'new_meta_field.type',
						'v-bind:name' => '"new_meta_fields[" + index + "][type]"',
					],
				],
				'new_meta_value_text'     => $default_meta_value('text', '', true),
				'new_meta_value_textarea' => $default_meta_value('textarea'),
				'new_meta_value_checkbox' => $default_meta_value('checkbox', true),
				'new_meta_value_color'    => $default_meta_value('color', '#4299e1'),
				'new_meta_value_image'    => array_merge(
					$default_meta_value('image'),
					[
						'content_wrapper_classes' => 'wu-mt-2',
						'stacked'                 => true,
					]
				),
			],
		],
		'repeat_option'   => [
			'type'            => 'submit',
			'title'           => __('+ Add meta field', 'multisite-ultimate'),
			'classes'         => 'button wu-self-end',
			'wrapper_classes' => 'wu-bg-whiten wu-items-end',
			'html_attr'       => [
				'v-on:click.prevent' => '() => new_meta_fields.push({
					type: "text",
					slug: "",
				})',
			],
		],
	];

	$final_fields['new_meta_fields_wrapper'] = [
		'type'              => 'group',
		'classes'           => 'wu-grid',
		'wrapper_html_attr' => [
			'v-show' => 'new_meta_fields_show',
		],
		'fields'            => $new_meta_fields,
	];

	$sections['site_meta'] = [
		'title'  => __('Site Meta', 'multisite-ultimate'),
		'desc'   => __('Custom data associated with this site.', 'multisite-ultimate'),
		'icon'   => 'dashicons-wu-database wu-pt-px',
		'fields' => $final_fields,
		'state'  => [
			'display_unset_fields' => false,
			'new_meta_fields_show' => false,
			'new_meta_fields'      => [
				[
					'type' => 'text',
					'slug' => '',
				],
			],
		],
	];

	return $sections;
}

// Hook into site options sections - Priority 12 to place after Reset Limitations but before Notes
add_filter('wu_site_options_sections', 'wu_add_site_meta_options_section', 12, 2);
