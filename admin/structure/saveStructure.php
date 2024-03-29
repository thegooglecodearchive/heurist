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
* saveStructure.php. This file accepts request to update the system structural definitions -
* rectypes, detailtypes, terms and constraints. It returns the entire structure for the affected area
* in order to update top.HEURIST
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
    define('ISSERVICE',1);

    require_once(dirname(__FILE__).'/../../common/connect/applyCredentials.php');
//    require_once(dirname(__FILE__).'/../../common/php/dbMySqlWrappers.php');
    require_once(dirname(__FILE__).'/../../common/php/getRecordInfoLibrary.php');
    require_once('saveStructureLib.php');

    header('Content-type: text/javascript; charset=utf-8');
    
/*****DEBUG****///error_log(">>>".print_r($_REQUEST, true));
    $rv = array();
  
    if (!is_admin()) {
        error_exit("Sorry, you need to be a database owner to be able to modify the database structure");
    }

    $legalMethods = array(
        "saveRectype",
        "saveRT",
        "saveRTS",
        "deleteRTS",
        "saveRTC",
        "deleteRTC",
        "saveRTG",
        "saveDetailType",
        "saveDT",
        "saveDTG",
        "saveTerms",
        "mergeTerms",
        "deleteTerms",
        "deleteDT",
        "deleteRT",
        "deleteRTG",
        "deleteDTG",
        "checkDTusage"
    );
 
$method = @$_REQUEST['method'];
if ($method && !in_array($_REQUEST['method'],$legalMethods)) {
    $method = null;
}
    
