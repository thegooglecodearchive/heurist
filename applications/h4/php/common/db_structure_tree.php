<?php
    /** 
    * @package     Heurist academic knowledge management system
    * @link        http://HeuristNetwork.org
    * @copyright   (C) 2005-2014 University of Sydney
    * @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
    * @license     http://www.gnu.org/licenses/gpl-3.0.txt GNU License 3.0
    * @version     4.0      
    */

    /*
    * Licensed under the GNU License, Version 3.0 (the "License"); you may not use this file except in compliance
    * with the License. You may obtain a copy of the License at http://www.gnu.org/licenses/gpl-3.0.txt
    * Unless required by applicable law or agreed to in writing, software distributed under the License is
    * distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied
    * See the License for the specific language governing permissions and limitations under the License.
    */

    /**
    * Generates an array - list and treeview of fields for given recordtypes 
    * used in smarty editor, titlemask editor, facet search wizard
    *
    * dbs_ - prefix for functions
    * 
    * main function: rtt_GetRectypeStructureTree
    * 
    * Internal function
    *  __getRecordTypeTree
    *  __getDetailSection
    */

    $dbs_rtStructs = null;
    $dbs_lookups = null; //human readale field type names

    /**
    * Returns  
    * {
    *   id          : rectype id, field id in form fNNN, name of default rectype field
    *   text        : rectype or field display name
    *   type        : rectype|Relationship|field type|term
    *   children    : []  // array of fields
    * }
    * 
    * @param mixed $system
    * @param mixed $rectypeids
    * @param mixed $mode  3 - all, 4 - for faceted search (with type names)
    */
    function dbs_GetRectypeStructureTree($system, $rectypeids, $mode, $fieldtypes=null){

        global $dbs_rtStructs, $dbs_lookups;

        if($fieldtypes==null){
            $fieldtypes = array('integer','date','freetext','year','float');
        }else if(!is_array($fieldtypes)){
            $fieldtypes = explode(",",$fieldtypes);
        }

        $dbs_rtStructs = dbs_GetRectypeStructures($system, $rectypeids, 1);   
        $dbs_lookups = dbs_GetDtLookups();

        $rtypes = $dbs_rtStructs['names'];
        $res = array();

        foreach ($rtypes as $rectypeID=>$rectypeName){
            array_push($res, __getRecordTypeTree($system, $rectypeID, 0, $mode, $fieldtypes));    
        }

        return $res;    
    }

    //
    //   {rt_id: , rt_name, recID, recTitle, recModified, recURL, ecWootText,
    //                  fNNN: 'display name of field', 
    //                  fNNN: array(termfield_name: , id, code:  )  // array of term's subfields
    //                  fNNN: array(rt_name: , recID ...... )       // unconstrained pointer or exact constraint
    //                  fNNN: array(array(rt_id: , rt_name, recID, recTitle ... ) //constrined pointers
    //     NNN - field type ID
    function __getRecordTypeTree($system, $recTypeId, $recursion_depth, $mode, $fieldtypes){

        global $dbs_rtStructs;

        $res = array();
        $children = array();
        //add default fields
        if($mode==3) array_push($children, array('key'=>'recID', 'type'=>'integer', 'title'=>'ID'));
        array_push($children, array('key'=>'recTitle',    'type'=>'freetext',  'title'=>'RecTitle'));
        array_push($children, array('key'=>'recModified', 'type'=>'date',      'title'=>'Modified'));
        if($mode==3) {
            array_push($children, array('key'=>'recURL',      'type'=>'freetext',  'title'=>'URL'));
            array_push($children, array('key'=>'recWootText', 'type'=>'blocktext', 'title'=>'WootText'));
        }

        if($recTypeId && is_numeric($recTypeId)){

            if(!@$dbs_rtStructs['typedefs'][$recTypeId]){
                //this rectype is not loaded yet - load it
                $rt0 = dbs_GetRectypeStructures($system, $recTypeId, 1);
                if(rt0){ //merge with $dbs_rtStructs 
                    $dbs_rtStructs['typedefs'][$recTypeId] = $rt0['typedefs'][$recTypeId];    
                    $dbs_rtStructs['names'][$recTypeId] = $rt0['names'][$recTypeId];
                }
            }

            $res['key'] = $recTypeId;
            $res['title'] = $dbs_rtStructs['names'][$recTypeId];
            $res['type'] = 'rectype';

            if(@$dbs_rtStructs['typedefs'][$recTypeId]){
                $details =  $dbs_rtStructs['typedefs'][$recTypeId]['dtFields'];

                foreach ($details as $dtID => $dtValue){

                    $res_dt = __getDetailSection($system, $dtID, $dtValue, $recursion_depth, $mode, $fieldtypes);
                    if($res_dt){
                        array_push($children, $res_dt);
                        /*
                        if(is_array($res_dt) && count($res_dt)==1){
                        $res["f".$dtID] = $res_dt[0];    
                        }else{
                        //multi-constrained pointers or simple variable
                        $res["f".$dtID] = $res_dt;
                        }
                        */
                    }
                }//for
            }
            if($mode==3 && $recursion_depth==0){
                array_push($children, __getRecordTypeTree($system, 'Relationship', $recursion_depth+1, $mode, $fieldtypes));
            }   

        }else if($recTypeId=="Relationship") {

            $res['title'] = "Relationship";
            $res['type'] = "relationship";

            //add specific Relationship fields
            array_push($children, array('key'=>'recRelationType', 'title'=>'RelationType'));
            array_push($children, array('key'=>'recRelationNotes', 'title'=>'RelationNotes'));
            array_push($children, array('key'=>'recRelationStartDate', 'title'=>'RelationStartDate'));
            array_push($children, array('key'=>'recRelationEndDate', 'title'=>'RelationEndDate'));
        }

        $res['children'] = $children;

        return $res;
    }

    /*
    $dtID   - detail type ID

    $dtValue - record type structure definition

    returns display name  or if enum array

    $mode - 3 all, 4 for facet treeview
    */
    function __getDetailSection($system, $dtID, $dtValue, $recursion_depth, $mode, $fieldtypes){

        global $dbs_rtStructs, $dbs_lookups;    

        $res = null;

        $rtNames = $dbs_rtStructs['names']; //???need
        $rst_fi = $dbs_rtStructs['typedefs']['dtFieldNamesToIndex'];


        $detailType = $dtValue[$rst_fi['dty_Type']];
        $dt_label   = $dtValue[$rst_fi['rst_DisplayName']];
        $dt_title   = $dtValue[$rst_fi['rst_DisplayName']];
        $dt_tooltip = $dtValue[$rst_fi['rst_DisplayHelpText']]; //help text

        //$dt_maxvalues = $dtValue[$rst_fi['rst_MaxValues']]; //repeatable
        //$issingle = (is_numeric($dt_maxvalues) && intval($dt_maxvalues)==1)?"true":"false";
        //error_log("1>>>".$mode."  ".$detailType."  ".$dt_label);

        switch ($detailType) {
            /* @TODO
            case 'file':
            break;
            case 'geo':
            break;
            case 'calculated':
            break;
            case 'fieldsetmarker':
            break;
            case 'relationtype':
            */
            case 'separator':
                return null;
            case 'enum':

                $res = array();
                if($mode==3){
                    $res['children'] = array(
                        array("text"=>"internalid"),
                        array("text"=>"code"),
                        array("text"=>"term"),
                        array("text"=>"conceptid"));
                }
                break;

            case 'resource': // link to another record type
            case 'relmarker':

                if($recursion_depth<2){


                    if($mode==4){
                        $dt_title = " <span style='font-style:italic'>" . $dt_title . "</span>";
                    }


                    $pointerRecTypeId = $dtValue[$rst_fi['rst_PtrFilteredIDs']];
                    $rectype_ids = explode(",", $pointerRecTypeId);

                    if($pointerRecTypeId=="" || count($rectype_ids)==0){ //unconstrainded

                        $res = __getRecordTypeTree($system, null, $recursion_depth+1, $mode, $fieldtypes);

                    }else{ //constrained pointer

                        $res = array();


                        if(count($rectype_ids)>1){
                            $res['rt_ids'] = $pointerRecTypeId; //list of rectype - constraint
                            $res['children'] = array();
                        }

                        foreach($rectype_ids as $rtID){
                            $rt_res = __getRecordTypeTree($system, $rtID, $recursion_depth+1, $mode, $fieldtypes);
                            if(count($rectype_ids)==1){//exact one rectype constraint
                                //avoid redundant level in tree
                                $res = $rt_res;
                                $res['rt_ids'] = $pointerRecTypeId; //list of rectype - constraint
                            }else{
                                array_push($res['children'], $rt_res);
                            }
                        }


                    }
                }

                break;

            default:
                //error_log("2>>>".$mode."  ".$detailType."  ".$dt_label."   ".($detailType=='float'));            
                if (($mode==3) ||  in_array($detailType, $fieldtypes))
                {
                    //error_log("!!!!!!!!!");                    
                    $res = array();
                }
        }//end switch

        //error_log("3>>>>".is_array($res)."<  ".$detailType."  ".$dt_label);
        if(is_array($res)){

            $res['key'] = "f:".$dtID;
            if($mode==4){
                $res['title'] = $dt_title . " <span style='font-size:0.7em'>(" . $dbs_lookups[$detailType] . ")</span>";   
            }else{
                $res['title'] = $dt_title;    
            }
            $res['type'] = $detailType;
            $res['name'] = $dt_label;
        }            
        return $res;
    }

