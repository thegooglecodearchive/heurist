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
* loadUserGrps.php
* load the particular user info and list of its groups, or group info and list of its memebrs
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


define("SAVE_URI", "disabled");

require_once(dirname(__FILE__).'/../../common/connect/applyCredentials.php');
require_once(dirname(__FILE__).'/../../common/php/dbMySqlWrappers.php');

header('Content-type: text/javascript');

mysql_connection_select(DATABASE);

/*****DEBUG****///error_log(">>>>>>>>>>>>>>> ".$_SERVER['QUERY_STRING']);
/*****DEBUG****///error_log(">>>>>>>>>>>>>>> userEmail=".urldecode(@$_REQUEST['userEmail']));
/*****DEBUG****///error_log(">>>>>>>>>>>>>>> userEmail=".urldecode(@$_GET['userEmail']));
	$metod = @$_REQUEST['method'];

if (is_logged_in() || $metod=="getuser") {
	if($metod=="searchuser"){
		//search the list of users by specified parameters
		$f_group = @$_REQUEST['grpID'];
		$f_nogroup = @$_REQUEST['nogrpID'];
		$f_role  = @$_REQUEST['grpRole'];
		$f_name  = urldecode(@$_REQUEST['name']);
		$f_disab = @$_REQUEST['disabled'];

/*****DEBUG****///error_log(">>>>>>>>>>>>>>> QUERY =".$f_group."        ".$f_role);

		$query = "select 0 as selection, usr.ugr_ID, usr.ugr_Name, concat(usr.ugr_FirstName, ' ', usr.ugr_LastName) as fullname, usr.ugr_eMail, usr.ugr_Enabled, usr.ugr_Organisation,";

		if($f_group!="all"){
			$query = $query." grp.ugl_Role as role from sysUGrps usr, sysUsrGrpLinks grp where usr.ugr_Type='user' and usr.ugr_ID=grp.ugl_UserID and grp.ugl_GroupID=".$f_group;
			if($f_role!="all"){
				$query = $query." and grp.ugl_Role='".$f_role."'";
			}
			if($f_group!="all"){

			}
			if($f_nogroup!="all"){
				$query = $query." and grp.ugl_GroupID!=".$f_nogroup;
			}
		}else{
			$query = $query." '' as role from sysUGrps usr where usr.ugr_Type='user'";
		}
		if($f_name && $f_name!=""){
			$query = $query." and usr.ugr_Name like '%".$f_name."%'";
		}

		//exclude user from specific group (used for selection to this group)
		if($f_nogroup!="all"){
			$query = $query." and usr.ugr_Enabled='y' and usr.ugr_ID not in (select grp2.ugl_UserID from sysUsrGrpLinks as grp2 where grp2.ugl_GroupID=".$f_nogroup.")";
		}else if($f_disab && $f_disab!=0){
			$query = $query." and usr.ugr_Enabled!='y'";
		}


		$userGrp = array();
		$userGrp['userslist'] = array();

/*****DEBUG****///error_log(">>>>>>>>>>>>>>> QUERY =".$query);

		$res = mysql_query($query);
		while ($row = mysql_fetch_row($res)) {
			$userGrp['userslist'][$row[1]] = $row;
		}

		print json_format($userGrp);
		exit();

	}else if($metod=="searchgroup"){

		//search the list of groups by specified parameters
		$f_user = @$_REQUEST['userID'];
		$f_role  = @$_REQUEST['grpRole'];
		$filter1 = "";
		$filter2 = "";

		//if noty admin, the list of users are limited to its own
		if(!is_admin()){
			$f_user =  get_user_id();
			if (!($f_role=="admin" || $f_role=="member")){
				$f_role="any";
			}
		}


		if($f_user!=null && $f_user!=""){
			$filter1 = ", ".USERS_DATABASE.".sysUsrGrpLinks gl2 ";
			$filter2 = " and grp.ugr_ID=gl2.ugl_GroupID and gl2.ugl_UserID=".$f_user;

			if($f_role!=null && $f_role!="" && $f_role!="any"){
				$filter2 = $filter2." and gl2.ugl_Role='".$f_role."'";
			}
		}

/*****DEBUG****///error_log(">>>>>>>>>>>>>>> QUERY =".$f_group."        ".$f_role);

		$workgroups = array();
		$workgroupIDs = array();
		$workgroupsLength = 0;

		//loads list of all groups

		$query = "select grp.ugr_ID as grpID, grp.ugr_Name as grpName, grp.ugr_LongName as grpLongName, grp.ugr_Description as description, grp.ugr_URLs as URL, count(gl1.ugl_UserID) as members, grp.ugr_Enabled as enabled
						  from ".USERS_DATABASE.".sysUGrps grp
					 left join ".USERS_DATABASE.".sysUsrGrpLinks gl1 on gl1.ugl_GroupID = grp.ugr_ID
					 left join ".USERS_DATABASE.".sysUGrps b on b.ugr_ID = gl1.ugl_UserID $filter1
						 where grp.ugr_Type != 'user'
						   and b.ugr_Enabled  = 'y' $filter2
					  group by grp.ugr_ID order by grp.ugr_Name";

/*****DEBUG****///error_log(">>>>>>>>>>>>>>>>>>".$query);

		$res = mysql_query($query);

		while ($row = mysql_fetch_assoc($res)) {
				$workgroups[$row["grpID"]] = array(
							"name" => $row["grpName"],
							"longname" => $row["grpLongName"],
							"description" => $row["description"],
							"url" => $row["URL"],
							"memberCount" => $row["members"],
							"enabled" => $row["enabled"]
				);
				$workgroupIDs[$row["grpName"]] = $row["grpID"];

				$workgroupsLength = max($workgroupsLength, intval($row["grpID"])+1);
		}
		$workgroups["length"] = $workgroupsLength;


		//load list of admins for each group

		$res = mysql_query("select gl1.ugl_GroupID, concat(b.ugr_FirstName,' ',b.ugr_LastName) as name, b.ugr_eMail, b.ugr_ID
						  from ".USERS_DATABASE.".sysUGrps grp
					 left join ".USERS_DATABASE.".sysUsrGrpLinks gl1 on gl1.ugl_GroupID = grp.ugr_ID
					 left join ".USERS_DATABASE.".sysUGrps b on b.ugr_ID = gl1.ugl_UserID $filter1
						 where grp.ugr_Type != 'user'
						   and gl1.ugl_Role = 'admin'
						   and b.ugr_Enabled  = 'y' $filter2
					  order by gl1.ugl_GroupID, b.ugr_LastName, b.ugr_FirstName");

		$grp_id = 0;
		while ($row = mysql_fetch_assoc($res)) {
			if ($grp_id == 0   ||  $grp_id != $row["ugl_GroupID"]) {
				if ($workgroups[$row["ugl_GroupID"]])
					$workgroups[$row["ugl_GroupID"]]["admins"] = array();
			}
			$grp_id = $row["ugl_GroupID"];

			if ($workgroups[$grp_id]){
				array_push($workgroups[$grp_id]["admins"],
					array("name" => $row["name"], "email" => $row["ugr_eMail"], "id" => $row["ugr_ID"]));
			}
		}

		$userGrp = array();
		$userGrp['workgroups'] = $workgroups;
		$userGrp['workgroupIDs'] = $workgroupIDs;

		print json_format($userGrp);
		//print "top.HEURIST.workgroups = " . json_format($workgroups) . ";\n";
		//print "top.HEURIST.workgroupIDs = " . json_format($workgroupIDs) . ";\n";
		//print "\n";
		exit();

	}else if($metod=="getgroup"){ //----------------- only one group with detailed information

		$groupID = @$_REQUEST['recID'];
		if ($groupID==null) {
			die("invalid call to loadUserGrpInfo, recID is required");
		}

		$colNames = array("ugr_ID", "ugr_Type", "ugr_Name", "ugr_LongName", "ugr_Description", "ugr_URLs", "ugr_Enabled");

		$userGrp = array();
		$userGrp['groups'] = array('fieldNames' => $colNames);

		$query = "select ".join(",", $colNames)." from ".USERS_DATABASE.".sysUGrps where ";

		if($groupID!="0"){
			$query = $query."ugr_ID=".$groupID;
		}else{
			$query = null;
		}

		if(@$_REQUEST['all']==null){
			$sAdminOnly = "ugl_Role = 'admin' and";
		}else{
			$sAdminOnly = null;
		}

/*****DEBUG****///error_log(">>>>>>>>>>>>>>> QUERY =".$query);
		if($query){
			$res = mysql_query($query);
			while ($row = mysql_fetch_row($res)) {
				$userGrp['groups'][$row[0]] = $row;

				if($sAdminOnly){
					$query = "select ugl_UserID from ".USERS_DATABASE.
							".sysUsrGrpLinks where ".$sAdminOnly." ugl_GroupID=".$row[0];
				}else{
					$query = "select ugl_UserID, ugl_Role, ugr_FirstName, ugr_LastName from ".USERS_DATABASE.
							".sysUsrGrpLinks g, ".USERS_DATABASE.".sysUGrps u where u.ugr_ID=g.ugl_UserID and g.ugl_GroupID=".$row[0];
				}

				$res2 = mysql_query($query);

				$admins = array();
				$members = array();
				while ($row2 = mysql_fetch_assoc($res2)) {
					if($sAdminOnly){
						array_push($admins, $row2["ugl_UserID"]);
					}else{
						if($row2["ugl_Role"]=="admin"){
							array_push($admins, $row2["ugl_UserID"]);
						}
						$members[$row2["ugl_UserID"]] = $row2["ugr_FirstName"]." ".$row2["ugr_LastName"];
						//array_push($members, array($row2["ugl_UserID"]=>($row2["ugr_FirstName"]." ".$row2["ugr_LastName"]) ));  //array
					}
				}
				$userGrp['groups'][$row[0]]["admins"] = $admins;
				if(!$sAdminOnly){
					$userGrp['groups'][$row[0]]["members"] = $members;
				}
			}
		}

		if($sAdminOnly){
			print "top.HEURIST.userGrp = " . json_format($userGrp) . ";\n";
			print "\n";
		}else{
			print json_format($userGrp);
		}

	}else if($metod=="getuser"){ //----------------- only one user with detailed information


	$userID = @$_REQUEST['recID'];

	if ($userID==null) {
		die("invalid call to loadUserGrpInfo, recID is required");
	}

	if($userID!=null){

		$colNames = array("ugr_ID", "ugr_Type", "ugr_Name", "ugr_Description",
		"ugr_Password", "ugr_eMail", "ugr_FirstName", "ugr_LastName",
		"ugr_Department", "ugr_Organisation", "ugr_City", "ugr_State", "ugr_Postcode", "ugr_Interests",
		"ugr_Enabled" , "ugr_URLs", "ugr_IncomingEmailAddresses");

		$userGrp = array();
		$userGrp['users'] = array('fieldNames' => $colNames);

		$query = "select ".join(",", $colNames)." from ".USERS_DATABASE.".sysUGrps where ";
		/*if($userEmail){
			$query = $query."ugr_eMail='".$userEmail."'";
		} else*/

		if(intval($userID)==0 && is_logged_in()){
			$userID = get_user_id();
		}

		if(intval($userID)!=0 && is_logged_in()){
			$query = $query."ugr_ID=".$userID;
		}else {
			$query = null;
		}

/*****DEBUG****///error_log(">>>>>>>>>>>>>>> QUERY =".$query);
		if($query){
			$res = mysql_query($query);
			while ($row = mysql_fetch_row($res)) {
				$userGrp['users'][$row[0]] = $row;
			}
		}

		if(!is_logged_in()){
			$query = mysql_query("SELECT ugr_FirstName, ugr_LastName, ugr_eMail FROM sysUGrps WHERE ugr_ID=2");
			$details = mysql_fetch_row($query);
			$fullName = $details[0] . " " . $details[1];
			$eMail = $details[2];
			$userGrp['adminName'] = $fullName;
			$userGrp['adminMail'] = $eMail;
		}

// using ob_gzhandler makes this stuff up on IE6-
//ini_set("zlib.output_compression_level", 5);
//ob_start('ob_gzhandler');


	print "top.HEURIST.userGrp = " . json_format($userGrp) . ";\n";
	print "\n";

	}
?>
	if (typeof top.HEURIST.fireEvent == "function") top.HEURIST.fireEvent(window, "heurist-obj-user-loaded");
<?php
//ob_end_flush();
	}//search the particular user info
}else{ //logged in
?>
    top.document.body.className += " is-not-logged-in";
<?php
}
?>
