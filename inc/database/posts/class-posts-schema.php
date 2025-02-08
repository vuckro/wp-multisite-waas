<?php
/**
 * Post schema class
 *
 * @package WP_Ultimo
 * @subpackage Database\Posts
 * @since 2.0.0
 */

namespace WP_Ultimo\Database\Posts;

use WP_Ultimo\Database\Engine\Schema;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Posts Schema Class.
 *
 * @since 2.0.0
 */
class Posts_Schema extends Schema {

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
			'name'     => 'author_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
		],

		[
			'name'       => 'type',
			'type'       => 'varchar',
			'searchable' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'slug',
			'type'       => 'varchar',
			'searchable' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'title',
			'type'       => 'varchar',
			'searchable' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'content',
			'type'       => 'longtext',
			'default'    => '',
			'searchable' => true,
		],

		[
			'name'       => 'excerpt',
			'type'       => 'longtext',
			'default'    => '',
			'searchable' => true,
		],

		[
			'name'       => 'list_order',
			'type'       => 'tinyint',
			'length'     => '4',
			'unsigned'   => true,
			'default'    => 10,
			'transition' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'status',
			'type'       => 'varchar', // An "enum" here would possibly limit custom post status.
			'default'    => 'draft',
			'transition' => true,
			'sortable'   => true,
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
