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

	/*
	$inverses = array();
	$inverses['Causes'] = 'IsCausedBy';
	$inverses['CollaboratesWith'] = 'CollaboratesWith';
	$inverses['CooperatesWith'] = 'CooperatesWith';
	$inverses['Funds'] = 'IsFundedBy';
	$inverses['HasAssociateDirector'] = 'IsAssociateDirectorOf';
	$inverses['HasAssociatePartner'] = 'IsAssociatePartnerIn';
	$inverses['HasAuthor'] = 'IsAuthorOf';
	$inverses['HasCoAuthor'] = 'IsCoAuthorOf';
	$inverses['HasCoConvenor'] = 'IsCoConvenerOf';
	$inverses['HasConvenor'] = 'IsConvenerOf';
	$inverses['HasDirector'] = 'IsDirectorOf';
	$inverses['HasHost'] = 'IsHostOf';
	$inverses['HasManager'] = 'IsManagerOf';
	$inverses['HasMember'] = 'IsMemberOf';
	$inverses['HasNodeDirector'] = 'IsNodeDirectorOf';
	$inverses['HasPartner'] = 'IsPartnerIn';
	$inverses['HasPhotograph'] = 'IsPhotographOf';
	$inverses['HasSpeaker'] = 'IsSpeakerAt';
	$inverses['HasSubNode'] = 'IsSubNodeOf';
	$inverses['IsOwnedBy'] = 'Owns';
	$inverses['IsParentOf'] = 'IsPartOf';
	$inverses['IsReferencedBy'] = 'References';
	$inverses['IsRelatedTo'] = 'IsRelatedTo';
	$inverses['IsSameAs'] = 'IsSameAs';
	$inverses['IsSimilarTo'] = 'IsSimilarTo';
	$inverses['IsUsedBy'] = 'Uses';
	*/

	$ranks = array();
	$ranks['IsAssociateDirectorOf'] = 3;
	$ranks['IsAssociatePartnerIn'] = 3;
	$ranks['IsDirectorOf'] = 3;
	$ranks['IsManagerOf'] = 3;
	$ranks['IsMemberOf'] = 3;
	$ranks['IsNodeDirectorOf'] = 3;

	$ranks['IsFundedBy'] = 2;
	$ranks['IsHostOf'] = 2;
	$ranks['IsPartOf'] = 2;

	$ranks['IsSpeakerAt'] = 1;
	$ranks['IsSubNodeOf'] = 1;

	/*
	$ranks['Causes'] = 0;
	$ranks['CollaboratesWith'] = 0;
	$ranks['CooperatesWith'] = 0;
	$ranks['Funds'] = 0;
	$ranks['HasAssociateDirector'] = 0;
	$ranks['HasAssociatePartner'] = 0;
	$ranks['HasAuthor'] = 0;
	$ranks['HasCoAuthor'] = 0;
	$ranks['HasCoConvenor'] = 0;
	$ranks['HasConvenor'] = 0;
	$ranks['HasDirector'] = 0;
	$ranks['HasHost'] = 0;
	$ranks['HasManager'] = 0;
	$ranks['HasMember'] = 0;
	$ranks['HasNodeDirector'] = 0;
	$ranks['HasPartner'] = 0;
	$ranks['HasSpeaker'] = 0;
	$ranks['HasSubNode'] = 0;
	$ranks['IsAssociateDirectorOf'] = 0;
	$ranks['IsAssociatePartnerIn'] = 0;
	$ranks['IsAuthorOf'] = 0;
	$ranks['IsCausedBy'] = 0;
	$ranks['IsCoAuthorOf'] = 0;
	$ranks['IsCoConvenerOf'] = 0;
	$ranks['IsConvenerOf'] = 0;
	$ranks['IsDirectorOf'] = 0;
	$ranks['IsFundedBy'] = 0;
	$ranks['IsHostOf'] = 0;
	$ranks['IsManagerOf'] = 0;
	$ranks['IsMemberOf'] = 0;
	$ranks['IsNodeDirectorOf'] = 0;
	$ranks['IsOwnedBy'] = 0;
	$ranks['IsParentOf'] = 0;
	$ranks['IsPartnerIn'] = 0;
	$ranks['IsPartOf'] = 0;
	$ranks['IsReferencedBy'] = 0;
	$ranks['IsRelatedTo'] = 0;
	$ranks['IsSameAs'] = 0;
	$ranks['IsSimilarTo'] = 0;
	$ranks['IsSpeakerAt'] = 0;
	$ranks['IsSubNodeOf'] = 0;
	$ranks['IsUsedBy'] = 0;
	$ranks['Owns'] = 0;
	$ranks['References'] = 0;
	$ranks['Uses'] = 0;
	*/

	/**
	* saw Enum change - find inverse as an id instead of a string
	*
	* @param mixed $relTermID
	* @return mixed
	*/
	function reltype_inverse ($relTermID) {
		global $inverses;

		if (!$relTermID) return;

		if (! $inverses) {
			//		$inverses = mysql__select_assoc("defTerms A left join defTerms B on B.trm_ID=A.trm_InverseTermID", "A.trm_Label", "B.trm_Label", "A.rdl_rdt_id=200 and A.trm_Label is not null");
			$inverses = mysql__select_assoc("defTerms A left join defTerms B on B.trm_ID=A.trm_InverseTermID", "A.trm_ID", "B.trm_ID", "A.trm_Label is not null and B.trm_Label is not null");
		}

		$inverse = @$inverses[$relTermID];
		if (!$inverse){
			$inverse = array_search($relTermID, $inverses);
		}
		if (!$inverse){
			$inverse = 'Inverse of '.$relTermID;
		}

		return $inverse;
	}

	/**
	*
	*
	* @param mixed $reltype
	* @return mixed
	*/
	function reltype_rank ($reltype)
	{
		global $ranks;
		$rank = $ranks[$reltype];
		if ($rank)
			return $rank;
		else
			return 0;
	}

	$relRT = (defined('RT_RELATION')?RT_RELATION:0);
	$relTypDT = (defined('DT_RELATION_TYPE')?DT_RELATION_TYPE:0);
	$relSrcDT = (defined('DT_PRIMARY_RESOURCE')?DT_PRIMARY_RESOURCE:0);
	$relTrgDT = (defined('DT_TARGET_RESOURCE')?DT_TARGET_RESOURCE:0);
	$intrpDT = (defined('DT_INTERPRETATION_REFERENCE')?DT_INTERPRETATION_REFERENCE:0);
	$notesDT = (defined('DT_SHORT_SUMMARY')?DT_SHORT_SUMMARY:0);
	$startDT = (defined('DT_START_DATE')?DT_START_DATE:0);
	$endDT = (defined('DT_END_DATE')?DT_END_DATE:0);
	$titleDT = (defined('DT_NAME')?DT_NAME:0);


	/*
	*		Raid recDetails for the given link resource and extract all the necessary values
	*/
	function fetch_relation_details($recID, $i_am_primary)
	{
		global $relTypDT,$relSrcDT,$relTrgDT,$intrpDT,$notesDT,$startDT,$endDT,$titleDT, $relRT;

		$res = mysql_query('select * from recDetails where dtl_RecID = ' . $recID);
		$bd = array('recID' => $recID);
		while ($row = mysql_fetch_assoc($res)) {
			switch ($row['dtl_DetailTypeID']) {
				case $relTypDT:	//saw Enum change - added RelationValue for UI

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

				case $relTrgDT:	// linked resource
				if (! $i_am_primary) break;
						$r = mysql_query('select rec_ID as recID, rec_Title as title, rec_RecTypeID as rectype, rec_URL as URL
											from Records where rec_ID = ' . intval($row['dtl_Value']));
						$bd['relatedRecID'] = mysql_fetch_assoc($r);
				break;

				case $relSrcDT:
				if ($i_am_primary) break;
						$r = mysql_query('select rec_ID as recID, rec_Title as title, rec_RecTypeID as rectype, rec_URL as URL
											from Records where rec_ID = ' . intval($row['dtl_Value']));
						$bd['relatedRecID'] = mysql_fetch_assoc($r);
				break;

				case $intrpDT:
						$r = mysql_query('select rec_ID as recID, rec_Title as title, rec_RecTypeID as rectype, rec_URL as URL
											from Records where rec_ID = ' . intval($row['dtl_Value']));
						$bd['interpRecID'] = mysql_fetch_assoc($r);
				break;

				case $notesDT:
						$bd['notes'] = $row['dtl_Value'];
				break;

				case $titleDT:
						$bd['title'] = $row['dtl_Value'];
				break;

				case $startDT:
						$bd['startDate'] = $row['dtl_Value'];
				break;

				case $endDT:
						$bd['endDate'] = $row['dtl_Value'];
				break;
			}
		}

		return $bd;
	}

	/**
	* put your comment there...
	*
	* @param mixed $recID
	* @param mixed $relnRecID
	* @return mixed
	*/
	function getAllRelatedRecords($recID, $relnRecID=0)
	{
		global $relTypDT,$relSrcDT,$relTrgDT,$intrpDT,$notesDT,$startDT,$endDT,$titleDT, $relRT;


		if (! $recID) return null;

		$query = "select LINK.dtl_DetailTypeID as type, DETAILS.*, DBIB.rec_Title as title,
		DBIB.rec_RecTypeID as rt, DBIB.rec_URL as url
		from recDetails LINK left join Records LBIB on LBIB.rec_ID=LINK.dtl_RecID,
		recDetails DETAILS left join Records DBIB on DBIB.rec_ID=DETAILS.dtl_Value and
		DETAILS.dtl_DetailTypeID in ($relSrcDT, $relTrgDT)".
		" where (LINK.dtl_DetailTypeID in ($relSrcDT, $relTrgDT) and LBIB.rec_RecTypeID=$relRT)".
		" and LINK.dtl_Value = $recID and DETAILS.dtl_RecID = LINK.dtl_RecID";

		if ($relnRecID) $query .= " and DETAILS.dtl_RecID = $relnRecID";

		$query .= " order by LINK.dtl_DetailTypeID desc, DETAILS.dtl_ID";

		/*****DEBUG****///error_log($query);
		$res = mysql_query($query);	/* primary resources first, then non-primary, then authors */

		if (!mysql_num_rows($res)) {
			return array();
		}

		$relations = array('relationshipRecs' => array());
		while ($row = mysql_fetch_assoc($res))
		{
				$relnRecID = $row["dtl_RecID"];
				$i_am_primary = ($row["type"] == $relSrcDT);
				if (! array_key_exists($relnRecID, $relations['relationshipRecs'])){
					$relations['relationshipRecs'][$relnRecID] = array();
				}

				if (! array_key_exists("role", $relations['relationshipRecs'][$relnRecID])) {
					if ($row["type"] == $relSrcDT) {
						$relations['relationshipRecs'][$relnRecID]["role"] = "Primary";
					} else if ($row["type"] == $relTrgDT) {
						$relations['relationshipRecs'][$relnRecID]["role"] = "Non-primary";
					} else {
						$relations['relationshipRecs'][$relnRecID]["role"] = "Unknown";
					}
				}
				if (! array_key_exists("recID", $relations['relationshipRecs'][$relnRecID])) {
					$relations['relationshipRecs'][$relnRecID]["recID"] = $recID;
				}

			switch ($row["dtl_DetailTypeID"]) {
		case $relTypDT:	//saw Enum change - nothing to do since dtl_Value is an id and inverse returns an id
					$relations['relationshipRecs'][$relnRecID]["relTermID"] = $i_am_primary? $row["dtl_Value"] : reltype_inverse($row["dtl_Value"]);
					if($relations['relationshipRecs'][$relnRecID]["relTermID"]) {
						$relval = mysql_fetch_assoc(mysql_query('select trm_Label from defTerms where trm_ID = ' .  intval($relations['relationshipRecs'][$relnRecID]["relTermID"])));
						$relations['relationshipRecs'][$relnRecID]['relTerm'] = $relval['trm_Label'];
					}
			break;

		case $relTrgDT:
		case $relSrcDT:
					if ( $row["dtl_Value"] !=  $recID) {
						$relations['relationshipRecs'][$relnRecID]["relatedRec"] = array("title" => $row["title"],
																						"rectype" => $row["rt"],
																						"URL" => $row["url"],
																						"recID" => $row["dtl_Value"]);
					}
			break;

		case $intrpDT:
				$relations['relationshipRecs'][$relnRecID]["interpRec"] = array("title" => $row["title"],
																				"rectype" => $row["rt"],
																				"URL" => $row["url"],
																				"recID" => $row["dtl_Value"]);
			break;

		case $notesDT:
				$relations['relationshipRecs'][$relnRecID]["notes"] = $row["dtl_Value"];
			break;

		case $titleDT:
				$relations['relationshipRecs'][$relnRecID]["title"] = $row["dtl_Value"];
			break;

		case $startDT:
				$relations['relationshipRecs'][$relnRecID]["startDate"] = $row["dtl_Value"];
			break;

		case $endDT:
				$relations['relationshipRecs'][$relnRecID]["endDate"] = $row["dtl_Value"];
			break;
		}

		}//while

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
		}//for

		return $relations;
	}

?>
