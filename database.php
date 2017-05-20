<?php
/**
 * @package SMF WhoDownloadedAttachment
 * @file database.php
 * @author digger <digger@mysmf.ru> <http://mysmf.ru>
 * @copyright Copyright (c) 2017, digger
 * @license The MIT License (MIT) https://opensource.org/licenses/MIT
 * @version 1.0
 */

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF')) {
    require_once(dirname(__FILE__) . '/SSI.php');
} elseif (!defined('SMF')) {
    die('<b>Error:</b> Cannot install - please verify that you put this file in the same place as SMF\'s index.php and SSI.php files.');
}

if ((SMF == 'SSI') && !$user_info['is_admin']) {
    die('Admin privileges required.');
}

global $smcFunc;
db_extend('packages');

$columns = array(
    array(
        'name' => 'id_attach',
        'type' => 'int',
        'size' => 10,
        'auto' => true,
        'unsigned' => true,
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
        'size' => 16,
        'default' => '',
        'null' => false,
    ),
);

$indexes = array(
    array(
        'name' => 'member_to_attach',
        'type' => 'primary',
        'columns' => array('id_attach', 'id_member'),
    )
);

$tblname = '{db_prefix}log_downloads';
$smcFunc['db_create_table']($tblname, $columns, $indexes, array(), 'update');

if (SMF == 'SSI') {
    echo 'Database changes are complete! <a href="/">Return to the main page</a>.';
}
