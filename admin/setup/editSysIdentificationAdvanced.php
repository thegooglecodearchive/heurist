<?php

/**
 * editSysIdentificationEmail.php, edits the email section of the system identification record
 * defining email server and addresses for incoming and outgoing mail
 * Ian Johnson 12 aug 2011
 * @copyright (C) 2005-2010 University of Sydney Digital Innovation Unit.
 * @link: http://HeuristScholar.org
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Heurist academic knowledge management system
 * @todo
 **/

?>

<?php

require_once(dirname(__FILE__).'/../../common/connect/applyCredentials.php');
require_once(dirname(__FILE__).'/../../common/t1000/t1000.php');

if (! is_logged_in()) {
	header('Location: ' . HEURIST_URL_BASE . 'common/connect/login.php?db='.HEURIST_DBNAME);
	return;
}
if (! is_admin()) return;


mysql_connection_overwrite(DATABASE);

$template = file_get_contents('editSysIdentificationAdvanced.html');
// $template = str_replace('{PageHeader}', '[literal]'.file_get_contents('menu.html').'[end-literal]', $template);
$lexer = new Lexer($template);

$body = new BodyScope($lexer);
$body->global_vars['dbname'] = HEURIST_DBNAME;
$body->verify();
if ($_REQUEST['_submit']) {
	$body->input_check();
	if ($body->satisfied) $body->execute();
}

$body->render();

?>