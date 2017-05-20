<?php
/**
 * @package SMF WhoDownloadedAttachment
 * @file Mod-WhoDownloadedAttachment.php
 * @author digger <digger@mysmf.ru> <http://mysmf.ru>
 * @copyright Copyright (c) 2017, digger
 * @license The MIT License (MIT) https://opensource.org/licenses/MIT
 * @version 1.0
 */

/**
 * Load all needed hooks
 */
function loadWhoDownloadedAttachmentHooks()
{
    if (defined('WIRELESS')) {
        return;
    }

    add_integration_function('integrate_actions', 'addWhoDownloadedAttachmentAction', false);
    add_integration_function('integrate_load_theme', 'loadWhoDownloadedAttachmentAssets', false);
    add_integration_function('integrate_load_permissions', 'addWhoDownloadedAttachmentPermissions', false);
    add_integration_function('integrate_menu_buttons', 'addWhoDownloadedAttachmentCopyright', false);

    // Custom hooks
    add_integration_function('integrate_attachment_download', 'logWhoDownloadedAttachment', false);
    add_integration_function('integrate_attachment_download_list', 'addWhoDownloadedAttachmentLink', false);
}

/**
 * Add mod action
 * @param array $actionArray
 */
function addWhoDownloadedAttachmentAction(&$actionArray = array())
{
    $actionArray['get_downloaders_list'] = array('Mod-WhoDownloadedAttachment.php', 'getWhoDownloadedAttachmentList');
}

/**
 * Add mod permissions
 * @param $permissionGroups
 * @param $permissionList
 * @param $leftPermissionGroups
 * @param $hiddenPermissions
 * @param $relabelPermissions
 */
function addWhoDownloadedAttachmentPermissions(
    &$permissionGroups,
    &$permissionList,
    &$leftPermissionGroups,
    &$hiddenPermissions,
    &$relabelPermissions
) {
    loadLanguage('WhoDownloadedAttachment/WhoDownloadedAttachment');
    $permissionList['membergroup']['show_download_list'] = array(false, 'member_admin', 'moderate_general');
}

/**
 * Log member who download this attachment
 * @param int $id_attach
 * @param int $attachment_type
 */
function logWhoDownloadedAttachment($id_attach = 0, $attachment_type = 0)
{
    global $smcFunc, $user_info;

    if ($id_attach == 0 || $attachment_type != 0 || isset($_REQUEST['image']) || $user_info['id'] == 0) {
        return;
    }

    $smcFunc['db_insert']('replace',
        '{db_prefix}log_downloads',
        array(
            'id_attach' => 'int',
            'id_member' => 'int',
            'log_time' => 'int',
            'ip' => 'string-16',
        ),
        array($id_attach, $user_info['id'], time(), $user_info['ip']),
        array()
    );
}

/**
 * Add link to show who downloaded this attachment
 * @param $attachment array of attachment vars
 */
function addWhoDownloadedAttachmentLink(&$attachment)
{
    global $txt;

    if (!allowedTo('show_download_list') || empty($attachment['id']) || $attachment['is_image']) {
        return;
    }

    loadLanguage('WhoDownloadedAttachment/WhoDownloadedAttachment');
    echo ' [<a href="javascript:void(0)" onclick="showWhoDownloadedAttachmentList(' . $attachment['id'] . ')">' . $txt['attachment_download_list'] . '</a>]<span id="download_list_' . $attachment['id'] . '"></span><br />';
}

/**
 * Load mod assets
 */
function loadWhoDownloadedAttachmentAssets()
{
    global $context, $settings;

    $context['insert_after_template'] .= '
		<script type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/WhoDownloadedAttachment.js"></script>';

    $context['html_headers'] .= '
	<link rel="stylesheet" type="text/css" href="' . $settings['default_theme_url'] . '/css/WhoDownloadedAttachment.css" />';
}


/**
 * Get XML document with members list
 */
function getWhoDownloadedAttachmentList()
{
    global $smcFunc, $scripturl, $context, $txt;

    if (empty($_GET['attachment']) || !isset($_GET['xml'])) {
        die;
    }

    $request = $smcFunc['db_query']('', '
                              SELECT d.id_member, d.log_time, d.ip,
                              m.real_name
                              FROM {db_prefix}log_downloads d
                              LEFT JOIN {db_prefix}members m ON m.id_member = d.id_member
                              WHERE id_attach = {int:id_attach}
                              LIMIT 1000',
        array(
            'id_attach' => (int)$_GET['attachment'],
        )
    );

    if ($smcFunc['db_num_rows']($request) == 0) {
        loadLanguage('WhoDownloadedAttachment/WhoDownloadedAttachment');
        $download_list = '<br />' . $txt['attachment_download_list_empty'];
    } else {

        $download_list = '<table class="download_list_table">';
        while ($row = $smcFunc['db_fetch_assoc']($request)) {
            $download_list .= '<tr><td><a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a></td><td>' . timeformat($row['log_time']) . '</td><td>' . $row['ip'] . '</td></tr>';
        }
        $download_list .= '</table>';
        $smcFunc['db_free_result']($request);
    }

    loadTemplate('WhoDownloadedAttachment');
    $context['sub_template'] = 'download_list';
    $context['download_list']['xml'] = $download_list;
}

/**
 * Add mod copyright to the forum credit's page
 */
function addWhoDownloadedAttachmentCopyright()
{
    global $context;

    if ($context['current_action'] == 'credits') {
        $context['copyrights']['mods'][] = '<a href="http://mysmf.net/mods/who-downloaded-attachment" target="_blank">Who Downloaded Attachment</a> &copy; 2017, digger';
    }
}
