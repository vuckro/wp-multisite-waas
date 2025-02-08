<?php
/**
 * Event schema class
 *
 * @package WP_Ultimo
 * @subpackage Database\Events
 * @since 2.0.0
 */

namespace WP_Ultimo\Database\Events;

use WP_Ultimo\Database\Engine\Schema;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Events Schema Class.
 *
 * @since 2.0.0
 */
class Events_Schema extends Schema {

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
			'name'     => 'severity',
			'type'     => 'tinyint',
			'length'   => '1',
			'unsigned' => true,
			'sortable' => true,
		],

		[
			'name'    => 'initiator',
			'type'    => 'enum(\'system\', \'manual\')',
			'default' => 'none',
		],

		[
			'name'       => 'author_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'sortable'   => true,
			'transition' => true,
		],

		[
			'name'       => 'object_type',
			'type'       => 'varchar',
			'length'     => 20,
			'default'    => 'network',
			'sortable'   => true,
			'searchable' => true,
		],

		[
			'name'       => 'object_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'sortable'   => true,
			'transition' => true,
		],

		[
			'name'    => 'slug',
			'type'    => 'longtext',
			'default' => '',
		],

		[
			'name'    => 'payload',
			'type'    => 'longtext',
			'default' => '',
		],

		[
			'name'       => 'date_created',
			'type'       => 'datetime',
			'default'    => null,
			'created'    => true,
			'date_query' => true,
			'sortable'   => true,
			'allow_null' => true,
		],

	];
}
