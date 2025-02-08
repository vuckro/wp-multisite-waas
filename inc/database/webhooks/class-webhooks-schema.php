<?php
/**
 * Webhook schema class
 *
 * @package WP_Ultimo
 * @subpackage Database\Webhooks
 * @since 2.0.0
 */

namespace WP_Ultimo\Database\Webhooks;

use WP_Ultimo\Database\Engine\Schema;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Webhooks Schema Class.
 *
 * @since 2.0.0
 */
class Webhooks_Schema extends Schema {

	/**
	 * Array of database column objects
	 *
	 * @since  2.0.0
	 * @access public
	 * @var array
	 */
	public $columns = [

		[
			'name'     => 'id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'extra'    => 'auto_increment',
			'primary'  => true,
			'sortable' => true,
		],

		[
			'name'       => 'migrated_from_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'sortable'   => true,
			'allow_null' => true,
		],

		[
			'name'       => 'name',
			'type'       => 'varchar',
			'searchable' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'webhook_url',
			'type'       => 'varchar',
			'searchable' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'event',
			'type'       => 'varchar',
			'searchable' => true,
			'sortable'   => true,
		],

		[
			'name'     => 'event_count',
			'type'     => 'int',
			'length'   => '10',
			'default'  => 0,
			'sortable' => true,
			'aliases'  => ['sent_events_count'],
		],

		[
			'name'       => 'active',
			'type'       => 'tinyint',
			'length'     => '4',
			'unsigned'   => true,
			'default'    => 1,
			'transition' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'hidden',
			'type'       => 'tinyint',
			'length'     => '4',
			'unsigned'   => true,
			'default'    => 0,
			'transition' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'integration',
			'type'       => 'varchar',
			'searchable' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'date_last_failed',
			'type'       => 'datetime',
			'date_query' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'date_created',
			'type'       => 'datetime',
			'created'    => true,
			'date_query' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'date_modified',
			'type'       => 'datetime',
			'default'    => null,
			'modified'   => true,
			'date_query' => true,
			'sortable'   => true,
			'allow_null' => true,
		],

	];
}
