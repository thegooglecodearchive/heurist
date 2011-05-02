<?php

	/*<!--
 * filename, brief description, date of creation, by whom
 * @copyright (C) 2005-2010 University of Sydney Digital Innovation Unit.
 * @link: http://HeuristScholar.org
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Heurist academic knowledge management system
 * @todo
 -->*/

if (!defined('MEMCACHED_PORT')) define('MEMCACHED_PORT', 11211);
$memcache = null;
$lastModified = null;
// read
function setLastModified() {
global $lastModified;
	$res = mysql_query("select max(tlu_DateStamp) from sysTableLastUpdated where tlu_CommonObj = 1");
	$lastModified = mysql_fetch_row($res);
	$lastModified = strtotime($lastModified[0]);
}

function getCachedData($key) {
	global $memcache, $lastModified;
	if (! $memcache) {
		$memcache = new Memcache;
		if (! $memcache->connect('localhost', MEMCACHED_PORT)) {	//saw Decision: error or just load raw???
			return array("error" => "couldn't connect to memcached");
		}
	}
	if (!$lastModified) {
		setLastModified();
	}
//error_log("key = $key lastMod = $lastModified and cached = ".$memcache->get('lastUpdate:'.$key));
	if ($lastModified > $memcache->get('lastUpdate:'.$key)) {
		// check the cached lastupdate value and return false on not equal meaning recreate data
//		error_log("returning null from cache for key = $key");
		return null;
	}
	return $memcache->get($key);
}

function setCachedData($key, $var) {
	global $memcache, $lastModified;
	if (! $memcache) {
		$memcache = new Memcache;
		if (! $memcache->connect('localhost', MEMCACHED_PORT)) {	//saw Decision: error or just load raw???
			return array("error" => "couldn't connect to memcached");
		}
	}
	if (!$lastModified) {
		setLastModified();
	}
	$memcache->set('lastUpdate:'.$key,$lastModified);
	return $memcache->set($key,$var);
}

