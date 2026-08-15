<?php
/**
 * @package SMF WhoDownloadedAttachment
 * @file Mod-WhoDownloadedAttachment.php
 * @author digger
 * @copyright Copyright (c) 2017-2026, digger
 * @license The MIT License (MIT) https://opensource.org/licenses/MIT
 * @version 1.1.4
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Load all needed hooks.
 */
function loadWhoDownloadedAttachmentHooks()
{
	if (defined('WIRELESS'))
		return;

	$hooks = array(
		'integrate_actions' => 'addWhoDownloadedAttachmentAction',
		'integrate_load_theme' => 'loadWhoDownloadedAttachmentAssets',
		'integrate_load_permissions' => 'addWhoDownloadedAttachmentPermissions',
		'integrate_menu_buttons' => 'addWhoDownloadedAttachmentCopyright',
		'integrate_modify_modifications' => 'addWhoDownloadedAttachmentSettings',
	);

	// SMF 2.1 has native hooks at the needed points.
	if (defined('SMF_VERSION') && version_compare(SMF_VERSION, '2.1', '>='))
	{
		$hooks['integrate_download_headers'] = 'logWhoDownloadedAttachment';
		$hooks['integrate_prepare_display_context'] = 'addWhoDownloadedAttachmentLinksToDisplayContext';
	}
	// SMF 2.0 branch is intentionally left on the original XML-added custom hooks.
	else
	{
		$hooks['integrate_attachment_download'] = 'logWhoDownloadedAttachment';
		$hooks['integrate_attachment_download_list'] = 'addWhoDownloadedAttachmentLink';
	}

	foreach ($hooks as $hook => $callback)
		add_integration_function($hook, $callback, false);
}

/**
 * Settings page for WhoDownloadedAttachment mod.
 *
 * @param bool $return_config
 * @return array|void
 */
function WhoDownloadedAttachmentSettings($return_config = false)
{
	global $txt, $context;

	loadLanguage('WhoDownloaded/WhoDownloaded');

	$config_vars = array(
		array('int', 'who_downloaded_cache_time', 'subtext' => $txt['who_downloaded_cache_time_desc']),
		array('int', 'who_downloaded_max_days', 'subtext' => $txt['who_downloaded_max_days_desc']),
		array('int', 'who_downloaded_max_rows', 'subtext' => $txt['who_downloaded_max_rows_desc']),
		array('check', 'who_downloaded_ip_admin_only', 'subtext' => $txt['who_downloaded_ip_admin_only_desc']),
	);

	if ($return_config)
		return $config_vars;

	$context['page_title'] = $txt['who_downloaded_settings_title'];
	$context['settings_title'] = $txt['who_downloaded_settings_title'];

	if (isset($_GET['save']))
	{
		checkSession();
		saveDBSettings($config_vars);
		redirectexit('action=admin;area=modsettings;sa=who_downloaded');
	}

	prepareDBSettingContext($config_vars);
}

/**
 * Add WhoDownloadedAttachment settings to admin panel.
 *
 * @param array $subActions
 */
function addWhoDownloadedAttachmentSettings(&$subActions)
{
	global $context, $txt;

	loadLanguage('WhoDownloaded/WhoDownloaded');

	// SMF 2.1 uses call_helper(), SMF 2.0 calls the function name directly.
	$subActions['who_downloaded'] = function_exists('call_helper') ? 'Mod-WhoDownloadedAttachment.php|WhoDownloadedAttachmentSettings' : 'WhoDownloadedAttachmentSettings';

	if (!empty($context['admin_menu_name']) && isset($context[$context['admin_menu_name']]['tab_data']['tabs']))
		$context[$context['admin_menu_name']]['tab_data']['tabs']['who_downloaded'] = array(
			'label' => $txt['who_downloaded_settings_title'],
		);
}

/**
 * Add mod action.
 *
 * @param array $actionArray
 */
function addWhoDownloadedAttachmentAction(&$actionArray = array())
{
	$actionArray['get_downloaders_list'] = array('Mod-WhoDownloadedAttachment.php', 'getWhoDownloadedAttachmentList');
}

/**
 * Add mod permissions.
 */
function addWhoDownloadedAttachmentPermissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	loadLanguage('WhoDownloaded/WhoDownloaded');
	$permissionList['membergroup']['show_download_list'] = array(false, 'member_admin', 'moderate_general');
}

/**
 * Log member who downloads this attachment.
 *
 * In SMF 2.0 this is called by the original XML-added custom hook with explicit arguments.
 * In SMF 2.1 it is called by integrate_download_headers without arguments, so the
 * attachment information is resolved from the current request.
 *
 * @param int $id_attach
 * @param int|null $attachment_type
 */
