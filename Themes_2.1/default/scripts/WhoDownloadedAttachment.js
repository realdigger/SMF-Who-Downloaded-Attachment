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
    getXMLDocument(smf_prepareScriptUrl(smf_scripturl) + 'action=get_downloaders_list;attachment=' + attachmentId + ';xml', recieveWhoDownloadedAttachmentList);
}

/*
 * Insert download list under attachment
 */
function recieveWhoDownloadedAttachmentList(oXMLDoc) {
    var download_list;

    download_list = oXMLDoc.getElementsByTagName('download_list')[0].innerHTML;
    document.getElementById('download_list_' + id_attachment).innerHTML = download_list;

    ajax_indicator(false)
}
