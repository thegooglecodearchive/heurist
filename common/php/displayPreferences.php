<?php

/* Load the user's display preferences.
 * Display preferences are added as CSS classes to the document body:
 * you should include this file in the BODY, not in the head.
 *
 * If arguments  xxx=yyy  are supplied, set those for future display,
 * and suppress normal output.
 *
 * Setting  xxx=yyy  will add class  xxx-yyy  to the body,
 * but then setting  xxx=xyz  would add  xxx-xyz  INSTEAD.
 *
 * Preferences are currently stored in the $_SESSION[HEURIST_INSTANCE_PREFIX.'heurist'], maybe they would eventually be in the DB.
 */

define("SAVE_URI", "disabled");

require_once(dirname(__FILE__)."/../connect/applyCredentials.php");

header("Content-type: text/javascript");


/* an array of the properties that may be set, and default values */
$prefs = array(
	"help" => "show",
    "advanced" => "hide",
	"input-visibility" => "required",
	"action-on-save" => "stay",
	"gigitiser-view" => "",
	"double-click-action" => "edit",

	"my-records-searches" => "show",
	"all-records-searches" => "show",
	"workgroup-searches" => "show",
	"left-panel-scroll" => 0,

	"record-search-string" => "",
	"record-search-type" => "",

	"search-result-style" => "list",
	"results-per-page" => 50,

	"scratchpad-bottom" => 0,
	"scratchpad-right" => 0,
	"scratchpad-width" => 0,
	"scratchpad-height" => 0,
	"scratchpad" => "hide"
);

foreach (get_group_ids() as $gid) {
	$prefs["workgroup-searches-$gid"] = "hide";
}

session_start();

$writeMode = false;
foreach ($_REQUEST as $property => $value) {
	if (array_key_exists($property, $prefs)) {
		$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']["display-preferences"][$property] = $value;
		$writeMode = true;
	}
}
if ($writeMode) return;	// suppress normal output

?>

//document.domain = "h3.heuristscholar.org";

if (! document.body) {
	// Document manipulation becomes much harder if we can't access the body.
	throw document.location.href + ": include displayPreferences.php in the body, not the head";
}

<?php

if ($prefs) {
	print "top.HEURIST.displayPreferences = {";
	$first = true;
	$classNames = "";
	$replaceClassNames = "";
	foreach ($prefs as $property => $value) {
		if (! $first) print ",";  $first = false;
		print "\n";

		if (@$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']["display-preferences"][$property])
			$value = $_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']["display-preferences"][$property];

		print "\t\"".addslashes($property)."\": \"".addslashes($value)."\"";

		$classNames .= " " . addslashes($property . "-" . $value);

		if ($replaceClassNames) $replaceClassNames .= "|";
		$replaceClassNames .= "\\b" . $property . "-\\S+\\b";
	}
	print "\n};\n";
	print "window.document.body.className = window.document.body.className.replace(/$replaceClassNames/g, '" . $classNames . "');\n";
	print "window.document.body.className += '" . $classNames . "';\n";

} else {
	print "top.HEURIST.displayPreferences = {};\n";
}

?>
top.HEURIST.fireEvent(window, "heurist-display-preferences-loaded");