function logWhoDownloadedAttachment($id_attach = 0, $attachment_type = null)
{
	global $smcFunc, $user_info, $context, $modSettings;

	if (empty($id_attach))
		$id_attach = isset($_REQUEST['attach']) ? (int) $_REQUEST['attach'] : (isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0);

	if (empty($id_attach) || empty($user_info['id']) || isset($_REQUEST['image']))
		return;

	// SMF 2.1 native hook path. Mirror the core counter conditions as closely as
	// possible without modifying ShowAttachments.php.
	if ($attachment_type === null)
	{
		if (isset($_REQUEST['thumb']) || !empty($_REQUEST['preview']) || !empty($context['skip_downloads_increment']) || whoDownloadedAttachmentIsResumedDownload())
			return;

		$attachment_type = whoDownloadedAttachmentGetAttachmentType($id_attach);
	}

	if ($attachment_type != 0)
		return;

	$ip = !empty($user_info['ip']) ? $user_info['ip'] : (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');

	$smcFunc['db_insert']('replace', '{db_prefix}log_downloads', array(
		'id_attach' => 'int',
		'id_member' => 'int',
		'log_time' => 'int',
		'ip' => 'string-45',
	), array(
		(int) $id_attach,
		(int) $user_info['id'],
		time(),
		$ip,
	), array('id_attach', 'id_member'));

	if (!empty($modSettings['cache_enable']) && !empty($modSettings['who_downloaded_cache_time']))
		cache_put_data('who_downloaded_revision_' . $id_attach, md5(uniqid('', true)), (int) $modSettings['who_downloaded_cache_time']);
}

/**
 * Check whether this request is only resuming a partial download.
 *
 * @return bool
 */
function whoDownloadedAttachmentIsResumedDownload()
{
	if (empty($_SERVER['HTTP_RANGE']))
		return false;

	$range = $_SERVER['HTTP_RANGE'];
	if (strpos($range, '=') !== false)
		list (, $range) = explode('=', $range, 2);

	if (strpos($range, ',') !== false)
		list ($range) = explode(',', $range, 2);

	if (strpos($range, '-') !== false)
		list ($range) = explode('-', $range, 2);

	return (int) $range !== 0;
}

/**
 * Get attachment type by ID. Used by the SMF 2.1 native download hook.
 *
 * @param int $id_attach
 * @return int
 */
function whoDownloadedAttachmentGetAttachmentType($id_attach)
{
	global $smcFunc;

	static $types = array();

	$id_attach = (int) $id_attach;
	if (isset($types[$id_attach]))
		return $types[$id_attach];

	$request = $smcFunc['db_query']('', '
		SELECT attachment_type
		FROM {db_prefix}attachments
		WHERE id_attach = {int:id_attach}
		LIMIT 1',
		array(
			'id_attach' => $id_attach,
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
	{
		$smcFunc['db_free_result']($request);
		$types[$id_attach] = 1;
		return $types[$id_attach];
	}

	list ($types[$id_attach]) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return (int) $types[$id_attach];
}

/**
 * Add links to show who downloaded attachments in SMF 2.1 display context.
 *
 * @param array $output
 * @param array $message
 * @param int $counter
 */
function addWhoDownloadedAttachmentLinksToDisplayContext(&$output, &$message, $counter)
{
	if (empty($output['attachment']) || !allowedTo('show_download_list'))
		return;

	loadLanguage('WhoDownloaded/WhoDownloaded');

	foreach ($output['attachment'] as $key => $attachment)
	{
		if (empty($attachment['id']) || !empty($attachment['is_image']))
			continue;

		$output['attachment'][$key]['size'] .= ' ' . whoDownloadedAttachmentBuildLink((int) $attachment['id']);
	}
}

/**
 * Add link to show who downloaded this attachment. Used by the SMF 2.0 XML branch.
 *
 * @param array $attachment
 */
function addWhoDownloadedAttachmentLink(&$attachment)
{
	if (!empty($attachment['is_image']) || !allowedTo('show_download_list'))
	{
		echo '<br />';
		return;
	}

	loadLanguage('WhoDownloaded/WhoDownloaded');

	echo ' ', whoDownloadedAttachmentBuildLink((int) $attachment['id']), '<br />';
}

/**
 * Build the downloaders list link HTML.
 *
 * @param int $id_attach
 * @return string
 */
function whoDownloadedAttachmentBuildLink($id_attach)
{
	global $txt, $scripturl, $context;

	$id_attach = (int) $id_attach;
	$url = $scripturl . '?action=get_downloaders_list;attachment=' . $id_attach;
	$charset = !empty($context['character_set']) ? $context['character_set'] : 'UTF-8';
	$link_text = htmlspecialchars($txt['attachment_download_list'], ENT_QUOTES, $charset);

	return '[<a href="' . htmlspecialchars($url, ENT_QUOTES, $charset) . '" onclick="return showWhoDownloadedAttachmentList(' . $id_attach . ');">' . $link_text . '</a>]' .
		'<div id="download_list_' . $id_attach . '"></div>';
}

/**
 * Load mod assets.
 */
function loadWhoDownloadedAttachmentAssets()
{
	global $context, $settings, $scripturl, $topic;

	if (empty($topic) && (empty($context['current_action']) || $context['current_action'] != 'display'))
		return;

	if (!isset($context['insert_after_template']))
		$context['insert_after_template'] = '';
	if (!isset($context['html_headers']))
		$context['html_headers'] = '';

	$context['insert_after_template'] .= '<script><!-- // --><![CDATA[
		var smf_scripturl = ' . JavaScriptEscape($scripturl) . ';
	// ]]></script>';
	$context['html_headers'] .= '<script src="' . $settings['default_theme_url'] . '/scripts/WhoDownloadedAttachment.js"></script>
	<link rel="stylesheet" href="' . $settings['default_theme_url'] . '/css/WhoDownloadedAttachment.css">';
}

/**
 * Get XML document or regular page with members list.
 */
function getWhoDownloadedAttachmentList()
{
	global $context, $txt, $modSettings, $user_info;

	$is_xml = !empty($_GET['xml']);

	loadLanguage('WhoDownloaded/WhoDownloaded');

	if (empty($_GET['attachment']) || !allowedTo('show_download_list'))
		whoDownloadedAttachmentDenyAccess($is_xml);

	$id_attach = (int) $_GET['attachment'];

	if (!whoDownloadedAttachmentCanViewAttachment($id_attach))
		whoDownloadedAttachmentDenyAccess($is_xml);

	$ttl = !empty($modSettings['who_downloaded_cache_time']) ? (int) $modSettings['who_downloaded_cache_time'] : 60;
	$max_days = !empty($modSettings['who_downloaded_max_days']) ? (int) $modSettings['who_downloaded_max_days'] : 0;
	$max_rows = !empty($modSettings['who_downloaded_max_rows']) ? (int) $modSettings['who_downloaded_max_rows'] : 1000;
	$show_ip = empty($modSettings['who_downloaded_ip_admin_only']) || !empty($user_info['is_admin']);

	if ($max_rows <= 0)
		$max_rows = 1000;

	$cache_revision = whoDownloadedAttachmentGetCacheRevision($id_attach, $ttl);
	$cache_key = 'who_downloaded_' . $id_attach . '_' . $cache_revision . '_' . $max_days . '_' . $max_rows . '_' . ($show_ip ? 'ip1' : 'ip0');

	if (!empty($modSettings['cache_enable']) && $ttl > 0)
	{
		$download_list = cache_get_data($cache_key, $ttl);
		if ($download_list !== null)
		{
			whoDownloadedAttachmentRenderList($download_list, $is_xml);
			return;
		}
	}

	$download_list = whoDownloadedAttachmentGetDownloadListHtml($id_attach, $max_days, $max_rows, $show_ip);

	if (!empty($modSettings['cache_enable']) && $ttl > 0)
		cache_put_data($cache_key, $download_list, $ttl);

	whoDownloadedAttachmentRenderList($download_list, $is_xml);
}

/**
 * Get the cache revision for an attachment.
 *
 * @param int $id_attach
 * @param int $ttl
 * @return string
 */
function whoDownloadedAttachmentGetCacheRevision($id_attach, $ttl)
{
	$revision = cache_get_data('who_downloaded_revision_' . (int) $id_attach, $ttl);

	return $revision === null ? 'initial' : $revision;
}

/**
 * Stop the downloaders list request when access is denied.
 *
 * @param bool $is_xml
 */
function whoDownloadedAttachmentDenyAccess($is_xml)
{
	if ($is_xml)
		die;

	fatal_lang_error('no_access', false);
}

/**
 * Check whether current user can access the attachment itself.
 *
 * @param int $id_attach
 * @return bool
 */
function whoDownloadedAttachmentCanViewAttachment($id_attach)
{
	global $smcFunc, $user_info;

	$id_attach = (int) $id_attach;
	if (empty($id_attach))
		return false;

	$boards_allowed = boardsAllowedTo('view_attachments');
	if (empty($boards_allowed))
		return false;

	$params = array(
		'id_attach' => $id_attach,
		'attachment_type' => 0,
	);
	$board_clause = '';

	if ($boards_allowed !== array(0))
	{
		$board_clause = '
			AND m.id_board IN ({array_int:boards_allowed})';
		$params['boards_allowed'] = $boards_allowed;
	}

	$request = $smcFunc['db_query']('', '
		SELECT a.id_attach, a.approved, m.id_member, m.id_board
		FROM {db_prefix}attachments AS a
			INNER JOIN {db_prefix}messages AS m ON (m.id_msg = a.id_msg)
			INNER JOIN {db_prefix}boards AS b ON (b.id_board = m.id_board AND {query_see_board})
		WHERE a.id_attach = {int:id_attach}
			AND a.attachment_type = {int:attachment_type}' . $board_clause . '
		LIMIT 1',
		$params
	);

	if ($smcFunc['db_num_rows']($request) == 0)
	{
		$smcFunc['db_free_result']($request);
		return false;
	}

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	if (empty($row['approved']))
	{
		if (!empty($user_info['id']) && !empty($row['id_member']) && $row['id_member'] == $user_info['id'])
			return true;

		$approve_boards = boardsAllowedTo('approve_posts');
		if ($approve_boards === array(0) || in_array($row['id_board'], $approve_boards))
			return true;

		return false;
	}

	return true;
}

/**
 * Build the downloaders list HTML.
 *
 * @param int $id_attach
 * @param int $max_days
 * @param int $max_rows
 * @param bool $show_ip
 * @return string
 */
function whoDownloadedAttachmentGetDownloadListHtml($id_attach, $max_days, $max_rows, $show_ip)
{
	global $smcFunc, $scripturl, $context, $txt;

	$charset = !empty($context['character_set']) ? $context['character_set'] : 'UTF-8';
	$where_clause = '';
	$params = array('id_attach' => (int) $id_attach);

	if ($max_days > 0)
	{
		$where_clause = ' AND d.log_time >= {int:since_time}';
		$params['since_time'] = time() - ($max_days * 86400);
	}

	$request = $smcFunc['db_query']('', '
		SELECT d.id_member, d.log_time, d.ip, m.real_name
		FROM {db_prefix}log_downloads AS d
			LEFT JOIN {db_prefix}members AS m ON (m.id_member = d.id_member)
		WHERE d.id_attach = {int:id_attach}' . $where_clause . '
		ORDER BY d.log_time DESC
		LIMIT {int:max_rows}',
		array_merge($params, array('max_rows' => (int) $max_rows))
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		$download_list = '<div class="download_list_empty">' . $txt['attachment_download_list_empty'] . '</div>';
	else
	{
		$download_list = '<table class="download_list_table"><thead><tr>' .
			'<th>' . htmlspecialchars($txt['who_downloaded_column_member'], ENT_QUOTES, $charset) . '</th>' .
			'<th>' . htmlspecialchars($txt['who_downloaded_column_time'], ENT_QUOTES, $charset) . '</th>' .
			($show_ip ? '<th>' . htmlspecialchars($txt['who_downloaded_column_ip'], ENT_QUOTES, $charset) . '</th>' : '') .
			'</tr></thead><tbody>';
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if (!empty($row['id_member']))
			{
				$member_name = !empty($row['real_name']) ? $row['real_name'] : '#' . (int) $row['id_member'];
				$member_html = sprintf(
					'<a href="%s?action=profile;u=%d">%s</a>',
					htmlspecialchars($scripturl, ENT_QUOTES, $charset),
					(int) $row['id_member'],
					htmlspecialchars($member_name, ENT_QUOTES, $charset)
				);
			}
			else
				$member_html = htmlspecialchars($txt['who_downloaded_deleted_member'], ENT_QUOTES, $charset);

			$download_list .= '<tr><td>' . $member_html . '</td><td>' . timeformat($row['log_time']) . '</td>' .
				($show_ip ? '<td>' . htmlspecialchars($row['ip'], ENT_QUOTES, $charset) . '</td>' : '') . '</tr>';
		}
		$download_list .= '</tbody></table>';
	}
	$smcFunc['db_free_result']($request);

	return $download_list;
}

/**
 * Render the prepared downloaders list.
 *
 * @param string $download_list
 * @param bool $is_xml
 */
function whoDownloadedAttachmentRenderList($download_list, $is_xml)
{
	global $context, $txt;

	loadTemplate('WhoDownloadedAttachment');

	if ($is_xml)
	{
		$context['sub_template'] = 'download_list';
		$xml_download_list = str_replace(']]>', ']]]]><![CDATA[>', $download_list);
		$context['download_list']['xml'] = '<download_list><![CDATA[' . $xml_download_list . ']]></download_list>';
	}
	else
	{
		$context['page_title'] = $txt['attachment_download_list'];
		$context['sub_template'] = 'download_list_page';
		$context['download_list']['html'] = $download_list;
	}
}

/**
 * Add mod copyright to the forum credit's page.
 */
function addWhoDownloadedAttachmentCopyright()
{
	global $context;

	if (isset($context['current_action']) && $context['current_action'] == 'credits')
		$context['copyrights']['mods'][] = '<a href="https://mysmf.net/mods/who-downloaded-attachment">Who Downloaded Attachment</a> &copy; 2017-2026, digger';
}
