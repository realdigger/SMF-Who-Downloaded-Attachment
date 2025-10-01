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

    $hooks = [
        'integrate_actions'               => 'addWhoDownloadedAttachmentAction',
        'integrate_load_theme'            => 'loadWhoDownloadedAttachmentAssets',
        'integrate_load_permissions'      => 'addWhoDownloadedAttachmentPermissions',
        'integrate_menu_buttons'          => 'addWhoDownloadedAttachmentCopyright',
        'integrate_modify_modifications'  => 'addWhoDownloadedAttachmentSettings',
    // Custom hooks
        'integrate_attachment_download'   => 'logWhoDownloadedAttachment',
        'integrate_attachment_download_list' => 'addWhoDownloadedAttachmentLink',
    ];

    foreach ($hooks as $hook => $callback) {
        add_integration_function($hook, $callback, false);
    }
}

/**
 * Settings page for WhoDownloadedAttachment mod
 */
function WhoDownloadedAttachmentSettings($return_config = false)
{
    global $txt, $context, $scripturl;

    loadLanguage('WhoDownloaded/WhoDownloaded');

    $config_vars = array(
        array('int', 'who_downloaded_cache_time', 'subtext' => $txt['who_downloaded_cache_time_desc']),
        array('int', 'who_downloaded_max_days', 'subtext' => $txt['who_downloaded_max_days_desc']),
    );


    if ($return_config)
        return $config_vars;

    // Заголовок страницы
    $context['page_title'] = $txt['who_downloaded_settings_title'];
    $context['settings_title'] = $txt['who_downloaded_settings_title'];

    // Сохраняем изменения
    if (isset($_GET['save'])) {
        checkSession();
        saveDBSettings($config_vars);
        redirectexit('action=admin;area=modsettings;sa=who_downloaded');
    }

    // Загружаем форму настроек
    prepareDBSettingContext($config_vars);
}

/**
 * Add WhoDownloadedAttachment settings to admin panel
 */
function addWhoDownloadedAttachmentSettings(&$subActions)
{
    $subActions['who_downloaded'] = [
        'file'      => 'Mod-WhoDownloadedAttachment.php',
        'function'  => 'WhoDownloadedAttachmentSettings',
    ];
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
    loadLanguage('WhoDownloaded/WhoDownloaded');
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

    if (!allowedTo('show_download_list') || $attachment['is_image']) {
        echo '<br />';
        return;
    }

    loadLanguage('WhoDownloaded/WhoDownloaded');
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
 * Get XML document with members list (cached and date filter)
 */
function getWhoDownloadedAttachmentList()
{
    global $smcFunc, $scripturl, $context, $txt, $modSettings;

    if (empty($_GET['attachment']) || empty($_GET['xml']) || !allowedTo('show_download_list')) {
        die;
    }

    $id_attach = (int)$_GET['attachment'];

    // Настройки кеша и фильтра
    $ttl = !empty($modSettings['who_downloaded_cache_time']) ? (int)$modSettings['who_downloaded_cache_time'] : 60;
    $max_days = !empty($modSettings['who_downloaded_max_days']) ? (int)$modSettings['who_downloaded_max_days'] : 0;

    // Ключ кеша зависит от attachment и фильтра по дням
    $cache_key = 'who_downloaded_' . $id_attach . '_' . $max_days;
  
    // Попробуем взять из кеша
    if (!empty($modSettings['cache_enable']) && $ttl > 0) {
        $download_list = cache_get_data($cache_key, $ttl);
        if ($download_list !== null) {
            loadTemplate('WhoDownloadedAttachment');
            $context['sub_template'] = 'download_list';
            $context['download_list']['xml'] = $download_list;
            return;
        }
    }

    // Формируем условие фильтра по дате
    $where_clause = '';
    $params = array('id_attach' => $id_attach);

    if ($max_days > 0) {
        $since_time = time() - ($max_days * 86400); // 86400 секунд в дне
        $where_clause = ' AND d.log_time >= {int:since_time}';
        $params['since_time'] = $since_time;
    }

    // SQL-запрос
    $request = $smcFunc['db_query']('', '
        SELECT d.id_member, d.log_time, d.ip, m.real_name
        FROM {db_prefix}log_downloads d
        LEFT JOIN {db_prefix}members m ON m.id_member = d.id_member
        WHERE id_attach = {int:id_attach}' . $where_clause . '
        LIMIT 1000',
                                    $params
    );

    // Формируем вывод
    if ($smcFunc['db_num_rows']($request) == 0) {
        loadLanguage('WhoDownloaded/WhoDownloaded');
        $download_list = '<br />' . $txt['attachment_download_list_empty'];
    } else {
        $download_list = '<table class="download_list_table">';
        while ($row = $smcFunc['db_fetch_assoc']($request)) {
            $download_list .= sprintf(
                '<tr><td><a href="%s?action=profile;u=%d">%s</a></td><td>%s</td><td>%s</td></tr>',
                $scripturl,
                $row['id_member'],
                htmlspecialchars($row['real_name'], ENT_QUOTES, 'UTF-8'),
                timeformat($row['log_time']),
                $row['ip']
            );
        }
        $download_list .= '</table>';
        $smcFunc['db_free_result']($request);
    }

    // Сохраняем в кеш
    if (!empty($modSettings['cache_enable']) && $ttl > 0) {
        cache_put_data($cache_key, $download_list, $ttl);
    }

    // Передаем в контекст шаблона
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
        $context['copyrights']['mods'][] = '<a href="https://mysmf.net/mods/who-downloaded-attachment" target="_blank">Who Downloaded Attachment</a> &copy; 2017-2021, digger';
    }
}
