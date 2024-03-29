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
* brief description of file
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


require_once(dirname(__FILE__).'/../connect/applyCredentials.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Legend & Reference Types</title>
<link rel="stylesheet" type="text/css" href= "<?=HEURIST_SITE_PATH?>common/css/global.css">
<style type=text/css>
	div { line-height: 22px; border-bottom:1px solid #DDD;}
	div.column{ vertical-align: top;width:250px; position: absolute;top:0; border: none; }
	.right {left:280px;}
	.left {left:10px}
	img {vertical-align: text-bottom;}
</style>
</head>

<body class="popup" width=580 height=400>

<div class="column left">
	<h3>Search list icons </h3>
	<div><img src="<?=HEURIST_SITE_PATH?>common/images/logo_rss_feed.png" height=12 width=12>&nbsp;Add live bookmark</div>
    <!-- <div><img src="../../pix/home_favourite.gif">&nbsp;Favourite search</div> -->
    <div><img src="<?=HEURIST_SITE_PATH?>common/images/edit-pencil.png">&nbsp;Edit the reference</div>
    <div><img src="<?=HEURIST_SITE_PATH?>common/images/follow_links_16x16.gif">&nbsp;Detail/tools</div>
    <!-- <div><img src="../external_link_16x16.gif">&nbsp;Open in new window</div> -->
    <div><img src="<?=HEURIST_SITE_PATH?>common/images/key.gif">&nbsp;Password reminder</div>
</div>
<div class="column right">
     <h3>Reference types</h3>
<?php
require_once("dbMySqlWrappers.php");
	mysql_connection_select(DATABASE);
	$res = mysql_query('select rty_ID, rty_Name from  defRecTypes order by rty_ID');
	while ($row = mysql_fetch_row($res)) {
?>
     <div>
     	<table><tr>
     	<td width="24px" align="right"><font color="#CCC"><?= $row[0] ?>&nbsp;</font></td>
     	<td width="24px" align="center">
     		<img class="rft" style="background-image:url(<?=HEURIST_ICON_URL.$row[0].".png)"?>" src="<?=HEURIST_SITE_PATH.'common/images/16x16.gif'?>">
     	</td>
     	<td><?= htmlspecialchars($row[1]) ?></td>
     	</tr></table>
</div>
<?php
	}
?>
  </p>
</div>
</body>
</html>
