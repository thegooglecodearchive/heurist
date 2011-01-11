<?php

require_once(dirname(__FILE__)."/../../common/connect/applyCredentials.php");
require_once(dirname(__FILE__)."/../../common/php/dbMySqlWrappers.php");

function jsonError($message) {
	print "{\"error\":\"" . addslashes($message) . "\"}";
	exit(0);
}

if (! is_logged_in()) {
	jsonError("no logged-in user");
}

if (! @$_REQUEST["label"]) {
	jsonError("missing argument: label");
}

mysql_connection_db_overwrite(DATABASE);

$label = @$_REQUEST["label"];
$wg = intval(@$_REQUEST["wg"]);

if ($wg > 0) {
	mysql_query("delete from usrSavedSearches where svs_Name='$label' and svs_UGrpID=$wg");
} else {
	mysql_query("delete from usrSavedSearches where svs_Name='$label' and svs_UGrpID=".get_user_id());
}

print "{\"deleted\":" . (mysql_affected_rows() > 0 ? "true" : "false") . "}";

?>