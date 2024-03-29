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
* saveStructureLib.php. Functions to update the system structural definitions -
* rectypes, detailtypes, terms and constraints.
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


	require_once(dirname(__FILE__).'/../../common/connect/applyCredentials.php');
//	require_once(dirname(__FILE__).'/../../common/php/dbMySqlWrappers.php');
    require_once(dirname(__FILE__).'/../../common/php/utilsTitleMask.php');
    require_once(dirname(__FILE__).'/../../records/edit/deleteRecordInfo.php');
    require_once(dirname(__FILE__).'/../../common/php/imageLibrary.php');

    $rtyColumnNames = array(
        "rty_ID"=>"i",
        "rty_Name"=>"s",
        "rty_OrderInGroup"=>"i",
        "rty_Description"=>"s",
        "rty_TitleMask"=>"s",
        "rty_CanonicalTitleMask"=>"s", //not used anymore
        "rty_Plural"=>"s",
        "rty_Status"=>"s",
        "rty_OriginatingDBID"=>"i",
        "rty_NameInOriginatingDB"=>"s",
        "rty_IDInOriginatingDB"=>"i",
        "rty_NonOwnerVisibility"=>"s",
        "rty_ShowInLists"=>"i",
        "rty_RecTypeGroupID"=>"i",
        "rty_RecTypeModelsIDs"=>"s",
        "rty_FlagAsFieldset"=>"i",
        "rty_ReferenceURL"=>"s",
        "rty_AlternativeRecEditor"=>"s",
        "rty_Type"=>"s",
        "rty_ShowURLOnEditForm" =>"i",
        "rty_ShowDescriptionOnEditForm" =>"i",
        //"rty_Modified"=>"i",
        "rty_LocallyModified"=>"i"
    );

    $rstColumnNames = array(
        "rst_ID"=>"i",
        "rst_RecTypeID"=>"i",
        "rst_DetailTypeID"=>"i",
        "rst_DisplayName"=>"s",
        "rst_DisplayHelpText"=>"s",
        "rst_DisplayExtendedDescription"=>"s",
        "rst_DisplayOrder"=>"i",
        "rst_DisplayWidth"=>"i",
        "rst_DefaultValue"=>"s",
        "rst_RecordMatchOrder"=>"i",
        "rst_CalcFunctionID"=>"i",
        "rst_RequirementType"=>"s",
        "rst_NonOwnerVisibility"=>"s",
        "rst_Status"=>"s",
        "rst_MayModify"=>"s",
        "rst_OriginatingDBID"=>"i",
        "rst_IDInOriginatingDB"=>"i",
        "rst_MaxValues"=>"i",
        "rst_MinValues"=>"i",
        "rst_DisplayDetailTypeGroupID"=>"i",
        "rst_FilteredJsonTermIDTree"=>"s",
        "rst_PtrFilteredIDs"=>"s",
        "rst_OrderForThumbnailGeneration"=>"i",
        "rst_TermIDTreeNonSelectableIDs"=>"s",
        "rst_Modified"=>"s",
        "rst_LocallyModified"=>"i"
    );

	$rcsColumnNames = array(
		"rcs_ID"=>"i",
		"rcs_SourceRectypeID"=>"i",
		"rcs_TargetRectypeID"=>"i",
		"rcs_Description"=>"s",
		"rcs_TermID"=>"i",
		"rcs_TermLimit"=>"i",
		"rcs_Modified"=>"s",
		"rcs_LocallyModified"=>"i"
	);

	$dtyColumnNames = array(
		"dty_ID"=>"i",
		"dty_Name"=>"s",
		"dty_Documentation"=>"s",
		"dty_Type"=>"s",
		"dty_HelpText"=>"s",
		"dty_ExtendedDescription"=>"s",
		"dty_Status"=>"s",
		"dty_OriginatingDBID"=>"i",
		"dty_NameInOriginatingDB"=>"s",
		"dty_IDInOriginatingDB"=>"i",
		"dty_DetailTypeGroupID"=>"i",
		"dty_OrderInGroup"=>"i",
		"dty_PtrTargetRectypeIDs"=>"s",
		"dty_JsonTermIDTree"=>"s",
		"dty_TermIDTreeNonSelectableIDs"=>"s",
		"dty_PtrTargetRectypeIDs"=>"s",
		"dty_FieldSetRectypeID"=>"i",
		"dty_ShowInLists"=>"i",
		"dty_NonOwnerVisibility"=>"s",
		"dty_Modified"=>"s",
		"dty_LocallyModified"=>"i",
		"dty_EntryMask"=>"s"
	);

	//field names and types for defRecTypeGroups
	$rtgColumnNames = array(
		"rtg_ID"=>"i",
		"rtg_Name"=>"s",
		"rtg_Domain"=>"s",
		"rtg_Order"=>"i",
		"rtg_Description"=>"s",
		"rtg_Modified"=>"s"
	);
	$dtgColumnNames = array(
		"dtg_ID"=>"i",
		"dtg_Name"=>"s",
		"dtg_Order"=>"i",
		"dtg_Description"=>"s",
		"dtg_Modified"=>"s"
	);

	$trmColumnNames = array(
		"trm_ID"=>"i",
		"trm_Label"=>"s",
		"trm_InverseTermId"=>"i",
		"trm_Description"=>"s",
		"trm_Status"=>"s",
		"trm_OriginatingDBID"=>"i",
		"trm_NameInOriginatingDB"=>"s",
		"trm_IDInOriginatingDB"=>"i",
		"trm_AddedByImport"=>"i",
		"trm_IsLocalExtension"=>"i",
		"trm_Domain"=>"s",
		"trm_OntID"=>"i",
		"trm_ChildCount"=>"i",
		"trm_ParentTermID"=>"i",
		"trm_Depth"=>"i",
		"trm_Modified"=>"s",
		"trm_LocallyModified"=>"i",
		"trm_Code"=>"s"
	);

    //
    // helper function
    //
    function addParam($parameters, $type, $val){
        $parameters[0] = $parameters[0].$type;  //concat
        if($type=="s" && $val!=null){
            $val = trim($val);
        }
        array_push($parameters, $val);
        return $parameters;
    }


	/**
	* deleteRectype - Helper function that delete a rectype from defRecTypes table.if there are no existing records of this type
	*
	* @author Stephen White
	* @param $rtyID rectype ID to delete
	* @return $ret an array of return values for the various data elements created or errors if they occurred
	**/
	function deleteRecType($rtyID) {
        global $mysqli;

		$ret = array();
		$query = "select rec_ID from Records where rec_RecTypeID=$rtyID and rec_FlagTemporary=0 limit 1";
		$res = $mysqli->query($query);
        $error = $mysqli->error;
		if ($error) {
			$ret['error'] = "SQL error finding records of type $rtyID in the Records table: ".$error;
		} else {
			$recCount = $res->num_rows;
			if ($recCount) { // there are records existing of this rectype, need to return error and the recIDs
				$ret['error'] = "You cannot delete record type $rtyID as it has existing data records";  //$recCount
				$ret['recIDs'] = array();
				while ( $row = $res->fetch_row() ) {
					array_push($ret['recIDs'], $row[0]);
				}
			} else { // no records ok to delete this rectype. Not that this should cascade for all dependent definitions

				//delete temporary records
				$query = "select rec_ID from Records where rec_RecTypeID=$rtyID and rec_FlagTemporary=1";
				$res = $mysqli->query($query);
				while ($row =  $res->fetch_row() ) {
					deleteRecord($row[0]);
				}

				$query = "delete from defRecTypes where rty_ID = $rtyID";
				$res = $mysqli->query($query);
				if ( $mysqli->error) {
					$ret['error'] = "SQL error deleting record type $rtyID from defRecTypes table: ".$mysqli->error;
				} else {

					$icon_filename = HEURIST_ICON_DIR.$rtyID.".png"; //BUG what about thumb??
					if(file_exists($icon_filename)){
						unlink($icon_filename);
					}

					$ret['result'] = $rtyID;
				}
			}
		}
		return $ret;
	}

    /**
    * createRectypes - Function that inserts a new rectype into defRecTypes table.and use the rty_ID to insert any
    * fields into the defRecStructure table
    * @author Stephen White
    * @param $commonNames an array valid column names in the defRecTypes table which match the order of data in the $rt param
    * @param $dtFieldNames an array valid column names in the defRecStructure table
    * @param $rt astructured array of which can contain the column names and data for one or more rectypes with fields
    * @param $icon_filename - filename from icon library - for new record type ONLY
    * @return $ret an array of return values for the various data elements created or errors if they occurred
    **/
    function createRectypes($commonNames, $rt, $isAddDefaultSetOfFields, $convertTitleMask=true, $icon_filename=null) {
        global $mysqli, $rtyColumnNames;

        $ret = null;

        if (count($commonNames)) {

            $colNames = join(",",$commonNames);

            $parameters = array("");
            $titleMask = null;
            $query = "";
            $querycols = "";

            foreach ($commonNames as $colName) {
                $val = array_shift($rt[0]['common']);

                if(@$rtyColumnNames[$colName]){
                    //keep value of text title mask to create canonical one
                    if($convertTitleMask && $colName == "rty_TitleMask"){
                        $titleMask = $val;
                    }

                        if($query!="") {
                            $query = $query.",";
                            $querycols = $querycols.",";
                        }
                        $querycols = $querycols.$colName;
                        $query = $query."?";
                        $parameters = addParam($parameters, $rtyColumnNames[$colName], $val);

//DEBUG error_log($colName."  ".$rtyColumnNames[$colName]." = ".$val);
                }
            }

            $query = "insert into defRecTypes ($querycols) values ($query)";

            $rows = execSQL($mysqli, $query, $parameters, true);

            if($rows == "1062"){
                $ret =  "Record type with specified name already exists in the database, please use the existing record type\nThis type may be hidden - turn it on through Database Designer view > Record types";
            }else if ($rows==0 || is_string($rows) ) {
                $ret = "SQL error inserting data into table defRecTypes: ".$rows;
            } else {
                $rtyID = $mysqli->insert_id;
                $ret = -$rtyID;
                if($isAddDefaultSetOfFields){
                    //add default set of detail types
                    addDefaultFieldForNewRecordType($rtyID);
                }

                //create canonical title mask
                if($titleMask){
                    updateTitleMask($rtyID, $titleMask);
                }
                
                $need_create_icon = true;
                if($icon_filename){
                      $need_create_icon = copy_IconAndThumb_FromLibrary($rtyID, $icon_filename);
                }
                //create icon and thumbnail
                if($need_create_icon){
                    getRectypeIconURL($rtyID);
                    getRectypeThumbURL($rtyID);
                }

            }

        }
        if ($ret ==  null) {
            $ret = "no data supplied for inserting record type";
        }

        return $ret;
    }
    
    /**
    * updateRectype - Function that updates rectypes in the defRecTypes table.and updates or inserts any
    * fields into the defRecStructure table for the given rtyID
    * @author
    * @param $commonNames an array valid column names in the defRecTypes table which match the order of data in the $rt param
    * @param $dtFieldNames an array valid column names in the defRecStructure table
    * @param $rtyID id of the rectype to update
    * @param $rt a structured array of which can contain the column names and data for one or more rectypes with fields
    * @return $ret an array of return values for the various data elements created or errors if they occurred
    **/
	function updateRectype($commonNames, $rtyID, $rt) {

		global $mysqli, $rtyColumnNames;

		$ret = null;

		$res = $mysqli->query("select rty_OriginatingDBID from defRecTypes where rty_ID = $rtyID");

		if ($res->num_rows<1){ //$mysqli->affected_rows<1){
			$ret = "invalid rty_ID ($rtyID) passed in data to updateRectype";
			return $ret;
		}

		//		$row = $res->fetch_object();
		//		$query = "rty_LocallyModified=".(($row->rty_OriginatingDBID>0)?"1":"0").",";

		/*****DEBUG****///error_log(">>>>>>>>>>>>>>> ".is_array($rt['common']));
		/*****DEBUG****///error_log(">>>>>>>>>>>>>>> ".$rt['common'].length);
		$query="";

		if (count($commonNames)) {

			$parameters = array(""); //list of field date types
			foreach ($commonNames as $colName) {

				$val = array_shift($rt[0]['common']);

				if (array_key_exists($colName, $rtyColumnNames)) {
					//array_push($ret['error'], "$colName is not a valid column name for defDetailTypes val= $val was not used");

					/*****DEBUG****///error_log(">>>>>>>>>>>>>>> $colName  val=".$val);

					if($query!="") $query = $query.",";
					$query = $query."$colName = ?";

                    //since 28-June-2013 - title mask and canonical are the same @todo remove canonical at all
					if($colName == "rty_TitleMask"){
                        //array_push($parameters, ""); //empty title mask - store only canonical!
//error_log("UPDATE TITLE MASK >>>>".$val);
						$val = titlemask_make($val, $rtyID, 1, null, _ERR_REP_SILENT); //make canonical

                    }

                    $parameters = addParam($parameters, $rtyColumnNames[$colName], $val);

				}
			}

			//
			if($query!=""){

				$query = $query.", rty_LocallyModified=IF(rty_OriginatingDBID>0,1,0)";
				$query = "update defRecTypes set ".$query." where rty_ID = $rtyID";

				/*****DEBUG****///error_log(">>>>>>>>>>>>>>>".$query."   params=".join(",",$parameters)."<<<<<<<<<<<<<<<");

				$res = execSQL($mysqli, $query, $parameters, true);
				if($res == "1062"){
					$ret =  "Record type with specified name already exists in the database, please use the existing record type";
				}else if(!is_numeric($res)){
					$ret = "SQL error updating record type $rtyID in updateRectype: ".$res;
					//}else if ($rows==0) {
					//	$ret = "error updating $rtyID in updateRectype - ".$mysqli->error;
				} else {
					$ret = $rtyID;
				}
			}
		}

		if ($ret == null) {
			$ret = "no data supplied for updating record type - $rtyID";
		}

		return $ret;

	}

	//
	// converts titlemask to concept codes
	//
	function updateTitleMask($rtyID, $mask) {
        global $mysqli;


		$ret = 0;
		if($mask){
				$val = titlemask_make($mask, $rtyID, 1, null, _ERR_REP_SILENT); //make coded
                $parameters = addParam($parameters, "s", $val);

                /* DEPRECATED
				$colName = "rty_CanonicalTitleMask";
				$parameters[0] = "ss";//$parameters[0].$rtyColumnNames[$colName];
				array_push($parameters, $val);
                rty_CanonicalTitleMask = ?,
                */

				$query = "update defRecTypes set rty_TitleMask = ? where rty_ID = $rtyID";

				$res = execSQL($mysqli, $query, $parameters, true);
				if(!is_numeric($res)){
					$ret = "SQL error updating record type $rtyID in updateTitleMask: ".$res;
				}
		}
		return $ret;
	}

	//
	// used in editRecStructure to prevent detail type delete
	//
	function findTitleMaskEntries($rtyID, $dtyID) {
        global $mysqli;

        $dtyID = getDetailTypeConceptID($dtyID);

		$ret = array();

		$query = "select rty_ID, rty_Name from defRecTypes where "
        ."((rty_TitleMask LIKE '%[{$dtyID}]%') OR "
        ."(rty_TitleMask LIKE '%.{$dtyID}]%') OR"
        ."(rty_TitleMask LIKE '%[{$dtyID}.%') OR"
        ."(rty_TitleMask LIKE '%.{$dtyID}.%'))";

		if($rtyID){
			$query .= " AND (rty_ID=".$rtyID.")";
		}

		$res = $mysqli->query($query);

		if ($res->num_rows>0){ //$mysqli->affected_rows<1){
			while($row = $res->fetch_object()){
				$ret[$row->rty_ID] = $row->rty_Name;
			}
		}

		return $ret;
	}


	//
	//
	//
	function getInitRty($ri, $di, $dt, $dtid, $defvals){

		$dt = $dt[$dtid]['commonFields'];

		$arr_target = array();

		$arr_target[$ri['rst_DisplayName']] = $dt[$di['dty_Name']];
		$arr_target[$ri['rst_DisplayHelpText']] = $dt[$di['dty_HelpText']];
		$arr_target[$ri['rst_DisplayExtendedDescription']] = $dt[$di['dty_ExtendedDescription']];
		$arr_target[$ri['rst_DefaultValue']] = "";
		$arr_target[$ri['rst_RequirementType']] = $defvals[0];
		$arr_target[$ri['rst_MaxValues']] = "1";
		$arr_target[$ri['rst_MinValues']] = $defvals[1]; //0 -repeatable, 1-single
		$arr_target[$ri['rst_DisplayWidth']] = $defvals[2];
		$arr_target[$ri['rst_RecordMatchOrder']] = "0";
		$arr_target[$ri['rst_DisplayOrder']] = "null";
		$arr_target[$ri['rst_DisplayDetailTypeGroupID']] = "1";
		$arr_target[$ri['rst_FilteredJsonTermIDTree']] = null;
		$arr_target[$ri['rst_PtrFilteredIDs']] = null;
		$arr_target[$ri['rst_TermIDTreeNonSelectableIDs']] = null;
		$arr_target[$ri['rst_CalcFunctionID']] = null;
		$arr_target[$ri['rst_Status']] = "open";
		$arr_target[$ri['rst_OrderForThumbnailGeneration']] = null;
		$arr_target[$ri['rst_NonOwnerVisibility']] = "viewable";
		//$arr_target[$ri['dty_TermIDTreeNonSelectableIDs']] = "null";
		//$arr_target[$ri['dty_FieldSetRectypeID']] = "null";

		 ksort($arr_target);

		return $arr_target;
	}

	//
	//
	//
	function addDefaultFieldForNewRecordType($rtyID)
	{

			$dt = getAllDetailTypeStructures();
			$dt = $dt['typedefs'];

			$rv = getAllRectypeStructures();
			$dtFieldNames = $rv['typedefs']['dtFieldNames'];

			$di = $dt['fieldNamesToIndex'];
			$ri = $rv['typedefs']['dtFieldNamesToIndex'];

			$data = array();
			$data['dtFields'] = array(
			DT_NAME => getInitRty($ri, $di, $dt, DT_NAME, array('required',1,40)),
			DT_CREATOR => getInitRty($ri, $di, $dt, DT_CREATOR, array('optional',0,40)),
			DT_SHORT_SUMMARY => getInitRty($ri, $di, $dt, DT_SHORT_SUMMARY, array('recommended',1,60)),
			DT_THUMBNAIL => getInitRty($ri, $di, $dt, DT_THUMBNAIL, array('recommended',1,60)),
			DT_GEO_OBJECT => getInitRty($ri, $di, $dt, DT_GEO_OBJECT, array('recommended',1,40))
			// DT_START_DATE => getInitRty($ri, $di, $dt, DT_START_DATE, array('recommended',1,40)),
			// DT_END_DATE => getInitRty($ri, $di, $dt, DT_END_DATE, array('recommended',1,40))
			);

			updateRecStructure($dtFieldNames, $rtyID, $data);
	}

	//================================
	//
	// update structure for record type
	//
	function updateRecStructure( $dtFieldNames , $rtyID, $rt) {

		global $mysqli, $rstColumnNames;

		$ret = array(); //result
		$ret[$rtyID] = array();

		$mysqli->query("select rty_ID from defRecTypes where rty_ID = $rtyID");

		if ($mysqli->affected_rows<1){
			array_push($ret, "invalid rty_ID ($rtyID) passed in data to updateRectype");
			return $ret;
		}

		$query2 = "";

		if (count($dtFieldNames) && count($rt['dtFields']))
		{

			$wasLocallyModified = false;

			foreach ($rt['dtFields'] as $dtyID => $fieldVals)
			{
				//$ret['dtFields'][$dtyID] = array();
				$fieldNames = "";
				$parameters = array(""); //list of field date types


				$res = $mysqli->query("select rst_OriginatingDBID from defRecStructure where rst_RecTypeID = $rtyID and rst_DetailTypeID = $dtyID");

				/*****DEBUG****///error_log("2>>>".$mysqli->affected_rows."  ".$res->num_rows);

				$isInsert = ($mysqli->affected_rows<1);
				if($isInsert){
					$fieldNames = $fieldNames.", rst_LocallyModified";
					$query2 = "9";
				}else{
					$row = $res->fetch_object();
					$query2 = "rst_LocallyModified=".(($row->rst_OriginatingDBID>0)?"1":"0");
					$wasLocallyModified = ($wasLocallyModified || ($row->rst_OriginatingDBID>0));
				}

				//$fieldNames = "rst_RecTypeID,rst_DetailTypeID,".join(",",$dtFieldNames);

				$query = $query2;
				foreach ($dtFieldNames as $colName) {

					$val = array_shift($fieldVals);

					/*****DEBUG****///error_log(">>".$dtyID."   ".$colName."=".$val);

					if (array_key_exists($colName, $rstColumnNames) && $colName!="rst_LocallyModified") {
						//array_push($ret['error'], "$colName is not a valid column name for defDetailTypes val= $val was not used");

						if($isInsert){
							if($query!="") $query = $query.",";
							$fieldNames = $fieldNames.", $colName";
							$query = $query."?";
						}else{
							if($query!="") $query = $query.",";
							$query = $query."$colName = ?";
						}

						//special beviour
						if($colName=='rst_MaxValues' && $val==0){
							$val = null;
						}

                        $parameters = addParam($parameters, $rstColumnNames[$colName], $val);
					}
				}//for columns

				if($query!=""){
					if($isInsert){
						$query = "insert into defRecStructure (rst_RecTypeID, rst_DetailTypeID $fieldNames) values ($rtyID, $dtyID,".$query.")";
					}else{
						$query = "update defRecStructure set ".$query." where rst_RecTypeID = $rtyID and rst_DetailTypeID = $dtyID";
					}

					/*****DEBUG****///error_log(">>>3.".$query);

					$rows = execSQL($mysqli, $query, $parameters, true);

					if ($rows==0 || is_string($rows) ) {
						$oper = (($isInsert)?"inserting":"updating");
						array_push($ret[$rtyID], "Error on ".$oper." field type ".$dtyID." for record type ".$rtyID." in updateRecStructure: ".$rows);
					} else {
						array_push($ret[$rtyID], $dtyID);
					}
				}
			}//for each dt

			if($wasLocallyModified){
				$query = "update defRecTypes set rty_LocallyModified=1  where rty_ID = $rtyID";
				execSQL($mysqli, $query, $parameters, true);
			}

		} //if column names

		if (count($ret[$rtyID])==0) {
			array_push($ret[$rtyID], "no data supplied for updating record structure - $rtyID");
		}

		return $ret;
	}

	//================================
	//
	// update structure for record type
	//
	function deleteRecStructure($rtyID, $dtyID) {
		global $mysqli;

		$mysqli->query("delete from defRecStructure where rst_RecTypeID = $rtyID and rst_DetailTypeID = $dtyID limit 1");

		$rv = array();
		/*****DEBUG****///error_log(">>>>>>>>>>>>>>>".$mysqli->affected_rows);
		/*****DEBUG****///error_log(">>>Error=".$mysqli->error);
		if(isset($mysqli) && $mysqli->error!=""){
			$rv['error'] = "SQL error deleting entry in defRecStructure for record type $rtyID and field type $dtyID: ".$mysqli->error;
		}else if ($mysqli->affected_rows<1){
			$rv['error'] = "Error - no rows affected - deleting entry in defRecStructure for record type $rtyID and field type $dtyID";
		}else{
			$rv['result'] = $dtyID;
		}
		return $rv;
	}

	/**
	* createRectypeGroups - Helper function that inserts a new rectypegroup into defRecTypeGroups table
	*
	* @author Artem Osmakov
	* @param $columnNames an array valid column names in the defRecTypeGroups table which match the order of data in the $rt param
	* @param $rt array of data
	* @return $ret an array of return values for the various data elements created or errors if they occurred
	**/

	function createRectypeGroups($columnNames, $rt) {
		global $mysqli, $rtgColumnNames;

		$rtg_Name = null;
		$ret = array();
		if (count($columnNames)) {

			$colNames = join(",",$columnNames);
			foreach ( $rt as $newRT) {

				$colValues = $newRT['values'];
				$parameters = array(""); //list of field date types
				$query = "";
				foreach ($columnNames as $colName) {
					$val = array_shift($colValues);
					if($query!="") $query = $query.",";
					$query = $query."?";
                    $parameters = addParam($parameters, $rtgColumnNames[$colName], $val);

					if($colName=='rtg_Name'){
						$rtg_Name = $val;
					}
				}

				if($rtg_Name){
					$mysqli->query("select rtg_ID from defRecTypeGroups where rtg_Name = '$rtg_Name'");
					if ($mysqli->affected_rows==1){
						$ret['error'] = "There is already record type group with name '$rtg_Name'";
						return $ret;
					}
				}


				$query = "insert into defRecTypeGroups ($colNames) values ($query)";

				$rows = execSQL($mysqli, $query, $parameters, true);

				if ($rows==0 || is_string($rows) ) {
					$ret['error'] = "SQL error inserting data into defRecTypeGroups: ".$rows;
				} else {
					$rtgID = $mysqli->insert_id;
					$ret['result'] = $rtgID;
					//array_push($ret['common'], "$rtgID");
				}
			}
		}
		if (!@$ret['result'] && !@$ret['error']) {
			$ret['error'] = "Error: no data supplied for insertion into record type";
		}


		return $ret;
	}


	/**
	* updateRectypeGroup - Helper function that updates group in the deleteRectypeGroup table
	* @author Artem Osmakov
	* @param $columnNames an array valid column names in the deleteRectypeGroups table which match the order of data in the $rt param
	* @param $rtgID id of the group to update
	* @param $rt - data
	* @return $ret an array of return values for the various data elements created or errors if they occurred
	**/

	function updateRectypeGroup($columnNames, $rtgID, $rt) {
		global $mysqli, $rtgColumnNames;

		$mysqli->query("select * from defRecTypeGroups where rtg_ID = $rtgID");

		if ($mysqli->affected_rows<1){
			return array("error" => "Error: invalid record type group ID (rtg_ID) $rtgID passed in data to updateRectypeGroup");
		}

		$ret = array();
		$query = "";
		$rtg_Name = null;
		if (count($columnNames)) {

			$vals = $rt;
			$parameters = array(""); //list of field date types
			foreach ($columnNames as $colName) {
				$val = array_shift($vals);

				if (array_key_exists($colName, $rtgColumnNames)) {
					//array_push($ret['error'], array('wrongname'=>"$colName is not a valid column name for defRecTypeGroups val= $val was not used"));

					if($query!="") $query = $query.",";
					$query = $query."$colName = ?";

                    $parameters = addParam($parameters, $rtgColumnNames[$colName], $val);

					if($colName=='rtg_Name'){
						$rtg_Name = $val;
					}
				}
			}
			//

			if($rtg_Name){
				$res = $mysqli->query("select rtg_ID from defRecTypeGroups where rtg_Name = '$rtg_Name' and rtg_ID != $rtgID");
				if ($mysqli->affected_rows==1){
					$ret['error'] = "There is already group with name '$rtg_Name'";
					return $ret;
				}
			}


			if($query!=""){
				$query = "update defRecTypeGroups set ".$query." where rtg_ID = $rtgID";

				$rows = execSQL($mysqli, $query, $parameters, true);
				if ($rows==0 || is_string($rows) ) {
					$ret['error'] = "SQL error updating $colName in updateRectypeGroup: ".$rows;
				} else {
					$ret['result'] = $rtgID;
				}
			}
		}
		if (!@$ret['result'] && !@$ret['error']) {
			$ret['error'] = "Error: no data supplied for updating record type group $rtgID in defRecTypeGroups table";
		}

		return $ret;
	}

	/**
	* deleteRectypeGroup - Helper function that delete a group from defRecTypeGroups table.if there are no existing defRectype of this group
	* @author Artem Osmakov
	* @param $rtgID rectype group ID to delete
	* @return $ret an array of return values for the various data elements created or errors if they occurred
	**/
	function deleteRectypeGroup($rtgID) {
        global $mysqli;

		$ret = array();
		$query = "select rty_ID from defRecTypes where rty_RecTypeGroupID =$rtgID";
		$res = $mysqli->query($query);
		if ($mysqli->error) {
			$ret['error'] = "Error finding record types for group $rtgID in defRecTypes table: ".$mysqli->error;
		} else {
			$recCount = $res->num_rows;
			if ($recCount) { // there are rectypes existing of this group, need to return error and the recIDs
				$ret['error'] = "You cannot delete group $rtgID as there are $recCount record types in this group";
				$ret['rtyIDs'] = array();
				while ($row = $res->fetch_row() ) {
					array_push($ret['rtyIDs'], $row[0]);
				}
			} else { // no rectypes belong this group -  ok to delete this group.
				// Not that this should cascade for all dependent definitions
				$query = "delete from defRecTypeGroups where rtg_ID=$rtgID";
				$res = $mysqli->query($query);
				if ($mysqli->error) {
					$ret['error'] = "Database error deleting record types group $rtgID from defRecTypeGroups table: ".$mysqli->error;
				} else {
					$ret['result'] = $rtgID;
				}
			}
		}
		return $ret;
	}


	/**
	* createDettypeGroups - Helper function that inserts a new dettypegroup into defDetailTypeGroups table
	* @author Artem Osmakov
	* @param $columnNames an array valid column names in the defDetailTypeGroups table which match the order of data in the $rt param
	* @param $rt array of data
	* @return $ret an array of return values for the various data elements created or errors if they occurred
	**/

	function createDettypeGroups($columnNames, $rt) {
		global $mysqli, $dtgColumnNames;

		$dtg_Name = null;
		$ret = array();
		if (count($columnNames)) {

			$colNames = join(",",$columnNames);
			foreach ( $rt as $newRT) {

				$colValues = $newRT['values'];
				$parameters = array(""); //list of field date types
				$query = "";
				foreach ($columnNames as $colName) {
					$val = array_shift($colValues);
					if($query!="") $query = $query.",";
					$query = $query."?";
                    $parameters = addParam($parameters, $dtgColumnNames[$colName], $val);

					if($colName=='dtg_Name'){
						$dtg_Name = $val;
					}
				}

				if($dtg_Name){
					$mysqli->query("select dtg_ID from defDetailTypeGroups where dtg_Name = '$dtg_Name'");
					if ($mysqli->affected_rows==1){
						$ret['error'] = "There is already detail group with name '$dtg_Name'";
						return $ret;
					}
				}


				$query = "insert into defDetailTypeGroups ($colNames) values ($query)";

				$rows = execSQL($mysqli, $query, $parameters, true);

				if ($rows==0 || is_string($rows) ) {
					$ret['error'] = "SQL error inserting data into defDetailTypeGroups table: ".$rows;
				} else {
					$dtgID = $mysqli->insert_id;
					$ret['result'] = $dtgID;
					//array_push($ret['common'], "$rtgID");
				}
			}
		}
		if (!@$ret['result'] && !@$ret['error']) {
			$ret['error'] = "Error: no data supplied for insertion of detail (field) type";
		}


		return $ret;
	}


	/**
	* updateDettypeGroup - Helper function that updates group in the defDetailTypeGroups table
	* @author Artem Osmakov
	* @param $columnNames an array valid column names in the defDetailTypeGroups table which match the order of data in the $rt param
	* @param $dtgID id of the group to update
	* @param $rt - data
	* @return $ret an array of return values for the various data elements created or errors if they occurred
	**/

	function updateDettypeGroup($columnNames, $dtgID, $rt) {
		global $mysqli, $dtgColumnNames;

		$mysqli->query("select * from defDetailTypeGroups where dtg_ID = $dtgID");

		if ($mysqli->affected_rows<1){
			return array("error" => "Error: looking for invalid field type group ID (dtg_ID) $dtgID in defDetailTypeGroups table");
		}

		$ret = array();
		$dtg_Name = null;
		$query = "";
		if (count($columnNames)) {

			$vals = $rt;
			$parameters = array(""); //list of field date types
			foreach ($columnNames as $colName) {
				$val = array_shift($vals);

				if (array_key_exists($colName, $dtgColumnNames)) {
					//array_push($ret['error'], array('wrongname'=>"$colName is not a valid column name for defDetailTypeGroups val= $val was not used"));

					if($query!="") $query = $query.",";
					$query = $query."$colName = ?";

                    $parameters = addParam($parameters, $dtgColumnNames[$colName], $val);

					if($colName=='dtg_Name'){
						$dtg_Name = $val;
					}
				}
			}
			//

			if($dtg_Name){
				$mysqli->query("select dtg_ID from defDetailTypeGroups where dtg_Name = '$dtg_Name' and dtg_ID!=$dtgID");
				if ($mysqli->affected_rows==1){
					$ret['error'] = "There is already group with name '$dtg_Name'";
					return $ret;
				}
			}


			if($query!=""){
				$query = "update defDetailTypeGroups set ".$query." where dtg_ID = $dtgID";

				$rows = execSQL($mysqli, $query, $parameters, true);
				if ($rows==0 || is_string($rows) ) {
					$ret['error'] = "SQL error updating $colName in updateDettypeGroup: ".$rows;
				} else {
					$ret['result'] = $dtgID;
				}
			}
		}
		if (!@$ret['result'] && !@$ret['error']) {
			$ret['error'] = "Error: no data supplied for updating field type group $dtgID in defDetailTypeGroups table";
		}

		return $ret;
	}

	/**
	* deleteDettypeGroup - Helper function that delete a group from defDetailTypeGroups table.if there are no existing defRectype of this group
	* @author Artem Osmakov
	* @param $rtgID rectype group ID to delete
	* @return $ret an array of return values for the various data elements created or errors if they occurred
	**/
	function deleteDettypeGroup($dtgID) {
        global $mysqli;

		$ret = array();
		$query = "select dty_ID from defDetailTypes where dty_DetailTypeGroupID =$dtgID";
		$res = $mysqli->query($query);
		if ($mysqli->error) {
			$ret['error'] = "Error: unable to find detail types in group $dtgID in the defDetailTypes table: ".$mysqli->error;
		} else {
			$recCount = $res->num_rows;
			if ($recCount) { // there are rectypes existing of this group, need to return error and the recIDs
				$ret['error'] = "You cannot delete field types group $dtgID because it contains $recCount field types";
				$ret['dtyIDs'] = array();
				while ($row = $res->fetch_row() ) {
					array_push($ret['dtyIDs'], $row[0]);
				}
			} else { // no rectypes belong this group -  ok to delete this group.
				// Not that this should cascade for all dependent definitions
				$query = "delete from defDetailTypeGroups where dtg_ID=$dtgID";
				$res = $mysqli->query($query);
				if ($mysqli->error) {
					$ret['error'] = "SQL error deleting field type group $dtgID from defRecTypeGroups table:".$mysqli->error;
				} else {
					$ret['result'] = $dtgID;
				}
			}
		}
		return $ret;
	}

	// -------------------------------  DETAILS ---------------------------------------
	/**
	* createDetailTypes - Helper function that inserts a new detailTypes into defDetailTypes table
	* @author Stephen White
	* @param $commonNames an array valid column names in the defDetailTypes table which match the order of data in the $dt param
	* @param $dt a structured array of data which can contain the column names and data for one or more detailTypes
	* @return $ret an array of return values for the various data elements created or errors if they occurred
	**/

	function createDetailTypes($commonNames,$dt) {
		global $mysqli, $dtyColumnNames;

		$ret = null;

		if (count($commonNames)) {


			$colNames = join(",",$commonNames);

			$parameters = array(""); //list of field date types
			$query = "";
            $querycols = "";
			foreach ($commonNames as $colName) {
				$val = array_shift($dt['common']);
                if(@$dtyColumnNames[$colName]){

				    if($query!="") {
                        $query = $query.",";
                        $querycols = $querycols.",";
                    }
				    $query = $query."?";
                    $querycols = $querycols.$colName;
                    $parameters = addParam($parameters, $dtyColumnNames[$colName], $val);
                }
			}

			$query = "insert into defDetailTypes ($querycols) values ($query)";

			$rows = execSQL($mysqli, $query, $parameters, true);

			if($rows == "1062"){
				$ret =  "Field type with specified name already exists in the database, please use the existing field type.\nThe field may be hidden - turn it on through Database Designer view > Manage Field Types";
			}else  if ($rows==0 || is_string($rows) ) {
				$ret = "Error inserting data into defDetailTypes table: ".$rows;
			} else {
				$dtyID = $mysqli->insert_id;
				$ret = -$dtyID;
			}

		}
		if ($ret ==  null) {
			$ret = "no data supplied for inserting dettype";
		}
		return $ret;
	}

	/**
	* updateDetailType - Helper function that updates detailTypes in the defDetailTypes table.
	* @author Stephen White
	* @param $commonNames an array valid column names in the defDetailTypes table which match the order of data in the $dt param
	* @param $dtyID id of the rectype to update
	* @param $dt a structured array of which can contain the column names and data for one or more detailTypes with fields
	* @return $ret an array of return values for the various data elements created or errors if they occurred
	**/

	/**
	* deleteDetailType - Helper function that deletes a detailtype from defDetailTypes table.if there are no existing details of this type
	* @author Stephen White
	* @param $dtyID detailtype ID to delete
	* @return $ret an array of return values for the various data elements created or errors if they occurred
	**/

	function deleteDetailType($dtyID) {
         global $mysqli;

		$ret = array();
		$query = "select dtl_ID from recDetails where dtl_DetailTypeID =$dtyID";
		$res = $mysqli->query($query);
		if ($mysqli->error) {
			$ret['error'] = "SQL error: unable to retrieve fields of type $dtyID from recDetails table: ".$mysqli->error;
		} else {
			$dtCount = $res->num_rows;
			if ($dtCount) { // there are records existing of this rectype, need to return error and the recIDs
				$ret['error'] = "You cannot delete field type $dtyID as it is used $dtCount times in the data";
				$ret['dtlIDs'] = array();
				while ($row = $res->fetch_row()) {
					array_push($ret['dtlIDs'], $row[0]);
				}
			} else { // no records ok to delete this rectype. Not that this should cascade for all dependent definitions
				$query = "delete from defDetailTypes where dty_ID = $dtyID";
				$res = $mysqli->query($query);
				if ($mysqli->error) {
					$ret['error'] = "SQL error deleting field type $dtyID from defDetailTypes table: ".$mysqli->error;
				} else {
					$ret['result'] = $dtyID;
				}
			}
		}
		return $ret;
	}

	//
	function updateDetailType($commonNames,$dtyID,$dt) {

		global $mysqli, $dtyColumnNames;

		$ret = null;

		$res = $mysqli->query("select dty_OriginatingDBID from defDetailTypes where dty_ID = $dtyID");

		if ($res->num_rows<1){ //$mysqli->affected_rows<1){
			$ret = "invalid dty_ID ($dtyID) passed in data to updateDetailType";
			return $ret;
		}
		//$row = $res->fetch_object();
		//$query = "dty_LocallyModified=".(($row->dty_OriginatingDBID>0)?"1":"0").",";
		$query = "";

		if (count($commonNames)) {

			$vals = $dt['common'];
			$parameters = array(""); //list of field date types
			foreach ($commonNames as $colName)
			{

				$val = array_shift($vals); //take next value

				if (array_key_exists($colName, $dtyColumnNames)) {
					//array_push($ret['error'], "$colName is not a valid column name for defDetailTypes val= $val was not used");

					if($query!="") $query = $query.",";
					$query = $query."$colName = ?";

                    $parameters = addParam($parameters, $dtyColumnNames[$colName], $val);
				}
			}//for
			//
			if($query!=""){

				$query = $query.", dty_LocallyModified=IF(dty_OriginatingDBID>0,1,0)";
				$query = "update defDetailTypes set ".$query." where dty_ID = $dtyID";

//error_log("AAAA:".$query);                
                
				$rows = execSQL($mysqli, $query, $parameters, true);
				if($rows == "1062"){
					$ret =  "Field type with specified name already exists in the database, please use the existing field type";
				}else if ($rows==0 || is_string($rows) ) {
//error_log($query);error_log($rows);
					$ret = "AAA SQL error updating field type $dtyID in updateDetailType: ".htmlspecialchars($query)."  type=".$parameters[0]." values=".@$parameters[1];//.$mysqli->error;
				} else {
					$ret = $dtyID;
				}
			}
		}

		if ($ret == null) {
			$ret = "no data supplied for updating dettype - $dtyID";
		}
		return $ret;
	}

	//========================================

	//================================
	/**
	* update terms
	*
	* @param $coldNames - array of field names
	* @param $trmID - term id, in case new term this is string
	* @param $values - array of values
    * @param $ext_db - mysqli
	* @return $ret - if success this is ID of term, if failure - error string
	*/
	function updateTerms( $colNames, $trmID, $values, $ext_db) {

		global $mysqli, $trmColumnNames;

		if($ext_db==null){
			$ext_db = $mysqli;
		}

		$ret = null;

		if (count($colNames) && count($values))
		{
			$isInsert = ($trmID==null || (!is_numeric($trmID) && (strrpos($trmID, "-")>0)));
            
            $inverse_termid_old = null;
            if(!$isInsert){//find inverse term id 
                $res = $ext_db->query("select trm_InverseTermId from defTerms where trmID=".$trmID);
                if($res){
                    if ( $row = $res->fetch_row() ) {
                        $inverse_termid_old = $row[0];
                    }
                }
            }
            

			$query = "";
            $querycols = "";
			$parameters = array("");

            $ch_parent_id = null;
            $ch_code = null;
            $ch_label = null;
            $inverse_termid = null;


			foreach ($colNames as $colName) {

				$val = array_shift($values);

				if (array_key_exists($colName, $trmColumnNames)) {
					//array_push($ret['error'], "$colName is not a valid column name for defDetailTypes val= $val was not used");

					if($query!=""){
                        $query = $query.",";
                        $querycols = $querycols.",";
                    }

					if($isInsert){
						$query = $query."?";
                        $querycols = $querycols.$colName;
					}else{
						$query = $query."$colName = ?";
					}

                    if($colName=="trm_ParentTermID"){
                        $ch_parent_id = $val;
                    }else if($colName=="trm_Code"){
                        $ch_code = $val;
                    }else if($colName=="trm_Label"){
                        $ch_label = $val;
                    }else if($colName=="trm_InverseTermId"){
                        if($val=="") $val=null;
                        $inverse_termid = $val;   //new value
                    }else if($colName=="trm_Status"){
                        if($val=="") $val="open";
                    }

                    $parameters = addParam($parameters, $trmColumnNames[$colName], $val);
				}
			}//for columns

            //check label and code duplication for the same level
            if($ch_code || $ch_label){
                if($ch_parent_id){
                    $ch_parent_id = "trm_ParentTermID=".$ch_parent_id;
                }else{
                    $ch_parent_id = "(trm_ParentTermID is null or trm_ParentTermID=0)";
                }

                $dupquery = "select trm_ID from defTerms where ".$ch_parent_id;

                if(!$isInsert){
                    $dupquery .= " and (trm_ID <>".$trmID.")";
                }
                $dupquery .= " and (";
                if($ch_code){
                    $dupquery .= "(trm_Code = '".mysql_real_escape_string($ch_code)."')";
                }
                if($ch_label){
                    if($ch_code){
                        $dupquery .= " or ";
                    }
                    $dupquery .= "(trm_Label = '".mysql_real_escape_string($ch_label)."')";
                }
                $dupquery .= ")";

                $res = $ext_db->query($dupquery);
                if ($ext_db->error) {
                    $ret = "SQL error checking duplication values in terms: ".$ext_db->error."  Query:".$dupquery;
                } else {
                    $recCount = $res->num_rows;
                    if($recCount>0){
                        $ret = "Duplicate label or code not allowed terms at same level in tree";
                    }
                }

            }

            //insert, update
			if(!$ret && $query!=""){
				if($isInsert){
					$query = "insert into defTerms (".$querycols.") values (".$query.")";
				}else{
					$query = $query.", trm_LocallyModified=IF(trm_OriginatingDBID>0,1,0)";
					$query = "update defTerms set ".$query." where trm_ID = $trmID";
				}

				$rows = execSQL($ext_db, $query, $parameters, true);

				if ($rows==0 || is_string($rows) ) {      //ERROR
					$oper = (($isInsert)?"inserting":"updating term ".$trmID);
					$ret = "SQL error $oper in updateTerms: ".$rows; //."  ".htmlspecialchars($query);
				} else {
					if($isInsert){
						$trmID = $ext_db->insert_id;  // new id
					}

                    if($inverse_termid!=null){
                        $query = "update defTerms set trm_InverseTermId=$trmID where trm_ID=$inverse_termid";
                        execSQL($ext_db, $query, null, true);
                    }else if ($inverse_termid_old!=null){
                        $query = "update defTerms set trm_InverseTermId=null where trm_ID=$inverse_termid_old";
                        execSQL($ext_db, $query, null, true);
                    }
                    

					$ret = $trmID;
				}

			}
		} //if column names




		if ($ret==null){
			$ret = "no data supplied for updating record structure - $trmID";
		}

		return $ret;
	}

    /**
    * Merge two terms in defTerms and update recDetails
    * 
        1. change parent id for all children terms 
        2. delete term $merge_id 
        3. update entries in recDetails for all detail type enum or reltype
        4. update term $retain_id
        
    * @param mixed $retain_id
    * @param mixed $merge_id
    */
    function mergeTerms($retain_id, $merge_id, $colNames, $dt){
        global $mysqli;
        
        $ret = array();
        
        //1. change parent id for all children terms 
        $query = "update defTerms set trm_ParentTermID = $retain_id where trm_ParentTermID = $merge_id";
        $res = $mysqli->query($query);
        if ($mysqli->error) {
            $ret['error'] = "SQL error can not change parent term for $merge_id from defTerms table: ".$mysqli->error;
            return $ret;
        }
        
        //2. update entries in recDetails for all detail type enum or reltype
        $query = "update recDetails, defDetailTypes set dtl_Value=".$retain_id
                    ." where (dty_ID = dtl_DetailTypeID ) and "
                    ." (dty_Type='enum' or dty_Type='relationtype') and "
                    ." (dtl_Value=".$merge_id.")";
            
        $res = $mysqli->query($query);
        if ($mysqli->error) {
            $ret['error'] = "SQL error in mergeTerms updating record details ".$mysqli->error;
            return $ret;
        }
        
        //3. delete term $merge_id 
        $query = "delete from defTerms where trm_ID = $merge_id";
        $res = $mysqli->query($query);
        if ($mysqli->error) {
            $ret['error'] = "SQL error deleting term $merge_id from defTerms table: ".$mysqli->error;
            return $ret;
        }
        
        //4. update term $retain_id
        return updateTerms( $colNames, $retain_id, $dt, null );
    }
    
	/**
	* recursive function
	* @param $ret -- array of child
	* @param $trmID - term id to be find all children
	*/
	function getTermsChilds($ret, $trmID) {
        global $mysqli;

		$query = "select trm_ID from defTerms where trm_ParentTermID = $trmID";
		$res = $mysqli->query($query);
		while ($row = $res->fetch_row()) {
			$child_trmID = $row[0];
			$ret = getTermsChilds($ret, $child_trmID);
			array_push($ret, $child_trmID);
		}
		return $ret;
	}
    
    /**
    * Returns all parents for given term
    * 
    * @param mixed $termId
    */
    function getTermParents($termId){
        global $mysqli;
                                                     
        $query = "select trm_ParentTermID from defTerms where trm_ID = ".$termId;
        $res = $mysqli->query($query);
        $row = $res->fetch_row();
        if($row){
            $parentId = $row[0];
            
        }

        $parentId = mysql_fetch_array(mysql_query($query));
        if($parentId && @$parentId[0]){
            return getTopMostParentTerm($parentId[0]);
        }else{
            return $termId;
        }
    }
    

	/**
	* deletes the term with given ID and all its children
	* before deletetion it verifies that this term or any of its children is refered in defDetailTypes dty_JsonTermIDTree
	*
	* @todo - need to check inverseid or it will error by foreign key constraint?
	*/
	function deleteTerms($trmID) {
         global $mysqli;

		$ret = array();

		$children = array();
		//find all children
		$children = getTermsChilds($children, $trmID);
		array_push($children, $trmID);

		/*****DEBUG****///		error_log(">>>>>>>>>>>>>>>>>".join(",",$children));

        //find possible entries in defDetailTypes dty_JsonTermIDTree
        foreach ($children as $termID) {
            $query = "select dty_ID, dty_Name from defDetailTypes where (FIND_IN_SET($termID, dty_JsonTermIDTree)>0)";
            $res = $mysqli->query($query);
            if ($mysqli->error) {
                $ret['error'] = "SQL error in deleteTerms retreiving field types which use term $termID: ".$mysqli->error;
                break;
            }else{
                $dtCount = $res->num_rows;
                if ($dtCount>0) { // there are records existing of this rectype, need to return error and the recIDs
                    $ret['error'] = "Error: You cannot delete term $trmID. "
                                .(($trmID==$termID)?"It":"Its child term $termID")
                                ." is referenced in $dtCount base field defintions "
                                ."- please delete field definitions or remove terms from these fields to allow deletion of these terms.<div style='text-align:left'><ul>";
                    $ret['dtyIDs'] = array();
                    while ($row = $res->fetch_row()) {
                        array_push($ret['dtyIDs'], $row[0]);
                        $ret['error'] = $ret['error'].("<li>".$row[0]."&nbsp;".$row[1]."</li>");
                    }
                    $ret['error'] = $ret['error']."</ul></div>";
                    break;
                }
            }
            //TODO: need to check inverseid or it will error by foreign key constraint?
        }//foreach
        
        //find usage in recDetails
        if(!array_key_exists("error", $ret)){
            
            $query = "select distinct dtl_RecID from recDetails, defDetailTypes "
                    ."where (dty_ID = dtl_DetailTypeID ) and "
                    ."(dty_Type='enum' or dty_Type='relationtype') and "
                    ."(dtl_Value in (".implode(",",$children)."))";
            
                $res = $mysqli->query($query);
                if ($mysqli->error) {
                    $ret['error'] = "SQL error in deleteTerms retreiving records which use term $termID: ".$mysqli->error;
                    break;
                }else{
                    $recCount = $res->num_rows;
                    if ($recCount>0) { // there are records existing of this rectype, need to return error and the recIDs
                        $ret['error'] = "Error: You cannot delete term $trmID. It or its child terms are referenced in $recCount record(s)";
                        $ret['recIDs'] = array();
                        while ($row = $res->fetch_row()) {
                            array_push($ret['recIDs'], $row[0]);
                        }
                        $ret['error'] = $ret['error']."<br><br>"
                        ."<a href='#' onclick='window.open(\""
                        .HEURIST_BASE_URL."search/search.html?db=".HEURIST_DBNAME."&q=ids:".implode(",",$ret['recIDs'])."\",\"_blank\")'>Click here</a> to retrieve these records";
                    }
                }
        }
        

		//all is clear - delete the term
		if(!array_key_exists("error", $ret)){
			//$query = "delete from defTerms where trm_ID in (".join(",",$children).")";
			//$query = "delete from defTerms where trm_ID = $trmID";

			foreach ($children as $termID) {
				$query = "delete from defTerms where trm_ID = $termID";
				$res = $mysqli->query($query);
				/*****DEBUG****///error_log(">>>>>>>>>>>>>>>>>".$res."   ".$mysqli->error);
				if ($mysqli->error) {
					$ret['error'] = "SQL error deleting term $termID from defTerms table: ".$mysqli->error;
					break;
				}
			}

			if(!array_key_exists("error", $ret)){
				$ret['result'] = $children;
			}

			/*
			$res = $mysqli->query($query);
			if ($mysqli->error) {
			$ret['error'] = "DB error deleting of term $trmID and its children from defTerms - ".$mysqli->error;
			} else {
			$ret['result'] = $children;
			}
			*/
		}

		return $ret;
	}

	/**
	* put your comment there...
	*
	* @param mixed $rcons - array that contains data for on record in defRelationshipConstraints
	*/
	function updateRelConstraint($srcID, $trgID, $terms){

		global $mysqli, $rcsColumnNames;

		$ret = null;

		if($terms==null){
			$terms = array("null", '', "null", '');
		}

		if(intval($terms[2])<1){ //if($terms[2]==null || $terms[2]=="" || $terms[2]=="0"){
			$terms[2] = "null";
		}

		$where = " where ";

 		if(intval($srcID)<1){
			$srcID = "null";
			$where = $where." rcs_SourceRectypeID is null";
 		}else{
			$where = $where." rcs_SourceRectypeID=".$srcID;
 		}
 		if(intval($trgID)<1){
			$trgID = "null";
			$where = $where." and rcs_TargetRectypeID is null";
 		}else{
			$where = $where." and rcs_TargetRectypeID=".$trgID;
 		}

		if(intval($terms[0])<1){ // $terms[0]==null || $terms[0]==""){
			$terms[0]=="null";
			$where = $where." and rcs_TermID is null";
		}else{
			$where = $where." and rcs_TermID=".$terms[0];;
		}

		$query = "select rcs_ID from defRelationshipConstraints ".$where;

		$res = $mysqli->query($query);

		$parameters = array("s",$terms[3]); //notes will be parameter
		$query = "";

		if ($res==null || $res->num_rows<1){ //$mysqli->affected_rows<1){
			//insert
			$query = "insert into defRelationshipConstraints(rcs_SourceRectypeID, rcs_TargetRectypeID, rcs_Description, rcs_TermID, rcs_TermLimit) values (".
						$srcID.",".$trgID.",?,".$terms[0].",".$terms[2].")";

		}else{
			//update
			$query = "update defRelationshipConstraints set rcs_Description=?, rcs_TermID=".$terms[0].", rcs_TermLimit=".$terms[2].$where;
		}

		$rows = execSQL($mysqli, $query, $parameters, true);
		if ($rows==0 || is_string($rows) ) {
				$ret = "SQL error in updateRelConstraint: ".$query; //$mysqli->error;
		} else {
				$ret = array($srcID, $trgID, $terms);
		}

		return $ret;
	}

	/**
	* Delete constraints
	*/
	function deleteRelConstraint($srcID, $trgID, $trmID){
        global $mysqli;

		$ret = array();
		$query = "delete from defRelationshipConstraints where ";

 		if(intval($srcID)<1){
			$srcID = "null";
			$query = $query." rcs_SourceRectypeID is null";
 		}else{
			$query = $query." rcs_SourceRectypeID=".$srcID;
 		}
 		if(intval($trgID)<1){
			$trgID = "null";
			$query = $query." and rcs_TargetRectypeID is null";
 		}else{
			$query = $query." and rcs_TargetRectypeID=".$trgID;
 		}

		if ( strpos($trmID,",")>0 ) {
			$query = $query." and rcs_TermID in ($trmID)";
		}else if(intval($trmID)<1){
			$query = $query." and rcs_TermID is null";
		}else{
			$query = $query." and rcs_TermID=$trmID";
		}

		$res = $mysqli->query($query);
		if ($mysqli->error) {
			$ret['error'] = "SQL error deleting constraint ($srcID, $trgID, $trmID) from defRelationshipConstraints table: ".$mysqli->error;
		} else {
			$ret['result'] = "ok";
		}
		return $ret;
	}
?>
