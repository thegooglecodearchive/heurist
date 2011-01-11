<?php

define('SAVE_URI', 'disabled');

require_once(dirname(__FILE__).'/../common/connect/applyCredentials.php');
require_once(dirname(__FILE__).'/../common/php/dbMySqlWrappers.php');

if (! is_logged_in()) {
	header('Location: ' . HEURIST_URL_BASE . 'common/connect/login.php');
	return;
}

mysql_connection_db_overwrite(DATABASE);

?>

<html>

 <head>
  <title>HEURIST: Delete records</title>

  <link rel="icon" href="<?=HEURIST_SITE_PATH?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?=HEURIST_SITE_PATH?>favicon.ico" type="image/x-icon">

  <link rel="stylesheet" type="text/css" href="<?=HEURIST_SITE_PATH?>common/css/newshsseri.css">

  <style type=text/css>
   a img { border: none; }
   .greyed, .greyed div { color: gray; }
  </style>
 </head>

 <body width=600 height=500 style="margin: 10px;">

<?php
	function delete_bib($rec_id) {
		mysql_query('delete from Records where rec_ID = ' . $rec_id);
		$bibs = mysql_affected_rows();
		mysql_query('delete from recDetails where dtl_RecID = ' . $rec_id);
		mysql_query('delete from usrReminders where rem_RecID = ' . $rec_id);
		mysql_query('delete from usrRecTagLinks where rtl_RecID = ' . $rec_id);
		mysql_query('delete from usrBookmarks where bkm_recID = ' . $rec_id);
		$bkmks = mysql_affected_rows();
		return array($bibs, $bkmks);
	}

	if (@$_REQUEST['delete'] == 1) {
		$bibs = 0;
		$bkmks = 0;
		$rels = 0;
		foreach ($_REQUEST['bib'] as $rec_id) {
			$rec_id = intval($rec_id);
			$res = mysql_query('select rec_AddedByUGrpID from Records where rec_ID = ' . $rec_id);
			$row = mysql_fetch_assoc($res);
			$owner = $row['rec_AddedByUGrpID'];

			$res = mysql_query('select '.USERS_USERNAME_FIELD.' from Records left join usrBookmarks on bkm_recID=rec_ID left join '.USERS_DATABASE.'.'.USERS_TABLE.' on '.USERS_ID_FIELD.'=bkm_UGrpID where rec_ID = ' . $rec_id);
			$bkmk_count = mysql_num_rows($res);
			$bkmk_users = array();
			while ($row = mysql_fetch_assoc($res)) array_push($bkmk_users, $row[USERS_USERNAME_FIELD]);

			if (is_admin()  ||
				($owner == get_user_id()  &&
				 ($bkmk_count == 0  ||
				 ($bkmk_count == 1  &&  $bkmk_users[0] == get_user_username())))) {

				list($a, $b) = delete_bib($rec_id);
				$bibs += $a;
				$bkmks += $b;
				$refs_res = mysql_query('select rec_ID from recDetails left join defDetailTypes on dty_ID=dtl_DetailTypeID left join Records on rec_ID=dtl_RecID where dty_Type="resource" and dtl_Value='.$rec_id.' and rec_RecTypeID=52');
				while ($row = mysql_fetch_assoc($refs_res)) {
					list($a, $b) = delete_bib($row['rec_ID']);
					$rels += $a;
					$bkmks += $b;
				}

			}
		}
		print '<p><b>' . $bibs . '</b> records records, <b>' . $rels . '</b> relationships and <b>' . $bkmks . '</b> associated bookmarks deleted</p>' . "\n";
		print '<input type="button" value="close" onclick="top.location.reload(true);">' . "\n";
	} else {
?>

  <form method="post">

<?php

	if (is_admin()) {
		print '<a style="float: right;" target=_new href=../../admin/verification/combineDuplicateRecords.php?bib_ids='.$_REQUEST['ids'].'>fix duplicates</a>';
	} else {
		print '<p>NOTE: You may not delete records you did not create, or records that have been bookmarked by other users</p>';
	}

	$bib_ids = explode(',', $_REQUEST['ids']);
	foreach ($bib_ids as $rec_id) {
		if (! $rec_id) continue;
		$res = mysql_query('select rec_Title,rec_AddedByUGrpID from Records where rec_ID = ' . $rec_id);
		$row = mysql_fetch_assoc($res);
		$rec_title = $row['rec_Title'];
		$owner = $row['rec_AddedByUGrpID'];
		$res = mysql_query('select '.USERS_USERNAME_FIELD.' from Records left join usrBookmarks on bkm_recID=rec_ID left join '.USERS_DATABASE.'.'.USERS_TABLE.' on '.USERS_ID_FIELD.'=bkm_UGrpID where rec_ID = ' . $rec_id);
		$bkmk_count = mysql_num_rows($res);
		$bkmk_users = array();
		while ($row = mysql_fetch_assoc($res)) array_push($bkmk_users, $row[USERS_USERNAME_FIELD]);
		$refs_res = mysql_query('select dtl_RecID from recDetails left join defDetailTypes on dty_ID=dtl_DetailTypeID where  dty_Type="resource and dtl_Value='.$rec_id.' "');
		$refs = mysql_num_rows($refs_res);

		$allowed = is_admin()  ||
				   ($owner == get_user_id()  &&
				   ($bkmk_count == 0  ||
				   ($bkmk_count == 1  &&  $bkmk_users[0] == get_user_username())));

		print "<div".(! $allowed ? ' class=greyed' : '').">\n";
		print ' <p><input type="checkbox" name="bib[]" value="'.$rec_id.'"'.($bkmk_count <= 1  &&  $refs == 0  &&  $allowed ? ' checked' : '').(! $allowed ? ' disabled' : '').'>' ."\n";
		print ' ' . $rec_id . '<a target=_new href="'.HEURIST_SITE_PATH.'records/edit/editRecord.html?bib_id='.$rec_id.'"><img src='.HEURIST_SITE_PATH.'common/images/external_link_16x16.gif></a>' ."\n";
		print ' ' . $rec_title ."</p>\n";

		print ' <p style="margin-left: 20px;"><b>' . $bkmk_count . '</b> bookmark' . ($bkmk_count == 1 ? '' : 's') . ($bkmk_count > 0 ? ':' : '') . "\n  ";
		print join(', ', $bkmk_users);
		print "\n </p>\n";

		if ($refs) {
			print ' <p style="margin-left: 20px;">Referenced by: '."\n";
			while ($row = mysql_fetch_assoc($refs_res)) {
				print '  <a target=_new href="'.HEURIST_SITE_PATH.'records/edit/editRecord.html?bib_id='.$row['dtl_RecID'].'">'.$row['dtl_RecID'].'</a>'."\n";
			}
			print "\n </p>\n";
		}

		print "\n</div>\n\n";
	}
?>

<input type="hidden" name="delete" value="1">
<input type="submit" value="delete" onclick="return confirm('ARE YOU SURE YOU WISH TO DELETE THE SELECTED RECORDS, ALONG WITH ALL ASSOCIATED BOOKMARKS?')  &&  confirm('REALLY REALLY SURE?');">

  </form>

<?php
	}
?>

 </body>
</html>
