<?php
/// Copyright (c) 2004-2014, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)
define('__TEXTCUBE_IPHONE__', true);
require ROOT . '/library/preprocessor.php';
requireView('iphoneView');
printMobileHTMLHeader();
printMobileHTMLMenu('','comment');


list($entryId) = getCommentAttributes($blogid, $suri['id'], 'entry');
list($entries, $paging) = getEntryWithPaging($blogid, $entryId);
$entry = $entries ? $entries[0] : null;


?>
<div id="comment_reply_<?php echo $suri['id']."_".time();?>" title="Comment reply" selected="false">
	<?php
		printMobileCommentFormView($suri['id'], _text('댓글에 답글 달기'), 'comment/comment');
	?>
	<fieldset class="navi margin-top10">
	<?php
		printMobileNavigation($entry);
	?>
	</fieldset>
</div>
<?php
printMobileHTMLFooter();
?>
