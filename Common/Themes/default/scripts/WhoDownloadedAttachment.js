/**
 * @package SMF WhoDownloadedAttachment
 * @file WhoDownloadedAttachment.js
 * @author digger
 * @copyright Copyright (c) 2017-2026, digger
 * @license The MIT License (MIT) https://opensource.org/licenses/MIT
 * @version 1.1.3
 */

var smf_scripturl;

function showWhoDownloadedAttachmentList(attachmentId)
{
	var requestCompleted = false;
	var requestTimedOut = false;
	var timeoutId;

	attachmentId = parseInt(attachmentId, 10);
	if (!attachmentId)
		return true;

	ajax_indicator(true);
	timeoutId = window.setTimeout(function() {
		if (!requestCompleted)
		{
			requestTimedOut = true;
			ajax_indicator(false);
		}
	}, 15000);

	getXMLDocument(smf_prepareScriptUrl(smf_scripturl) + 'action=get_downloaders_list;attachment=' + attachmentId + ';xml=1', function(oXMLDoc) {
		if (requestTimedOut)
			return;

		requestCompleted = true;
		window.clearTimeout(timeoutId);
		receiveWhoDownloadedAttachmentList(oXMLDoc, attachmentId);
	});

	return false;
}

function receiveWhoDownloadedAttachmentList(oXMLDoc, attachmentId)
{
	var download_list = '';
	var target = document.getElementById('download_list_' + attachmentId);
	var nodes;

	if (!target)
	{
		ajax_indicator(false);
		return;
	}

	if (!oXMLDoc || !oXMLDoc.getElementsByTagName)
	{
		ajax_indicator(false);
		return;
	}

	nodes = oXMLDoc.getElementsByTagName('download_list');
	if (nodes.length > 0)
		download_list = typeof nodes[0].textContent !== 'undefined' ? nodes[0].textContent : nodes[0].text;

	target.innerHTML = download_list;
	ajax_indicator(false);
}
