<?php

    /** 
    * Application interface. See hRecordMgr in hapi.js           
    * record manipulation - add, save, delete
    * 
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


    require_once (dirname(__FILE__).'/../System.php');
    require_once (dirname(__FILE__).'/../common/db_records.php');

    $response = array();

    $system = new System();
    if( ! $system->init(@$_REQUEST['db']) ){

        //get error and response
        $response = $system->getError();

    }else{

        $mysqli = $system->get_mysqli();

        if ( $system->get_user_id()<1 ) {

            $response = $system->addError(HEURIST_REQUEST_DENIED);

        }else{

            $action = @$_REQUEST['a'];// || @$_REQUEST['action'];

            // call function from db_record library
            // these function returns standard response: status and data
            // data is recordset (in case success) or message

            if($action=="a" || $action=="add"){

                $record = array();
                $record['RecTypeID'] = @$_REQUEST['rt'];
                $record['OwnerUGrpID'] = @$_REQUEST['ro'];
                $record['NonOwnerVisibility'] =  @$_REQUEST['rv'];

                $response = recordAdd($system, $record);

            } else if ($action=="s" || $action=="save") {

                $response = recordSave($system, $_REQUEST);

            } else if (($action=="d" || $action=="delete") && @$_REQUEST['ids']){

                $response = recordDelete($system, $_REQUEST['ids']);

            } else {

                $response = $system->addError(HEURIST_INVALID_REQUEST);
            }
        }
    }

    header('Content-type: text/javascript');
    print json_encode($response);
    exit();
?>
