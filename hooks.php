<?php
/**
 * @package SMF WhoDownloadedAttachment
 * @file hooks.php
 * @author digger
 * @copyright Copyright (c) 2017-2026, digger
 * @license The MIT License (MIT) https://opensource.org/licenses/MIT
 * @version 1.1.15
 */

global $context, $user_info, $boardurl;

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF'))
	die('Error: Cannot install - please verify that you put this file in the same place as SMF\'s index.php and SSI.php files.');

if (SMF == 'SSI' && !$user_info['is_admin'])
	die('Admin privileges required.');

$hooks = array(
	'integrate_pre_include' => '$sourcedir/Mod-WhoDownloadedAttachment.php',
	'integrate_actions' => 'addWhoDownloadedAttachmentAction',
	'integrate_load_permissions' => 'addWhoDownloadedAttachmentPermissions',
	'integrate_menu_buttons' => 'addWhoDownloadedAttachmentCopyright',
	'integrate_modify_modifications' => 'addWhoDownloadedAttachmentSettings',
	'integrate_load_theme' => 'loadWhoDownloadedAttachmentAssets',
);

if (defined('SMF_VERSION') && version_compare(SMF_VERSION, '2.1', '>='))
{
	$hooks['integrate_download_headers'] = 'logWhoDownloadedAttachment';
	$hooks['integrate_prepare_display_context'] = 'addWhoDownloadedAttachmentLinksToDisplayContext';
}
else
{
	$hooks['integrate_attachment_download'] = 'logWhoDownloadedAttachment';
	$hooks['integrate_attachment_download_list'] = 'addWhoDownloadedAttachmentLink';
}

$call = !empty($context['uninstalling']) ? 'remove_integration_function' : 'add_integration_function';

foreach ($hooks as $hook => $function)
{
	if ($call == 'add_integration_function')
		add_integration_function($hook, $function);
	else
		remove_integration_function($hook, $function);
}

remove_integration_function('integrate_pre_load', 'loadWhoDownloadedAttachmentHooks');
if (SMF == 'SSI')
	echo 'Hook changes are complete! <a href="' . $boardurl . '">Return to the main page</a>.';
