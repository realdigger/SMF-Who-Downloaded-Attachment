<?php
/**
 * @package SMF WhoDownloadedAttachment
 * @file database.php
 * @author digger
 * @copyright Copyright (c) 2017-2026, digger
 * @license The MIT License (MIT) https://opensource.org/licenses/MIT
 * @version 1.1.16
 */

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF'))
	die('Error: Cannot install - please verify that you put this file in the same place as SMF\'s index.php and SSI.php files.');

if (SMF == 'SSI' && !$user_info['is_admin'])
	die('Admin privileges required.');

global $smcFunc, $modSettings, $boardurl;

db_extend('packages');

$columns = array(
	array(
		'name' => 'id_attach',
		'type' => 'int',
		'size' => 10,
		'unsigned' => true,
		'default' => 0,
		'null' => false,
	),
	array(
		'name' => 'id_member',
		'type' => 'mediumint',
		'size' => 8,
		'unsigned' => true,
		'default' => 0,
		'null' => false,
	),
	array(
		'name' => 'log_time',
		'type' => 'int',
		'size' => 10,
		'unsigned' => true,
		'default' => 0,
		'null' => false,
	),
	array(
		'name' => 'ip',
		'type' => 'varchar',
		'size' => 45,
		'default' => '',
		'null' => false,
	),
);

$indexes = array(
	array(
		'name' => 'member_to_attach',
		'type' => 'primary',
		'columns' => array('id_attach', 'id_member'),
	),
	array(
		'name' => 'attach_log_time',
		'type' => 'index',
		'columns' => array('id_attach', 'log_time'),
	),
);

$smcFunc['db_create_table']('{db_prefix}log_downloads', $columns, $indexes, array(), 'update');
$smcFunc['db_add_index']('{db_prefix}log_downloads', $indexes[1], array(), 'ignore');

if (function_exists('updateSettings'))
{
	$who_downloaded_settings = array();

	if (!isset($modSettings['who_downloaded_cache_time']))
		$who_downloaded_settings['who_downloaded_cache_time'] = 60;
	if (!isset($modSettings['who_downloaded_max_days']))
		$who_downloaded_settings['who_downloaded_max_days'] = 0;
	if (!isset($modSettings['who_downloaded_max_rows']))
		$who_downloaded_settings['who_downloaded_max_rows'] = 1000;
	if (!isset($modSettings['who_downloaded_ip_admin_only']))
		$who_downloaded_settings['who_downloaded_ip_admin_only'] = 0;

	if (!empty($who_downloaded_settings))
		updateSettings($who_downloaded_settings);
}

if (SMF == 'SSI')
	echo 'Database changes are complete! <a href="' . $boardurl . '">Return to the main page</a>.';
