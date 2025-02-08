<?php
/**
 * Site schema class
 *
 * @package WP_Ultimo
 * @subpackage Database\Sites
 * @since 2.0.0
 */

namespace WP_Ultimo\Database\Sites;

use WP_Ultimo\Database\Engine\Schema;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Sites Schema Class.
 *
 * @since 2.0.0
 */
class Sites_Schema extends Schema {

	/**
	 * Table prefix, including the site prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $prefix = '';

	/**
	 * Array of database column objects
	 *
	 * @since  2.0.0
	 * @access public
	 * @var array
	 */
	public $columns = [

		[
			'name'       => 'blog_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'extra'      => 'auto_increment',
			'primary'    => true,
			'sortable'   => true,
			'aliases'    => ['id', 'ID'],
			'searchable' => true,
		],

		[
			'name'     => 'site_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'sortable' => true,
		],

		[
			'name'       => 'domain',
			'type'       => 'varchar',
			'searchable' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'path',
			'type'       => 'varchar',
			'searchable' => true,
			'sortable'   => true,
		],

		[
			'name'       => 'registered',
			'type'       => 'datetime',
			'default'    => '0000-00-00 00:00:00',
			'modified'   => true,
			'date_query' => true,
			'sortable'   => true,
			'allow_null' => true,
		],

		[
			'name'       => 'last_updated',
			'type'       => 'datetime',
			'default'    => '0000-00-00 00:00:00',
			'modified'   => true,
			'date_query' => true,
			'sortable'   => true,
			'allow_null' => true,
		],

		[
			'name'     => 'public',
			'type'     => 'tinyint',
			'length'   => '2',
			'unsigned' => true,
			'default'  => 1,
			'sortable' => true,
		],

		[
			'name'     => 'archived',
			'type'     => 'tinyint',
			'length'   => '2',
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true,
		],

		[
			'name'     => 'mature',
			'type'     => 'tinyint',
			'length'   => '2',
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true,
		],

		[
			'name'     => 'spam',
			'type'     => 'tinyint',
			'length'   => '2',
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true,
		],

		[
			'name'     => 'deleted',
			'type'     => 'tinyint',
			'length'   => '2',
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true,
		],

		[
			'name'     => 'lang_id',
			'type'     => 'int',
			'length'   => '11',
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true,
		],

	];
}
