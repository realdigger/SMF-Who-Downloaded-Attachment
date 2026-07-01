<?php
/**
 * @package SMF WhoDownloadedAttachment
 * @file WhoDownloadedAttachment.template.php
 * @author digger
 * @copyright Copyright (c) 2017-2026, digger
 * @license The MIT License (MIT) https://opensource.org/licenses/MIT
 * @version 1.1.2
 */

function template_download_list()
{
	global $context;

	echo '<', '?xml version="1.0" encoding="', $context['character_set'], '"?', '>
', cleanXml($context['download_list']['xml']), '
';
}


function template_download_list_page()
{
	global $context, $txt;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">', $txt['attachment_download_list'], '</h3>
	</div>
	<div class="windowbg">
		<div class="content">', $context['download_list']['html'], '</div>
	</div>';
}
