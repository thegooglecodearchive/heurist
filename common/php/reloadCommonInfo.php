<?php

/*
* Copyright (C) 2005-2013 University of Sydney
*
* Licensed under the GNU License, Version 3.0 (the "License"); you may not use this file except
* in compliance with the License. You may obtain a copy of the License at
*
* http://www.gnu.org/licenses/gpl-3.0.txt
*
* Unless required by applicable law or agreed to in writing, software distributed under the License
* is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
* or implied. See the License for the specific language governing permissions and limitations under
* the License.
*/

/**
* reloadCommonInfo.pgp - returns definitions as json
*
* @author      Tom Murtagh
* @author      Kim Jackson
* @author      Ian Johnson   <ian.johnson@sydney.edu.au>
* @author      Stephen White   <stephen.white@sydney.edu.au>
* @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
* @copyright   (C) 2005-2013 University of Sydney
* @link        http://Sydney.edu.au/Heurist
* @version     3.1.0
* @license     http://www.gnu.org/licenses/gpl-3.0.txt GNU License 3.0
* @package     Heurist academic knowledge management system
* @subpackage  !!!subpackagename for file such as Administration, Search, Edit, Application, Library
*/


/* load some very basic HEURIST objects into top.HEURIST */

// session_cache_limiter("private");
define('ISSERVICE',1);
define("SAVE_URI", "disabled");

// using ob_gzhandler makes this stuff up on IE6-
ini_set("zlib.output_compression_level", 5);
//ob_start('ob_gzhandler');


require_once(dirname(__FILE__)."/../connect/applyCredentials.php");
//require_once("dbMySqlWrappers.php");
require_once("getRecordInfoLibrary.php");

mysql_connection_select(DATABASE);

ob_start();

header("Content-type: text/javascript");

$rv = array();
if(@$_REQUEST['action']=='usageCount'){
	$rv = getRecTypeUsageCount();
}else{
	$rv['rectypes'] = getAllRectypeStructures(false);
	$rv['detailTypes'] = getAllDetailTypeStructures(false);
	$rv['terms'] = getTerms(false);
    $rv['icon_url'] = HEURIST_ICON_URL; //HEURIST_SERVER_URL.
}

print json_encode($rv);
//print json_format($rv);

ob_end_flush();
?>
