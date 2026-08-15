/**
 * @package SMF WhoDownloadedAttachment
 * @file WhoDownloadedAttachment.js
 * @author digger <digger@mysmf.ru> <http://mysmf.ru>
 * @copyright Copyright (c) 2017, digger
 * @license The MIT License (MIT) https://opensource.org/licenses/MIT
 * @version 1.0
 */

var smf_scripturl;
var id_attachment;

/*
 * Get download list
 */
function showWhoDownloadedAttachmentList(attachmentId) {
    id_attachment = attachmentId;

    ajax_indicator(true);
    getXMLDocument(smf_prepareScriptUrl(smf_scripturl) + 'action=get_downloaders_list;attachment=' + attachmentId + ';xml=1', recieveWhoDownloadedAttachmentList);

    return false;
}

/*
 * Insert download list under attachment
 */
function recieveWhoDownloadedAttachmentList(oXMLDoc) {
    var download_list = '';
    var nodes = oXMLDoc.getElementsByTagName('download_list');
    var target = document.getElementById('download_list_' + id_attachment);

    if (nodes.length > 0)
        download_list = typeof nodes[0].textContent !== 'undefined' ? nodes[0].textContent : nodes[0].text;

    if (target)
        target.innerHTML = download_list;

    ajax_indicator(false);
}