function getResolvedIDs($recID,$bmkID) {
	// Look at the request parameters rec_ID and bkm_ID,
	// return the actual rec_ID and bkm_ID as the user has access to them

	/* chase down replaced-by-bib-id references */
	$replaced = false;
	if (intval(@$recID)) {
		$res = mysql_query("select rfw_NewRecID from recForwarding where rfw_OldRecID=$recID");
		$recurseLimit = 10;
		while (mysql_num_rows($res) > 0) {
			$row = mysql_fetch_row($res);
			$recID = $row[0];
			$replaced = true;
			$res = mysql_query("select rfw_NewRecID from recForwarding where rfw_OldRecID=$recID");
			if ($recurseLimit-- === 0) { return array(); }
		}
	}

	$rec_id = 0;
	$bkm_ID = 0;
	if (intval(@$recID)) {
		$rec_id = intval($recID);
		$res = mysql_query('select rec_ID, bkm_ID from Records
		 left join usrBookmarks on bkm_recID=rec_ID and bkm_UGrpID='.get_user_id().' where rec_ID='.$rec_id);
		$row = mysql_fetch_assoc($res);
		$rec_id = intval($row['rec_ID']);
		$bkm_ID = intval($row['bkm_ID']);
	}

	if (! $rec_id  &&  intval(@$bmkID)) {
		$bkm_ID = intval($bmkID);
		$res = mysql_query('select bkm_ID, rec_ID from usrBookmarks left join Records on bkm_recID=rec_ID where bkm_ID='.$bkm_ID.' and bkm_UGrpID='.get_user_id());
		$row = mysql_fetch_assoc($res);
		$bkm_ID = intval($row['bkm_ID']);
		$rec_id = intval($row['rec_ID']);
	}

	return array($rec_id, $bkm_ID, $replaced);
}


function getBaseProperties($rec_id, $bkm_ID) {
	// Return an array of the basic scalar properties for this record / bookmark
	if (!$rec_id && !$bkm_ID) return array("error"=>"invalid parameters passed to getBaseProperties");
	if ($bkm_ID) {
		$res = mysql_query('select rec_ID, rec_Title as title, rty_Name as rectype,
									rty_ID as rectypeID, rec_URL as url, grp.ugr_ID as workgroupID,
									grp.ugr_Name as workgroup, rec_ScratchPad as notes,
									rec_NonOwnerVisibility as visibility, bkm_PwdReminder as passwordReminder,
									bkm_Rating as rating, rec_Modified, rec_FlagTemporary
								from usrBookmarks left join Records on bkm_recID=rec_ID and bkm_UGrpID='.get_user_id().'
												left join defRecTypes on rty_ID = rec_RecTypeID
												left join '.USERS_DATABASE.'.sysUGrps grp on grp.ugr_ID=rec_OwnerUGrpID
								where bkm_ID='.$bkm_ID);
	} else if ($rec_id) {
		$res = mysql_query('select rec_ID, rec_Title as title, rty_Name as rectype, rty_ID as rectypeID,
									rec_URL as url, grp.ugr_ID as workgroupID, grp.ugr_Name as workgroup,
									rec_ScratchPad as notes, rec_NonOwnerVisibility as visibility, rec_Modified,
									rec_FlagTemporary
								from Records left join usrBookmarks on bkm_recID=rec_ID
											left join defRecTypes on rty_ID = rec_RecTypeID
											left join '.USERS_DATABASE.'.sysUGrps grp on grp.ugr_ID=rec_OwnerUGrpID
								where rec_ID='.$rec_id);
	}

	$row = mysql_fetch_assoc($res);
	$rec_id = $row["rec_ID"];
	$props = array();

	if ($rec_id) $props["bibID"] = $rec_id;
	if ($rec_id) $props["recID"] = $rec_id; // saw TODO leave both for now until weed out bibID
	if ($bkm_ID) $props["bkmkID"] = $bkm_ID;
	$props["title"] = $row["title"];
	$props["rectype"] = $row["rectype"];
	$props["rectypeID"] = $row["rectypeID"];
	$props["url"] = $row["url"];
	$props["moddate"] = $row["rec_Modified"];
	$props["isTemporary"] = $row["rec_FlagTemporary"]? true : false;

	if (@$row["passwordReminder"]) {
		$props["passwordReminder"] = $row["passwordReminder"];
	}
	if (@$row["rating"]) {
		$props['rating'] = $row['rating'];
	}
	$props["quickNotes"] = @$row["quickNotes"]? $row["quickNotes"] : "";
	if ($row['workgroupID']) {
		$props['workgroupID'] = $row['workgroupID'];
		$props['workgroup'] = $row['workgroup'];
		if ($row['visibility']) $props['workgroupVisibility'] = $row['visibility'];
	}
//	$props['notes'] = $row['notes']; // saw TODO: add code to get personal woots

	if ($bkm_ID) {
		// grab the user tags for this bookmark, as a single comma-delimited string
		$kwds = mysql__select_array("usrRecTagLinks left join usrTags on tag_ID=rtl_TagID", "tag_Text", "rtl_RecID=$rec_id and tag_UGrpID=".get_user_id() . " order by rtl_Order, rtl_ID");
		$props["tagString"] = join(",", $kwds);
	}

	return $props;
}

function getAllBibDetails($rec_id) {
	// Get all recDetails entries for this entry,
	// as an array.
	// File entries have file data associated,
	// geo entries have geo data associated,
	// record references have title data associated.

	// here we want  list of detail values for this record grouped by detail Type
	// recordType can contain pseudoDetailTypes that are structural only information (relmarker, separator and fieldsetmarker)
	// since these will never be instantiated to a detail (data value) we don't need to worry about them here. Any detail will
	// will be of valid type and might not be in teh definition of the rec type (Heurist separates data storage for type definition).
	$res = mysql_query("select dtl_ID, dtl_DetailTypeID, dtl_Value, rec_Title, dtl_UploadedFileID, trm_Label,
	                           if(dtl_Geo is not null, astext(envelope(dtl_Geo)), null) as envelope,
	                           if(dtl_Geo is not null, astext(dtl_Geo), null) as dtl_Geo
	                      from recDetails
	                 left join defDetailTypes on dty_ID=dtl_DetailTypeID
	                 left join Records on rec_ID=dtl_Value and dty_Type='resource'
	                 left join defTerms on trm_ID = dtl_Value
	                     where dtl_RecID = $rec_id order by dtl_DetailTypeID, dtl_ID");
	$bibDetails = array();
	while ($row = mysql_fetch_assoc($res)) {
		$detail = array();

		$detail["id"] = $row["dtl_ID"];
		$detail["value"] = $row["dtl_Value"];
		if (array_key_exists('trm_Label',$row) && $row['trm_Label']) $detail["enumValue"] = $row["trm_Label"];	// saw Enum change
		if ($row["rec_Title"]) $detail["title"] = $row["rec_Title"];

		if ($row["dtl_UploadedFileID"]) {
			$fileRes = mysql_query("select * from recUploadedFiles where ulf_ID=" . intval($row["dtl_UploadedFileID"]));
			if (mysql_num_rows($fileRes) == 1) {
				$file = mysql_fetch_assoc($fileRes);
				$detail["file"] = array(
					"id" => $file["ulf_ID"],
					"origName" => $file["ulf_OrigFileName"],
					"date" => $file["ulf_ID"],
					"mimeType" => $file["file_mimetype"],	//	saw TODO lookup or delete
					"nonce" => $file["ulf_ObfuscatedFileID"],
					"fileSize" => $file["ulf_FileSizeKB"]
				);
			}
		}
		else if ($row["envelope"]  &&  preg_match("/^POLYGON[(][(]([^ ]+) ([^ ]+),[^,]*,([^ ]+) ([^,]+)/", $row["envelope"], $poly)) {
			list($match, $minX, $minY, $maxX, $maxY) = $poly;
//error_log($match);
			$x = 0.5 * ($minX + $maxX);
			$y = 0.5 * ($minY + $maxY);

			// This is a bit ugly ... but it is useful.
			// Do things differently for a path -- set minX,minY to the first point in the path, maxX,maxY to the last point
			if ($row["dtl_Value"] == "l"  &&  preg_match("/^LINESTRING[(]([^ ]+) ([^ ]+),.*,([^ ]+) ([^ ]+)[)]$/", $row["dtl_Geo"], $matches)) {
				list($dummy, $minX, $minY, $maxX, $maxY) = $matches;
			}

			switch ($row["dtl_Value"]) {
			  case "p": $type = "point"; break;
			  case "pl": $type = "polygon"; break;
			  case "c": $type = "circle"; break;
			  case "r": $type = "rectangle"; break;
			  case "l": $type = "path"; break;
			  default: $type = "unknown";
			}
			$wkt = $row["dtl_Value"] . " " . $row["dtl_Geo"];	// well-known text value
			$detail["geo"] = array(
				"minX" => $minX,
				"minY" => $minY,
				"maxX" => $maxX,
				"maxY" => $maxY,
				"x" => $x,
				"y" => $y,
				"type" => $type,
				"value" => $wkt
			);
		}

		if (! @$bibDetails[$row["dtl_DetailTypeID"]]) $bibDetails[$row["dtl_DetailTypeID"]] = array();
		array_push($bibDetails[$row["dtl_DetailTypeID"]], $detail);
	}

	return $bibDetails;
}


function getAllReminders($rec_id) {
	// Get any reminders as an array;
	if (! $rec_id) return array();

	// ... MYSTIFYINGLY these are stored by rec_ID+user_id, not bkm_ID
	$res = mysql_query("select * from usrReminders where rem_RecID=$rec_id and rem_OwnerUGrpID=".get_user_id()." order by rem_StartDate");

	$reminders = array();
	if (mysql_num_rows($res) > 0) {
		while ($rem = mysql_fetch_assoc($res)) {

			array_push($reminders, array(
				"id" => $rem["rem_ID"],
				"user" => $rem["rem_ToUserID"],
				"group" => $rem["rem_ToWorkGroupID"],
				"email" => $rem["rem_ToEmail"],
				"message" => $rem["rem_Message"],
				"when" => $rem["rem_StartDate"],
				"frequency" => $rem["rem_Freq"]
			));
		}
	}

	return $reminders;
}

function getAllWikis($rec_id, $bkm_ID) {
	// Get all wikis for this record / bookmark as an array/object

	$wikis = array();
	$wikiNames = array();

	if ($bkm_ID) {
		array_push($wikis, array("Private", "Bookmark:$bkm_ID"));
		array_push($wikiNames, "Bookmark:$bkm_ID");
	}

	if ($rec_id) {
		$res = mysql_query("select rec_URL from Records where rec_ID=".$rec_id);
		$row = mysql_fetch_assoc($res);
		if (preg_match("!(acl.arts.usyd.edu.au|heuristscholar.org)/tmwiki!", @$row["rec_URL"])) {	//FIXME: this needs to be configurable or generic for installations
			array_push($wikis, array("Public", preg_replace("!.*/!", "", $row["rec_URL"])));
			array_push($wikiNames, preg_replace("!.*/!", "", $row["rec_URL"]));
		} else {
			array_push($wikis, array("Public", "Biblio:$rec_id"));
			array_push($wikiNames, "Biblio:$rec_id");
		}

		$res = mysql_query("select grp.ugr_ID, grp.ugr_Name from ".USERS_DATABASE.".sysUsrGrpLinks left join ".USERS_DATABASE.".sysUGrps grp on ugl_GroupID=grp.ugr_ID where ugl_UserID=".get_user_id()." and grp.ugr_Type !='User' order by grp.ugr_Name");
		while ($grp = mysql_fetch_row($res)) {
			array_push($wikis, array(htmlspecialchars($grp[1]), "Biblio:".$rec_id."_Workgroup:".$grp[0]));
			array_push($wikiNames, slash("Biblio:".$rec_id."_Workgroup:".$grp[0]));
		}
	}

	// get a precis for each of the wikis we're dealing with	//FIXME:  tmwikidb is not used??
	$preces = mysql__select_assoc("tmwikidb.tmw_page left join tmwikidb.tmw_revision on rev_id=page_latest left join tmwikidb.tmw_text on old_id=rev_text_id", "page_title", "old_text", "page_title in ('" . join("','", $wikiNames) . "')");
	foreach ($wikis as $id => $wiki) {
		$precis = @$preces[$wiki[1]];	// look-up by wiki page name
		if (strlen($precis) > 100) $precis = substr($precis, 0, 100) . "...";
		array_push($wikis[$id], $precis? $precis : "");
	}

	return $wikis;
}

function getAllComments($rec_id) {
	$res = mysql_query("select cmt_ID, cmt_Deleted, cmt_Text, cmt_ParentCmtID, cmt_Added, cmt_Modified, cmt_OwnerUGrpID, concat(usr.ugr_FirstName,' ',usr.ugr_LastName) as Realname from recThreadedComments left join ".USERS_DATABASE.".sysUGrps usr on cmt_OwnerUGrpID=usr.ugr_ID where cmt_RecID = $rec_id order by cmt_Added");

	$comments = array();
	while ($cmt = mysql_fetch_assoc($res)) {
		if ($cmt["cmt_Deleted"]) {
			/* indicate that the comments exists but has been deleted */
			$comments[$cmt["cmt_ID"]] = array(
				"id" => $cmt["cmt_ID"],
				"owner" => $cmt["cmt_ParentCmtID"],
				"deleted" => true
			);
			continue;
		}

		$comments[$cmt["cmt_ID"]] = array(
			"id" => $cmt["cmt_ID"],
			"text" => $cmt["cmt_Text"],
			"owner" => $cmt["cmt_ParentCmtID"],	/* comments that owns this one (i.e. parent, just like in Dickensian times) */
			"added" => $cmt["cmt_Added"],
			"modified" => $cmt["cmt_Modified"],
			"user" => $cmt["Realname"],
			"userID" => $cmt["cmt_OwnerUGrpID"],
			"deleted" => false
		);
	}

	return $comments;
}

function getAllworkgroupTags($rec_id) {
// FIXME: should limit this just to workgroups that the user is in
	$res = mysql_query("select tag_ID from usrRecTagLinks, usrTags where rtl_TagID=tag_ID and rtl_RecID=$rec_id");
	$kwd_ids = array();
	while ($row = mysql_fetch_row($res)) array_push($kwd_ids, $row[0]);
	return $kwd_ids;
}

function attachChild($contIndex, $index, $terms) {
		if (!@count($terms[$index]) || $contIndex == $index) {
			return $terms;
		}
//error_log(" enter attach $contIndex, $index, ".print_r($terms,true));
		if (array_key_exists($index,$terms)) {
			if (count($terms[$index])) {
				foreach($terms[$index] as $cID => $n) {
					if ($cID != null) {
						$terms = attachChild($index,$cID, $terms);
// error_log(" after recurse $index, $cID, ".print_r($terms,true));
					}
				}
			}
//error_log(" attaching ".print_r($terms[$index],true));
//error_log(" to ".print_r($terms[$contIndex],true));
			$terms[$contIndex][$index] = $terms[$index];
			unset($terms[$index]);
		}
// error_log(" exit attach $contIndex, $index, ".print_r($terms,true));
		return $terms;
	}

function getTermTree($termDomain, $matching = 'exact') {	// vocabDomain can be empty, 'reltype' or 'enum' or any future term use domain defined in trm_Domain enum

	$whereClause = "a.trm_Domain ".($matching == 'prefix' ?  " like '".$termDomain."%' " :
									($matching == 'postfix' ?  " like '%".$termDomain."' " :
									"='".$termDomain."'"));
	$query = "select a.trm_ID as pID, b.trm_ID as cID
				from defTerms a
					left join defTerms b on a.trm_ID = b.trm_ParentTermID
				where $whereClause
				order by a.trm_Label, b.trm_Label";
	$res = mysql_query($query);
	$terms = array();
	while ($row = mysql_fetch_assoc($res)) {
		if (!@$terms[$row["pID"]]) {
			$terms[$row["pID"]] = array();
		}
		if ($row['cID']) {
				$terms[$row["pID"]][$row['cID']] = array();
		}
	}
	foreach ($terms as $pID => $cIDs) {
			foreach( $cIDs as $cID => $n ) {
				if ($cID != null && array_key_exists($cID,$terms)) {
					if (count($terms[$cID]) ) {
						$terms = attachChild($pID,$cID, $terms);
					}else{
						unset($terms[$cID]);
					}
				}
			}
	}
	return $terms;
}

function getTerms($useCachedData = false) {	// vocabDomain can be empty, 'reltype' or 'enum' or any future term use domain defined in trm_Domain enum
	$cacheKey = HEURIST_DBNAME.":getTerms";
	if ($useCachedData) {
		$terms = getCachedData($cacheKey);
		if ($terms) {
			return $terms;
		}
	}

	$query = "select trm_ID as ID, trm_Label as Term, trm_Description as Description, trm_InverseTermId as InverseId, if(trm_Domain like 'enum%', 'enum', 'relation') as Domain
				from defTerms
				order by Domain, trm_Label";
	$res = mysql_query($query);
	$terms = array('termsByDomainLookup' => array('relation'=> array(), 'enum'=> array()));
	while ($row = mysql_fetch_assoc($res)) {
			$terms['termsByDomainLookup'][$row["Domain"]][$row["ID"]] = array($row['Term'], $row['Description'],  $row['InverseId']);
	}
	$terms['treesByDomain'] = array('relation' => getTermTree("relation","prefix"),
									'enum' => getTermTree("enum","prefix"));
	setCachedData($cacheKey,$terms);
	return $terms;
}

/*
function getTermSets($termDomain) {	// termDomain can be empty, 'reltype' or 'enum' or any future term use domain defined in trm_Domain enum
	$query = "select a.trm_Label as pName, b.trm_Label as cName, a.trm_ID as pID, b.trm_ID as cID, b.trm_ChildCount as childCnt,
					if(a.trm_ParentTermID is null,'top',if(b.trm_ChildCount = 0,'bottom', 'middle')) as nodetype
				from defTerms a
					left join defTerms b on a.trm_ID = b.trm_ParentTermID
				where b.trm_ID is not null and not a.trm_ID = b.trm_ID
				".(!@$termDomain ? "" : " and a.trm_Domain like '".$termDomain."%'")."
				 order by a.trm_Label, b.trm_Label";
	$res = mysql_query($query);
	$terms = array();
	while ($row = mysql_fetch_assoc($res)) {
		if (!@$terms[$row["pID"]]) {
			$terms[$row["pID"]] = array('type'=>$row['nodetype'], 'name' => $row['pName']);
		}
		if ($row['cID']) {	// set term = highest of children node types
			$terms[$row["pID"]]['child'][$row['cID']] = intval($row['childCnt']);
			if($terms[$row["pID"]]['type'] == 'bottom' && $row['nodetype'] != 'bottom' ||
				$terms[$row["pID"]]['type'] == 'middle' && $row['nodetype'] == 'top') {
				$terms[$row["pID"]]['type'] = $row['nodetype'];
			}
		}
	}
	return $terms;
}
*/

function getRectypeConstraints($rectypeID) {
	$query = "select rcs_SourceRectypeID as srcID,
					rcs_TermID as trmID,
					rcs_TargetRectypeID as trgID,
					rcs_TermLimit as max,
					trm_Depth as level
				from defRelationshipConstraints
					left join defTerms on rcs_TermID = trm_ID
				".(@$rectypeID ? " where rcs_SourceRectypeID = $rectypeID or rcs_SourceRectypeID is null":"")."
				order by rcs_SourceRectypeID is null,
					rcs_SourceRectypeID,
					trm_Depth,
					rcs_TermID is null,
					rcs_TermID,
					rcs_TargetRectypeID is null,
					rcs_TargetRectypeID";
	$res = mysql_query($query);
	$cnstrnts = array();
	while ($row = mysql_fetch_assoc($res)) {
		$srcID = (@$row['srcID'] === null ? "".'0' : $row['srcID']);
		$trmID = (@$row['trmID'] === null ? "".'0' : $row['trmID']);
		$trgID = (@$row['trgID'] === null ? "".'0' : $row['trgID']);
		$max = (@$row['max'] === null ? '' : $row['max']);
		if (!@$cnstrnts[$srcID]) {
			$cnstrnts[$srcID] = array();
		}
		if (!@$cnstrnts[$srcID][$trmID]) {
			$cnstrnts[$srcID][$trmID] = array($trgID => $max);
		}else{
			$cnstrnts[$srcID][$trmID][$trgID] = $max;
		}
	}
	return $cnstrnts;
}

function getAllRectypeConstraint() {
	$query = "select rcs_SourceRectypeID as srcID,
					rcs_TermID as trmID,
					rcs_TargetRectypeID as trgID,
					rcs_TermLimit as max,
					trm_Depth as level,
					if(trm_ChildCount > 0, true, false) as hasCildren
				from defRelationshipConstraints
					left join defTerms on rcs_TermID = trm_ID
				order by rcs_SourceRectypeID is null,
					rcs_SourceRectypeID,
					trm_Depth,
					rcs_TermID is null,
					rcs_TermID,
					rcs_TargetRectypeID is null,
					rcs_TargetRectypeID";
	$res = mysql_query($query);
	$cnstrnts = array();
	while ($row = mysql_fetch_assoc($res)) {
		$srcID = (@$row['srcID'] === null ? "".'0' : $row['srcID']);
		$trmID = (@$row['trmID'] === null ? "".'0' : $row['trmID']);
		$trgID = (@$row['trgID'] === null ? "".'0' : $row['trgID']);
		$max = (@$row['max'] === null ? '' : $row['max']);
		if (!@$cnstrnts[$srcID]) {
			$cnstrnts[$srcID] = array();
		}
		if (!@$cnstrnts[$srcID][$trmID] || @$cnstrnts[$srcID][$trmID]['inheritCnstrnt']) {
			if(@$cnstrnts[$srcID][$trmID]['inheritCnstrnt']) {	// term has a defined constratin so override the inheritted
				unset($cnstrnts[$srcID][$trmID]);
		}
			$offspring = $trmID ? getTermOffspringList($trmID):null;
			$cnstrnts[$srcID][$trmID] = $offspring ? array('offspring' => $offspring, $trgID => $max):
																array($trgID => $max);
		}else{
			$cnstrnts[$srcID][$trmID][$trgID] = $max;
		}
		if (@$cnstrnts[$srcID][$trmID]['offspring']) {
			foreach ($cnstrnts[$srcID][$trmID]['offspring'] as $childTermID) { // point all offspring to inherit from term
				$cnstrnts[$srcID][$childTermID] = array('inheritCnstrnt' => $trmID);
			}
		}
	}
	return $cnstrnts;
}

// returns array list of all terms under a given term
function getTermOffspringList($termID) {
	$offspring = array();
	if ($termID) {
		$res = mysql_query("select * from defTerms where trm_ParentTermID = $termID");
		if (mysql_num_rows($res)) {	//child nodes exist
			while ($row = mysql_fetch_assoc($res)) { // for each child node
				$subTermID = $row['trm_ID'];
				array_push($offspring,$subTermID);
				if ($row['trm_ChildCount'] > 0) {
					$offspring = array_merge($offspring, getTermOffspringList($subTermID));
				}
			}
		}
	}
	return $offspring;
}

/*
// returns array subtree for a given term
function getPrunedTermSubtree($termID,$pruneIDs) {
	$subTree = "1";
	if ($termID) {
		$res = mysql_query("select * from defTerms where trm_ParentTermID = $termID");
		if (mysql_num_rows($res)) {	//child nodes exist
			$subTree = array();
			while ($row = mysql_fetch_assoc($res)) { // for each child node
				$subTermID = $row['trm_ID'];
				if (array_search($subTermID, $pruneIDs) !== false) { //prune match so stop recursion for this term
					$subTree[$subTermID] = "1";
				}else{
					$subTree[$subTermID] = getPrunedTermSubtree($subTermID, $pruneIDs);
				}
			}
		}
	}
	return $subTree;
}

*/

function getRectypeColNames(){
	return array("rty_Name",
					"rty_Description",
					"rty_Type",
					"rty_OrderInGroup",
					"rty_TitleMask",
					"rty_CanonicalTitleMask",
					"rty_Plural",
					"rty_ShowInLists",
					"rty_Status",
					"rty_RecTypeGroupIDs",
					"rty_FlagAsFieldset",
					"rty_ReferenceURL",
					"rty_AlternativeRecEditor");
}

function getRectypeDef($rt_id) {
	$rtDef = array();
	// get rec Structure info ordered by the detailType Group order, then by recStruct display order and then by ID in recStruct incase 2 have the same order
	$res = mysql_query("select ".join(",", getRectypeColNames())." from defRecTypes
							left join defRecTypeGroups  on rtg_ID = (select substring_index(rty_RecTypeGroupIDs,',',1))
							where rty_ID=$rt_id
							order by rtg_Order, rtg_Name, rty_OrderInGroup, rty_Name");
	$rtDef = mysql_fetch_row($res);
	return $rtDef;
}

function getRectypeStructureFieldColNames(){
	return array("rst_DisplayName",
					"rst_DisplayHelpText",
					"rst_DisplayExtendedDescription",
					"rst_DefaultValue",
					"rst_RequirementType",
					"rst_MaxValues",
					"rst_MinValues",
					"rst_DisplayWidth",
					"rst_RecordMatchOrder",
					"rst_DisplayOrder",
					"rst_DisplayDetailTypeGroupID",
					"rst_FilteredJsonTermIDTree",
					"rst_PtrFilteredIDs",
					"rst_TermIDTreeNonSelectableIDs",
					"rst_CalcFunctionID",
					"rst_Status",
					"rst_OrderForThumbnailGeneration",
					"dty_TermIDTreeNonSelectableIDs",
					"dty_FieldSetRectypeID");
}

function getRectypeFields($rt_id) {
	$rtfs = array();
	$colNames = array("rst_DetailTypeID",
						"if(rst_DisplayName is not null,rst_DisplayName,dty_Name) as rst_DisplayName",
						"if(rst_DisplayHelpText is not null,rst_DisplayHelpText,dty_HelpText) as rst_DisplayHelpText",
						"if(rst_DisplayExtendedDescription is not null,rst_DisplayExtendedDescription,dty_ExtendedDescription) as rst_DisplayExtendedDescription",
						"rst_DefaultValue",
						"rst_RequirementType",
						"rst_MaxValues",
						"rst_MinValues",
						"rst_DisplayWidth",
						"rst_RecordMatchOrder",
						"rst_DisplayOrder",
						"if(rst_DisplayDetailTypeGroupID is not null,rst_DisplayDetailTypeGroupID,dty_DetailTypeGroupID) as rst_DisplayDetailTypeGroupID",
						"if(rst_FilteredJsonTermIDTree is not null,rst_FilteredJsonTermIDTree,dty_JsonTermIDTree) as rst_FilteredJsonTermIDTree",
						"if(rst_PtrFilteredIDs is not null,rst_PtrFilteredIDs,dty_PtrTargetRectypeIDs) as rst_PtrFilteredIDs",
						"rst_TermIDTreeNonSelectableIDs",
						"rst_CalcFunctionID",
						"rst_Status",
						"rst_OrderForThumbnailGeneration",
						"dty_TermIDTreeNonSelectableIDs",
						"dty_FieldSetRectypeID");

	// get rec Structure info ordered by the detailType Group order, then by recStruct display order and then by ID in recStruct incase 2 have the same order
	$res = mysql_query("select ".join(",", $colNames)." from defRecStructure
															left join defDetailTypes on rst_DetailTypeID = dty_ID
															left join defDetailTypeGroups on dtg_ID = if(rst_DisplayDetailTypeGroupID is not null,rst_DisplayDetailTypeGroupID,dty_DetailTypeGroupID)
														where rst_RecTypeID=".$rt_id."
														order by dtg_Order, dtg_Name, rst_DisplayOrder, rst_ID");
	while ($row = mysql_fetch_row($res)) {
		$rtfs[$row[0]] = array_slice($row,1);
	}
	return $rtfs;
}

function getRectypeStructure($rtID) {
	$rectypesStructure = array();
	$rectypesStructure['commonFields'] = getRectypeDef($rtID);
	$rectypesStructure['dtFields'] = getRectypeFields($rtID);
	return $rectypesStructure;
}

// returns an array of RecType Structures for array of ids passed in
function getRectypeStructures($rt_ids) {
	$rtStructs = array('commomFieldNames' => getRectypeColNames(), 'dtFieldNames' => getRectypeStructureFieldColNames());
	foreach ($rt_ids as $rt_id) {
		$rtStructs[$rt_id] = getRectypeStructure($rt_id);
	}
	return $rtStructs;
}

// returns an array of RecType Structures for all RecTypes
function getAllRectypeStructures($useCachedData = false) {
	$cacheKey = HEURIST_DBNAME.":AllRecTypeInfo";
	if ($useCachedData) {
		$rtStructs = getCachedData($cacheKey);
		if ($rtStructs) {
			return $rtStructs;
		}
	}

	$colNames = array("rst_RecTypeID",
						"rst_DetailTypeID",
						"if(rst_DisplayName is not null,rst_DisplayName,dty_Name) as rst_DisplayName",
						"if(rst_DisplayHelpText is not null,rst_DisplayHelpText,dty_HelpText) as rst_DisplayHelpText",
						"if(rst_DisplayExtendedDescription is not null,rst_DisplayExtendedDescription,dty_ExtendedDescription) as rst_DisplayExtendedDescription",
						"rst_DefaultValue",
						"rst_RequirementType",
						"rst_MaxValues",
						"rst_MinValues",
						"rst_DisplayWidth",
						"rst_RecordMatchOrder",
						"rst_DisplayOrder",
						"if(rst_DisplayDetailTypeGroupID is not null,rst_DisplayDetailTypeGroupID,dty_DetailTypeGroupID) as rst_DisplayDetailTypeGroupID",
						"if(rst_FilteredJsonTermIDTree is not null,rst_FilteredJsonTermIDTree,dty_JsonTermIDTree) as rst_FilteredJsonTermIDTree",
						"if(rst_PtrFilteredIDs is not null,rst_PtrFilteredIDs,dty_PtrTargetRectypeIDs) as rst_PtrFilteredIDs",
						"rst_TermIDTreeNonSelectableIDs",
						"rst_CalcFunctionID",
						"rst_Status",
						"rst_OrderForThumbnailGeneration",
						"dty_TermIDTreeNonSelectableIDs",
						"dty_FieldSetRectypeID");

	// get rec Structure info ordered by the detailType Group order, then by recStruct display order and then by ID in recStruct incase 2 have the same order
	$query = "select ".join(",", $colNames)." from defRecStructure
					left join defDetailTypes on rst_DetailTypeID = dty_ID
					left join defDetailTypeGroups on
		dtg_ID = if(rst_DisplayDetailTypeGroupID is not null,rst_DisplayDetailTypeGroupID,dty_DetailTypeGroupID)
				order by rst_RecTypeID, dtg_Order, dtg_Name,  rst_DisplayOrder, rst_ID";


	$res = mysql_query($query);
	$rtStructs = array('groups' => getRectypeGroups(),
						'names' => array(),
						'pluralNames' => array(),
						'usageCount' => getRecTypeUsageCount(),
						'dtDisplayOrder' => array());
	$rtStructs['typedefs'] = array('commomFieldNames' => getRectypeColNames(), 'dtFieldNames' => getRectypeStructureFieldColNames());
	while ($row = mysql_fetch_row($res)) {
		if (!array_key_exists($row[0],$rtStructs['typedefs'])) {
			$rtStructs['typedefs'][$row[0]] = array('dtFields' => array($row[1] => array_slice($row,2)));
			$rtStructs['dtDisplayOrder'][$row[0]] = array();
		}else{
			$rtStructs['typedefs'][$row[0]]['dtFields'][$row[1]] = array_slice($row,2);
		}
		array_push($rtStructs['dtDisplayOrder'][$row[0]],$row[1]);
	}

	// get rec Structure info ordered by the detailType Group order, then by recStruct display order and then by ID in recStruct incase 2 have the same order
	$res = mysql_query("select rty_ID, rtg_ID, rtg_Name, ".join(",", getRectypeColNames())." from defRecTypes
							left join defRecTypeGroups  on rtg_ID = substring_index(rty_RecTypeGroupIDs,',',1)
							order by rtg_Order, rtg_Name, rty_OrderInGroup, rty_Name");

	while ($row = mysql_fetch_row($res)) {
		if (!array_key_exists($row[1],$rtStructs['groups'])) {
			$rtStructs['groups'][$row[1]] = array('types'=> array($row[0] => $row[10]), 'name' => $row[2]);
		}else{
			$rtStructs['groups'][$row[1]]['types'][$row[0]] = $row[10];
		}
		$rtStructs['typedefs'][$row[0]]['commonFields'] = array_slice($row,3);
		$rtStructs['names'][$row[0]] = $row[3];
		$rtStructs['pluralNames'][$row[0]] = $row[9];
	}
	$rtStructs['constraints'] = getAllRectypeConstraint();
	setCachedData($cacheKey,$rtStructs);
	return $rtStructs;
}

function getRectypeGroups() {
	$rtGroups = array();
	$res = mysql_query("select * from defRecTypeGroups order by rtg_Order, rtg_Name");
	while ($row = mysql_fetch_assoc($res)) {
		$rtGroups[$row["rtg_ID"]] = array('name' => $row["rtg_Name"], 'description' => $row["rtg_Description"]);
	}
	return $rtGroups;
}

function getRecTypesByGroup() {
	$rectypesByGroup = array();
	// query assumes rty_RecTypeGroupID is ordered isngle functional group ID followed by zero or more model group ids
	$res = mysql_query("select rtg_ID,rtg_Name,rty_ID, rty_ShowInLists
							from defRecTypes left join defRecTypeGroups  on rtg_ID = substring_index(rty_RecTypeGroupIDs,',',1)
							where 1 order by rtg_Order, rtg_Name, rty_OrderInGroup, rty_Name");
	while ($row = mysql_fetch_assoc($res)) {
		if (!array_key_exists($row['rtg_ID'],$rectypesByGroup)){
			$rectypesByGroup[$row['rtg_ID']] = array('name'=>$row["rtg_Name"],'types'=>array());
		}
		$rectypesByGroup[$row['rtg_ID']]['types'][$row["rty_ID"]] = $row["rty_ShowInLists"];
	}
	return $rectypesByGroup;
}

function getDetailTypeDefUsage() {
	$rectypesByDetailType = array();
	$res = mysql_query("select rst_DetailTypeID as dtID, rst_RecTypeID as rtID
						from defRecStructure order by dtID, rtID");
	while ($row = mysql_fetch_assoc($res)) {
		if (!array_key_exists($row['dtID'],$rectypesByDetailType)){
			$rectypesByDetailType[$row['dtID']] = array();
		}
		array_push($rectypesByDetailType[$row['dtID']],$row["rtID"]);
	}
	return $rectypesByDetailType;
}


function getRecTypeUsageCount() {
	$recCountByRecType = array();
	$res = mysql_query("select rty_ID as rtID, count(rec_ID) as usageCnt
						from Records left join defRecTypes on rty_ID = rec_RecTypeID
						group by rec_RecTypeID");
	while ($row = mysql_fetch_assoc($res)) {
		$recCountByRecType[$row['rtID']] = $row["usageCnt"];
	}
	return $recCountByRecType;
}


function getDetailTypeUsageCount() {
	$recDetailsByDetailType = array();
	$res = mysql_query("select dty_ID as dtID, count(dtl_ID) as usageCnt
						from recDetails left join defDetailTypes on dty_ID = dtl_DetailTypeID
						group by dtl_DetailTypeID");
	while ($row = mysql_fetch_assoc($res)) {
		$recDetailsByDetailType[$row['dtID']] = $row["usageCnt"];
	}
	return $recDetailsByDetailType;
}


function getDetailTypeColNames() {
	return array("dty_Name",
					"dty_ExtendedDescription",
					"dty_Type",
					"dty_OrderInGroup",
					"dty_HelpText",
					"dty_ShowInLists",
					"dty_Status",
					"dty_DetailTypeGroupID",
					"dty_FieldSetRectypeID",
					"dty_JsonTermIDTree",
					"dty_TermIDTreeNonSelectableIDs",
					"dty_PtrTargetRectypeIDs",
					"dty_ID");
}

// returns an array of RecType Structures for all RecTypes
function getAllDetailTypeStructures($useCachedData = false) {
	$cacheKey = HEURIST_DBNAME.":AllDetailTypeInfo";
	if ($useCachedData) {
		$dtStructs = getCachedData($cacheKey);
		if ($dtStructs) {
			return $dtStructs;
		}
	}

	$dtStructs = array('groups' => getDetailTypeGroups(),
						'names' => array(),
						'rectypeUsage' => getDetailTypeDefUsage(),
						'usageCount' => getDetailTypeUsageCount(),
						'typedefs' => array('commomFieldNames' => getDetailTypeColNames()));

	$query = "select dty_ID, dtg_ID, dtg_Name, ".join(",", getDetailTypeColNames())." from defDetailTypes
							left join defDetailTypeGroups  on dtg_ID = dty_DetailTypeGroupID
							order by dtg_Order, dtg_Name, dty_OrderInGroup, dty_Name";

	$res = mysql_query($query);


	while ($row = mysql_fetch_row($res)) {
			$dtStructs['groups'][$row[1]]['types'][$row[0]] = $row[8];
		$dtStructs['typedefs'][$row[0]]['commonFields'] = array_slice($row,3);
		$dtStructs['names'][$row[0]] = $row[3];
	}
	setCachedData($cacheKey,$dtStructs);
	return $dtStructs;
}

function getDetailTypeDef($dtID) {
	$dtDef = array();
	// get rec Structure info ordered by the detailType Group order, then by recStruct display order and then by ID in recStruct incase 2 have the same order
	$res = mysql_query("select ".join(",", getDetailTypeColNames())." from defDetailTypes
							left join defDetailTypeGroups  on dtg_ID = dty_DetailTypeGroupID
							where dty_ID=$dtID
							order by dtg_Order, dtg_Name, dty_OrderInGroup, dty_Name");
	$dtDef = mysql_fetch_row($res);
	return $dtDef;
}

function getDetailTypeStructure($dtID) {
	$detailTypesStructure = array();
	$detailTypesStructure['commonFields'] = getDetailTypeDef($dtID);
	return $detailTypesStructure;
}

// returns an array of DetailType Structures for array of ids passed in
function getDetailTypeStructures($dtIDs) {
	$dtStructs = array('commomFieldNames' => getDetailTypeColNames());
	foreach ($dtIDs as $dtID) {
		$dtStructs[$dtID] = getDetailTypeStructure($dtID);
	}
	return $dtStructs;
}

function getDetailTypeGroups() {
	$dtGroups = array();
	$res = mysql_query("select * from defDetailTypeGroups order by dtg_Order, dtg_Name");
	while ($row = mysql_fetch_assoc($res)) {
		$dtGroups[$row["dtg_ID"]] = array( 'name' => $row["dtg_Name"], 'description' => $row["dtg_Description"] );
	}
	return $dtGroups;
}

function getDetailTypesByGroup() {
	$detailTypesByGroup = array();
	// query assumes rty_RecTypeGroupID is ordered isngle functional group ID followed by zero or more model group ids
	$res = mysql_query("select dtg_ID,dtg_Name,dty_ID, dty_ShowInLists
							from defDetailTypes left join defDetailTypeGroups  on dtg_ID = dty_DetailTypeGroupID
							where 1 order by dtg_Order, dtg_Name, dty_OrderInGroup, dty_Name");
	while ($row = mysql_fetch_assoc($res)) {
		if (!array_key_exists($row['dtg_ID'],$detailTypesByGroup)){
			$detailTypesByGroup[$row['dtg_ID']] = array('name'=>$row["dtg_Name"],'types'=>array());
		}
		$detailTypesByGroup[$row['dtg_ID']]['types'][$row["dty_ID"]] = $row["dty_ShowInLists"];
	}
	return $detailTypesByGroup;
}

function reltype_inverse ($relTermID) {	//saw Enum change - find inverse as an id instead of a string
	global $inverses;
	if (!$relTermID) return;
	if (! $inverses) {
		//		$inverses = mysql__select_assoc("defTerms A left join defTerms B on B.trm_ID=A.trm_InverseTermID", "A.trm_Label", "B.trm_Label", "A.rdl_rdt_id=200 and A.trm_Label is not null");
		$inverses = mysql__select_assoc("defTerms A left join defTerms B on B.trm_ID=A.trm_InverseTermID", "A.trm_ID", "B.trm_ID", "A.trm_Label is not null and B.trm_Label is not null");
	}

	$inverse = @$inverses[$relTermID];
	if (!$inverse)
	$inverse = array_search($relTermID, $inverses);
	if (!$inverse)
	$inverse = 'Inverse of '.$relTermID;

	return $inverse;
}

function fetch_relation_details($recID, $i_am_primary) {
	/* Raid recDetails for the given link resource and extract all the necessary values */

	$res = mysql_query('select * from recDetails where dtl_RecID = ' . $recID);
	$bd = array('recID' => $recID);
	while ($row = mysql_fetch_assoc($res)) {
		switch ($row['dtl_DetailTypeID']) {
			case 200:	//saw Enum change - added RelationValue for UI
				if ($i_am_primary) {
					$bd['relTermID'] = $row['dtl_Value'];
				}else{
					$bd['relTermID'] = reltype_inverse($row['dtl_Value']);
				}
				$relval = mysql_fetch_assoc(mysql_query('select trm_Label, trm_ParentTermID from defTerms where trm_ID = ' .  intval($bd['RelTermID'])));
				$bd['relTerm'] = $relval['trm_Label'];
				if ($relval['trm_ParentTermID'] ) {
					$bd['parentTermID'] = $relval['trm_ParentTermID'];
				}
				break;

			case 199:	// linked resource
				if (! $i_am_primary) break;
				$r = mysql_query('select rec_ID as recID, rec_Title as title, rec_RecTypeID as rectype, rec_URL as URL
				from Records where rec_ID = ' . intval($row['dtl_Value']));
				$bd['relatedRecID'] = mysql_fetch_assoc($r);
				break;

			case 202:
				if ($i_am_primary) break;
				$r = mysql_query('select rec_ID as recID, rec_Title as title, rec_RecTypeID as rectype, rec_URL as URL
				from Records where rec_ID = ' . intval($row['dtl_Value']));
				$bd['relatedRecID'] = mysql_fetch_assoc($r);
				break;

			case 638:
				$r = mysql_query('select rec_ID as recID, rec_Title as title, rec_RecTypeID as rectype, rec_URL as URL
				from Records where rec_ID = ' . intval($row['dtl_Value']));
				$bd['interpRecID'] = mysql_fetch_assoc($r);
				break;

			case 201:
				$bd['notes'] = $row['dtl_Value'];
				break;

			case 160:
				$bd['title'] = $row['dtl_Value'];
				break;

			case 177:
				$bd['startDate'] = $row['dtl_Value'];
				break;

			case 178:
				$bd['endDate'] = $row['dtl_Value'];
				break;
		}
	}

	return $bd;
}
function getAllRelatedRecords($recID, $relnRecID=0) {
	if (! $recID) return null;
	$query = "select relnID,
					src.dtl_Value as src,
					srcRec.rec_RecTypeID as srcRT,
					srcRec.rec_Title as srcTitle,
					srcRec.rec_URL as srcURL,
					trg.dtl_Value as trg,
					if(srcRec.rec_ID = $recID, 'Primary', 'Non-Primary') as role,
					trgRec.rec_RecTypeID as trgRT,
					trgRec.rec_Title as trgTitle,
					trgRec.rec_URL as trgURL,
					trm.dtl_Value as trmID,
					term.trm_Label as term,
					inv.trm_ID as invTrmID,
					if(inv.trm_ID, inv.trm_Label, concat('inverse of ', term.trm_Label)) as invTrm,
					rlnTtl.dtl_Value as title,
					rlnNote.dtl_Value as note,
					strDate.dtl_Value as strDate,
					endDate.dtl_Value as endDate,
					intrpRec.rec_ID as intrp,
					intrpRec.rec_RecTypeID as intrpRT,
					intrpRec.rec_Title as intrpTitle,
					intrpRec.rec_URL as intrpURL
				from (select rrc_RecID as relnID from recRelationshipsCache) rels
					left join recDetails src on src.dtl_RecID = rels.relnID and src.dtl_DetailTypeID = 202
					left join Records srcRec on src.dtl_Value = srcRec.rec_ID
					left join recDetails trg on trg.dtl_RecID = rels.relnID and trg.dtl_DetailTypeID = 199
					left join Records trgRec on trg.dtl_Value = trgRec.rec_ID
					left join recDetails trm on trm.dtl_RecID = rels.relnID and trm.dtl_DetailTypeID = 200
					left join defTerms term on term.trm_ID = trm.dtl_Value
					left join defTerms inv on inv.trm_ID = term.trm_InverseTermID
					left join recDetails intrp on intrp.dtl_RecID = rels.relnID and intrp.dtl_DetailTypeID = 638
					left join Records intrpRec on intrp.dtl_Value = intrpRec.rec_ID
					left join recDetails rlnTtl on rlnTtl.dtl_RecID = rels.relnID and rlnTtl.dtl_DetailTypeID = 160
					left join recDetails rlnNote on rlnNote.dtl_RecID = rels.relnID and rlnNote.dtl_DetailTypeID = 201
					left join recDetails strDate on strDate.dtl_RecID = rels.relnID and strDate.dtl_DetailTypeID = 177
					left join recDetails endDate on endDate.dtl_RecID = rels.relnID and endDate.dtl_DetailTypeID = 178
				where (srcRec.rec_ID = $recID or trgRec.rec_ID = $recID)";
	if ($relnRecID) $query .= " and rels.relnID = $relnRecID";


	//error_log($query);
	$res = mysql_query($query);	/* primary resources first, then non-primary, then authors */

	if (!mysql_num_rows($res)) {
		return array();
	}
	$relations = array('relationshipRecs' => array());
	while ($row = mysql_fetch_assoc($res)) {
		$relnRecID = $row["relnID"];

		$relations['relationshipRecs'][$relnRecID] = array( "relnID" => $relnRecID,
															"title" => $row['title'],
															"recID" => $recID,
															"role" => $row['role'],
															"relTermID" => $row['trmID'],
															"relTerm" => $row['term'],
															"relInvTerm" => $row['invTrm']);

		if (@$row['invTrmID']) {
			$relations['relationshipRecs'][$relnRecID]["relInvTermID"] = $row['invTrmID'];
		}

		if (@$row['note']) {
			$relations['relationshipRecs'][$relnRecID]["notes"] = $row['note'];
		}

		if (@$row['strDate']) {
			$relations['relationshipRecs'][$relnRecID]["startDate"] = $row['strDate'];
		}

		if (@$row['endDate']) {
			$relations['relationshipRecs'][$relnRecID]["endDate"] = $row['endDate'];
		}

		if (@$row['intrp']) {
			$relations['relationshipRecs'][$relnRecID]["interpRec"] = array("title" => $row["intrpTitle"],
																			"rectype" => $row["intrpRT"],
																			"URL" => $row["intrpURL"],
																			"recID" => $row["intrp"]);
		}

		if ($row['src'] == $recID) {
			$relations['relationshipRecs'][$relnRecID]["relatedRec"] = array("title" => $row["trgTitle"],
																				"rectype" => $row["trgRT"],
																				"URL" => $row["trgURL"],
																				"recID" => $row["trg"]);
		} else {
			$relations['relationshipRecs'][$relnRecID]["relatedRec"] = array("title" => $row["srcTitle"],
																				"rectype" => $row["srcRT"],
																				"URL" => $row["srcURL"],
																				"recID" => $row["src"]);
		}
	}

	foreach ($relations['relationshipRecs'] as $relnRecID => $reln) {
		$relRT = $reln['relatedRec']['rectype'];
		$relRecID = $reln['relatedRec']['recID'];
		$relTermID = $reln['relTermID'];
		if (!array_key_exists('byRectype',$relations)) {
			$relations['byRectype'] = array();
		}
		if (!array_key_exists($relRT,$relations['byRectype'])) {
			$relations['byRectype'][$relRT] = array();
		}
		if (!array_key_exists($relTermID,$relations['byRectype'][$relRT])) {
			$relations['byRectype'][$relRT][$relTermID] = array($relnRecID);
		} else {
			array_push($relations['byRectype'][$relRT][$relTermID],$relnRecID);
		}
		if (!array_key_exists('byTerm',$relations)) {
			$relations['byTerm'] = array();
		}
		if (!array_key_exists($relTermID,$relations['byTerm'])) {
			$relations['byTerm'][$relTermID] = array();
		}
		if (!array_key_exists($relRT,$relations['byTerm'][$relTermID])) {
			$relations['byTerm'][$relTermID][$relRT] = array($relnRecID);
		} else {
			array_push($relations['byTerm'][$relTermID][$relRT],$relnRecID);
		}
	}

	return $relations;
}

/*
function getAllRelatedRecords2($recID, $relnRecID=0) {
	if (! $recID) return null;
	$query = "select LINK.dtl_DetailTypeID as type, DETAILS.*, DBIB.rec_Title as title,
	DBIB.rec_RecTypeID as rt, DBIB.rec_URL as url
	from recDetails LINK left join Records LBIB on LBIB.rec_ID=LINK.dtl_RecID,
	recDetails DETAILS left join Records DBIB on DBIB.rec_ID=DETAILS.dtl_Value and
	DETAILS.dtl_DetailTypeID in (202, 199, 158)
	where ((LINK.dtl_DetailTypeID in (202, 199) and LBIB.rec_RecTypeID=52)
	or LINK.dtl_DetailTypeID=158) and LINK.dtl_Value = $recID and DETAILS.dtl_RecID = LINK.dtl_RecID";
	if ($relnRecID) $query .= " and DETAILS.dtl_RecID = $relnRecID";

	$query .= " order by LINK.dtl_DetailTypeID desc, DETAILS.dtl_ID";

	//error_log($query);
	$res = mysql_query($query);	// primary resources first, then non-primary, then authors

	if (!mysql_num_rows($res)) {
		return array();
	}
	$relations = array('relationshipRecs' => array());
	while ($row = mysql_fetch_assoc($res)) {
		$relnRecID = $row["dtl_RecID"];
		$i_am_primary = ($row["type"] == 202);
		if (! array_key_exists($relnRecID, $relations['relationshipRecs'])){
			$relations['relationshipRecs'][$relnRecID] = array();
		}

		if (! array_key_exists("role", $relations['relationshipRecs'][$relnRecID])) {
			if ($row["type"] == 202) {
				$relations['relationshipRecs'][$relnRecID]["role"] = "Primary";
			} else if ($row["type"] == 199) {
				$relations['relationshipRecs'][$relnRecID]["role"] = "Non-primary";
			} else {
				$relations['relationshipRecs'][$relnRecID]["role"] = "Unknown";
			}
		}
		if (! array_key_exists("recID", $relations['relationshipRecs'][$relnRecID])) {
			$relations['relationshipRecs'][$relnRecID]["recID"] = $recID;
		}

		switch ($row["dtl_DetailTypeID"]) {
			case 200:	//saw Enum change - nothing to do since dtl_Value is an id and inverse returns an id
				$relations['relationshipRecs'][$relnRecID]["relTermID"] = $i_am_primary? $row["dtl_Value"] : reltype_inverse($row["dtl_Value"]);
				if($relations['relationshipRecs'][$relnRecID]["relTermID"]) {
					$relval = mysql_fetch_assoc(mysql_query('select trm_Label from defTerms where trm_ID = ' .  intval($relations['relationshipRecs'][$relnRecID]["relTermID"])));
					$relations['relationshipRecs'][$relnRecID]['relTerm'] = $relval['trm_Label'];
				}
				break;

			case 199:
			case 202:
				if ( $row["dtl_Value"] !=  $recID) {
					$relations['relationshipRecs'][$relnRecID]["relatedRec"] = array("title" => $row["title"],
																					"rectype" => $row["rt"],
																					"URL" => $row["url"],
																					"recID" => $row["dtl_Value"]);
				}
				break;

			case 638:
			$relations['relationshipRecs'][$relnRecID]["interpRec"] = array("title" => $row["title"],
																			"rectype" => $row["rt"],
																			"URL" => $row["url"],
																			"recID" => $row["dtl_Value"]);
			break;

			case 201:
			$relations['relationshipRecs'][$relnRecID]["notes"] = $row["dtl_Value"];
			break;

			case 160:
			$relations['relationshipRecs'][$relnRecID]["title"] = $row["dtl_Value"];
			break;

			case 177:
			$relations['relationshipRecs'][$relnRecID]["startDate"] = $row["dtl_Value"];
			break;

			case 178:
			$relations['relationshipRecs'][$relnRecID]["endDate"] = $row["dtl_Value"];
			break;
		}

	}
	foreach ($relations['relationshipRecs'] as $relnRecID => $reln) {
		$relRT = $reln['relatedRec']['rectype'];
		$relRecID = $reln['relatedRec']['recID'];
		$relTermID = $reln['relTermID'];
		if (!array_key_exists('byRectype',$relations)) {
			$relations['byRectype'] = array();
		}
		if (!array_key_exists($relRT,$relations['byRectype'])) {
			$relations['byRectype'][$relRT] = array();
		}
		if (!array_key_exists($relTermID,$relations['byRectype'][$relRT])) {
			$relations['byRectype'][$relRT][$relTermID] = array($relRecID);
		} else {
			array_push($relations['byRectype'][$relRT][$relTermID],$relRecID);
		}
		if (!array_key_exists('byTerm',$relations)) {
			$relations['byTerm'] = array();
		}
		if (!array_key_exists($relTermID,$relations['byTerm'])) {
			$relations['byTerm'][$relTermID] = array();
		}
		if (!array_key_exists($relRT,$relations['byTerm'][$relTermID])) {
			$relations['byTerm'][$relTermID][$relRT] = array($relRecID);
		} else {
			array_push($relations['byTerm'][$relTermID][$relRT],$relRecID);
		}
	}

	return $relations;
}
*/
	/*no carriage returns after closing script tags please, it breaks xml script genenerator that uses this file as include */
?>