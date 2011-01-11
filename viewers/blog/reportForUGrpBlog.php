<?php

require_once(dirname(__FILE__).'/../../common/connect/cred.php');
require_once(dirname(__FILE__).'/../../common/connect/db.php');

if (! is_admin()) {
	header("Location: ".HEURIST_URL_BASE."common/connect/login.php");
}

$groupID = intval(@$_REQUEST["g"]);
$userID = intval(@$_REQUEST["u"]);
$date = $_REQUEST["d"];

if (! $groupID  &&  ! $userID) {
	exit("No group or user specified");
}

function get_group_members($gid) {
	$query = "SELECT usr.ugr_ID as id, usr.ugr_FirstName as firstname, usr.ugr_LastName as lastname
	            FROM ".USERS_DATABASE.".".USER_GROUPS_TABLE."
	      INNER JOIN ".USERS_DATABASE.".".USERS_TABLE." usr ON usr.ugr_ID = ugl_UserID
	           WHERE ugl_GroupID = $gid
	        ORDER BY usr.ugr_LastName, usr.ugr_FirstName";
	$res = mysql_query($query);
	$rv = array();
	while ($row = mysql_fetch_assoc($res)) {
		array_push($rv, $row);
	}
	return $rv;
}

function print_heading($date) {
	print "<h2>Entries since " . $date . ", generated on " . date("Y-m-d") . " at " . date("H:i:s") . "<h2>\n";
}

function get_blog_entries($uid, $date) {
	$query = "SELECT rec_ID as id,
	                 rec_Added as added,
	                 rec_Title as title
	            FROM Records
	           WHERE rec_AddedByUGrpID = $uid
	             AND rec_RecTypeID = 137
	" . ($date ? "AND rec_Added >= '".addslashes($date)."'" : "") . "
	         ORDER BY rec_Added DESC";
	error_log($query);
	$res = mysql_query($query);
	$rv = array();
	while ($row = mysql_fetch_assoc($res)) {
print "\n<!--\n";
print_r($row);
print "\n-->\n";
		array_push($rv, $row);
	}
	return $rv;
}

function get_blog_entry_content($id) {
	$query = "SELECT GROUP_CONCAT(chunk_Text SEPARATOR '\n')
	            FROM woots
	       LEFT JOIN woot_Chunks ON chunk_WootID = woot_ID
	                            AND chunk_IsLatest
	                            AND ! chunk_Deleted
	           WHERE woot_Title = CONCAT('record:', $id)
	        GROUP BY woot_ID
	        ORDER BY chunk_DisplayOrder";
	$res = mysql_query($query);
	if (mysql_num_rows($res) < 1) {
		return "";
	}
	$row = mysql_fetch_row($res);
	return $row[0];
}

function print_blog_entries($uid, $name, $date) {
	print "<hr>\n";
	print "<h3>$name</h3>\n";
	$entries = get_blog_entries($uid, $date);
	foreach ($entries as $entry) {
		print "<h2>" . $entry["title"] . " - " . $entry["added"] . "</h2>\n";
		$content = get_blog_entry_content($entry["id"]);
		print $content . "\n\n";
		print_comments($entry["id"]);
	}
}


function print_comments($rec_id) {
	$query = "SELECT concat(usr.ugr_FirstName,' ',usr.ugr_LastName) as Realname, cmt_Added, cmt_Text
	          FROM recThreadedComments
			  LEFT JOIN sysUGrps usr ON usr.ID = cmt_OwnerUGrpID
	          WHERE cmt_RecID = $rec_id
	          AND ! cmt_Deleted
	          ORDER BY cmt_Added";
	$res = mysql_query($query);
	if (mysql_num_rows($res) > 0) {
		print "<h3>Comments</h3>\n";
		while ($row = mysql_fetch_assoc($res)) {
			print "<p>" . $row["Realname"] . " - " . $row["cmt_Added"] . "</p>\n";
			print "<p>" . $row["cmt_Text"] . "</p>\n";
		}
	}
}

mysql_connection_select(DATABASE);

if ($groupID) {
	$gres = mysql_query("select grp.ugr_Name from sysUGrps grp where grp.ugr_ID = $groupID");
	$row = mysql_fetch_assoc($gres);
	$grp_name = $row["ugr_Name"];
	print "<h1>Blog report for group $grp_name</h1>\n";
	print_heading($date);
	$members = get_group_members($groupID);
	foreach ($members as $member) {
		print_blog_entries($member["id"], $member["firstname"]." ".$member["lastname"], $date);
	}
}
else {
	$ures = mysql_query("select concat(usr.ugr_FirstName,' ',usr.ugr_LastName) as Realname from sysUGrps usr usr where usr.ugr_ID = $userID");
	$row = mysql_fetch_assoc($ures);
	$usr_name = $row["Realname"];
	print "<h1>Blog report for user $usr_name</h1>\n";
	print_heading($date);
	print_blog_entries($userID, $usr_name, $date);
}

?>