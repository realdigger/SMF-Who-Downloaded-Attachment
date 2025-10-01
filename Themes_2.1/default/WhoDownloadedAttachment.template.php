<?php
/**
 * @package SMF WhoDownloadedAttachment
 * @file WhoDownloadedAttachment.template.php
 * @author digger <digger@mysmf.ru> <http://mysmf.ru>
 * @copyright Copyright (c) 2017, digger
 * @license The MIT License (MIT) https://opensource.org/licenses/MIT
 * @version 1.0
 */

function template_download_list()
{
    global $context;

    echo '<', '?xml version="1.0" encoding="', $context['character_set'], '"?', '>
<smf>
	<download_list>', cleanXml($context['download_list']['xml']), '</download_list>
</smf>';
}