if(!$method){
  error_exit("Invalid call to saveStructure, there is no valid 'method' parameter");  
}
else
{
        $data = @$_REQUEST['data'];
        //decode and unpack data
        if($data){
            $data = json_decode(urldecode(@$_REQUEST['data']), true);
        }
        $mysqli = mysqli_connection_overwrite(DATABASE); // mysqli
        mysql_connection_overwrite(DATABASE); // need for getRecordInfoLibrary 

        switch ($method) {
            

            //{ rectype:
            //            {colNames:{ common:[rty_name,rty_OrderInGroup,.......],
            //                        dtFields:[rst_DisplayName, ....]},
            //            defs : {-1:[[common:['newRecType name',56,34],dtFields:{dty_ID:[overide name,76,43], 160:[overide name2,136,22]}],
            //                        [common:[...],dtFields:{nnn:[....],...,mmm:[....]}]],
            //                    23:{common:[....], dtFields:{nnn:[....],...,mmm:[....]}}}}}

            case 'saveRectype':

            case 'saveRT': // Record type
                if (!array_key_exists('rectype',$data) ||
                    !array_key_exists('colNames',$data['rectype']) ||
                    !array_key_exists('defs',$data['rectype'])) {
                    error_exit("Invalid data structure sent with saveRectype method call to saveStructure.php");
                }
                $commonNames = $data['rectype']['colNames']['common'];
                //$dtFieldNames = $rtData['rectype']['colNames']['dtFields'];

                $rv['result'] = array(); //result
                
                
                foreach ($data['rectype']['defs'] as $rtyID => $rt) {
                    if ($rtyID == -1) {    // new rectypes
                        $definit = @$_REQUEST['definit'];  //create set of default fields for new rectype
                        
                        $ret = createRectypes($commonNames, $rt, ($definit=="1"), true, @$_REQUEST['icon']);
                        array_push($rv['result'], $ret);
                        
                    }else{
                        array_push($rv['result'], updateRectype($commonNames, $rtyID, $rt));
                    }
                }

                $rv['rectypes'] = getAllRectypeStructures(false);
                
                break;

            case 'saveRTS': // Record type structure

                /*****DEBUG****///DEBUG   error_log(">>>>>>>".print_r($data,true));
                if (!array_key_exists('rectype',$data) ||
                    !array_key_exists('colNames',$data['rectype']) ||
                    !array_key_exists('defs',$data['rectype']))
                {
                    error_exit("Invalid data structure sent with updateRecStructure method call to saveStructure.php");
                }

                //$commonNames = $rtData['rectype']['colNames']['common'];
                $dtFieldNames = $data['rectype']['colNames']['dtFields'];
                
                $rv['result'] = array(); //result

                //actually client sends the definition only for one record type
                foreach ($data['rectype']['defs'] as $rtyID => $rt) {
                    array_push($rv['result'], updateRecStructure($dtFieldNames, $rtyID, $rt));
                }
                $rv['rectypes'] = getAllRectypeStructures();
                $rv['detailTypes'] = getAllDetailTypeStructures();
                break;

            case 'deleteRTS':

                $rtyID = @$_REQUEST['rtyID'];
                $dtyID = @$_REQUEST['dtyID'];

                if (!$rtyID || !$dtyID) {
                    $rv['error'] = "Error: No IDs or invalid IDs sent with deleteRecStructure method call to saveStructure.php";
                }else{
                    $rv = deleteRecStructure($rtyID, $dtyID);
                    if (!array_key_exists('error', $rv)) {
                        $rv['rectypes'] = getAllRectypeStructures();
                        $rv['detailTypes'] = getAllDetailTypeStructures();
                    }
                }
                break;

            case 'saveRTC': //Constraints

                $srcID = @$_REQUEST['srcID'];
                $trgID = @$_REQUEST['trgID'];
                $terms_todel = @$_REQUEST['del'];

                if (!$srcID && !$trgID) {

                    $rv['error'] = "Error: No record type IDs or invalid IDs sent with deleteRelConstraint method call to saveStructure.php";

                }else{
                    //$colNames = $data['colNames'];  //['defs']
                    $rv['result'] = array(); //result

                    for ($ind=0; $ind<count($data); $ind++) {
                        array_push($rv['result'], updateRelConstraint($srcID, $trgID, $data[$ind]  ));
                    }
                    if($terms_todel){
                        array_push($rv['result'], deleteRelConstraint($srcID, $trgID, $terms_todel));
                    }

                    $rv['constraints'] = getAllRectypeConstraint();//getAllRectypeStructures();

                }
                break;


            case 'deleteRTC': //Constraints

                $srcID = @$_REQUEST['srcID'];
                $trgID = @$_REQUEST['trgID'];
                $trmID = @$_REQUEST['trmID'];

                if (!$srcID && !$trgID) {
                    $rv['error'] = "Error: No record type IDs or invalid IDs sent with deleteRelConstraint method call to saveStructure.php";
                }else{
                    $rv = deleteRelConstraint($srcID, $trgID, $trmID);
                    if (!array_key_exists('error', $rv)) {
                        $rv['constraints'] = getAllRectypeConstraint();//getAllRectypeStructures();
                    }
                }
                break;

            case 'deleteRectype':
            case 'deleteRT':

                $rtyID = @$_REQUEST['rtyID'];

                if (!$rtyID) {
                    $rv['error'] = "Error: No IDs or invalid IDs sent with deleteRectype method call to saveStructure.php";
                }else{
                    $rv = deleteRecType($rtyID);
                    if (!array_key_exists('error',$rv)) {
                        $rv['rectypes'] = getAllRectypeStructures();
                    }
                }
                break;

                //------------------------------------------------------------

            case 'saveRTG':    // Record type group

                if (!array_key_exists('rectypegroups',$data) ||
                    !array_key_exists('colNames',$data['rectypegroups']) ||
                    !array_key_exists('defs',$data['rectypegroups'])) {
                    error_exit("Invalid data structure sent with saveRectypeGroup method call to saveStructure.php");
                }
                $colNames = $data['rectypegroups']['colNames'];
                foreach ($data['rectypegroups']['defs'] as $rtgID => $rt) {
                    if ($rtgID == -1) {    // new rectype group
                        array_push($rv, createRectypeGroups($colNames, $rt));
                    }else{
                        array_push($rv, updateRectypeGroup($colNames, $rtgID, $rt));
                    }
                }
                if (!array_key_exists('error',$rv)) {
                    $rv['rectypes'] = getAllRectypeStructures();
                }
                break;

            case 'deleteRTG':

                $rtgID = @$_REQUEST['rtgID'];
                if (!$rtgID) {
                    error_exit("Invalid or no record type group ID sent with deleteRectypeGroup method call to saveStructure.php");
                }
                $rv = deleteRectypeGroup($rtgID);
                if (!array_key_exists('error',$rv)) {
                    $rv['rectypes'] = getAllRectypeStructures();
                }
                break;

            case 'saveDTG':    // Field (detail) type group

                if (!array_key_exists('dettypegroups',$data) ||
                    !array_key_exists('colNames',$data['dettypegroups']) ||
                    !array_key_exists('defs',$data['dettypegroups'])) {
                    error_exit("Invalid data structure sent with saveDetailTypeGroup method call to saveStructure.php");
                }
                $colNames = $data['dettypegroups']['colNames'];
                foreach ($data['dettypegroups']['defs'] as $dtgID => $rt) {
                    if ($dtgID == -1) {    // new dettype group
                        array_push($rv, createDettypeGroups($colNames, $rt));
                    }else{
                        array_push($rv, updateDettypeGroup($colNames, $dtgID, $rt));
                    }
                }
                if (!array_key_exists('error',$rv)) {
                    $rv['detailTypes'] = getAllDetailTypeStructures();
                }
                break;

            case 'deleteDTG':

                $dtgID = @$_REQUEST['dtgID'];
                if (!$dtgID) {
                    error_exit("Invalid or no detail type group ID sent with deleteDetailType method call to saveStructure.php");
                }
                $rv = deleteDettypeGroup($dtgID);
                if (!array_key_exists('error',$rv)) {
                    $rv['detailTypes'] = getAllDetailTypeStructures();
                }
                break;

                //------------------------------------------------------------

            case 'saveDetailType': // Field (detail) types
            case 'saveDT':
                /*
                 detailtype:{
                        colNames:common:[],
                        defs:[dtyID:{},.... ]
                */
                if (!array_key_exists('detailtype',$data) ||
                    !array_key_exists('colNames',$data['detailtype']) ||
                    !array_key_exists('defs',$data['detailtype'])) {
                    error_exit("Invalid data structure sent with saveDetailType method call to saveStructure.php");
                }
                $commonNames = $data['detailtype']['colNames']['common'];
                $rv['result'] = array(); //result

                foreach ($data['detailtype']['defs'] as $dtyID => $dt) {
                    if ($dtyID == -1) {    // new detailtypes
                        array_push($rv['result'], createDetailTypes($commonNames,$dt));
                    }else{
                        array_push($rv['result'], updateDetailType($commonNames, $dtyID, $dt)); //array($dtyID =>
                    }
                }

                $rv['detailTypes'] = getAllDetailTypeStructures();
                break;

            case 'checkDTusage': //used in editRecStructure to prevent detail type delete


                $rtyID = @$_REQUEST['rtyID'];
                $dtyID = @$_REQUEST['dtyID'];

                $rv = findTitleMaskEntries($rtyID, $dtyID);

                break;

            case 'deleteDetailType':
            case 'deleteDT':
                $dtyID = @$_REQUEST['dtyID'];

                if (!$dtyID) {
                    $rv['error'] = "Error: No IDs or invalid IDs sent with deleteDetailType method call to saveStructure.php";
                }else{
                    $rv = deleteDetailType($dtyID);
                    if (!array_key_exists('error',$rv)) {
                        $rv['detailTypes'] = getAllDetailTypeStructures();
                    }
                }
                break;

                //------------------------------------------------------------

            case 'saveTerms': // Terms

                if (!array_key_exists('terms',$data) ||
                    !array_key_exists('colNames',$data['terms']) ||
                    !array_key_exists('defs',$data['terms'])) {
                    error_exit("Invalid data structure sent with saveTerms method call to saveStructure.php");
                }
                $colNames = $data['terms']['colNames'];
                $rv['result'] = array(); //result

                foreach ($data['terms']['defs'] as $trmID => $dt) {
                    $res = updateTerms($colNames, $trmID, $dt, null);
                    array_push($rv['result'], $res);
                }

                // slows down the performance, but we need the updated terms because Ian wishes to update terms
                // while selecting terms while editing the field type
                $rv['terms'] = getTerms();
                break;

            case 'mergeTerms':    
            
                $retain_id = @$_REQUEST['retain'];
                $merge_id = @$_REQUEST['merge'];
                if($retain_id==null || $merge_id==null || $retain_id == $merge_id){
                    error_exit("Invalid data structure sent with mergeTerms method call to saveStructure.php");
                }
                
                $colNames = $data['terms']['colNames'];
                $dt = @$data['terms']['defs'][$retain_id];
                
                $res = mergeTerms($retain_id, $merge_id, $colNames, $dt);
                
                $rv['terms'] = getTerms();
                break;
                
            case 'deleteTerms':
                $trmID = @$_REQUEST['trmID'];

                if (!$trmID) {
                    $rv['error'] = "Error: No IDs or invalid IDs sent with deleteTerms method call to saveStructure.php";
                }else{
                    $ret = deleteTerms($trmID);
                    if(@$ret['error']){
                        $rv = $ret;
                    }else{
                        $rv['result'] = $ret;
                        $rv['terms'] = getTerms();
                    }
                }
                break;
        }
        $mysqli->close();

        /*
        if (@$rv) {
        print json_format($rv);
        }*/

}//$method!=null

print json_format($rv);
exit();

function error_exit($msg){
    print json_format(array('error'=>$msg));
    exit();
}
?>
