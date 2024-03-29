<?php

    /**
    * Rule Set Builder Dialog
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


    require_once(dirname(__FILE__)."/../php/System.php");
    //require_once(dirname(__FILE__).'/../php/common/db_structure.php'); //may be removed

    $system = new System();

    if(@$_REQUEST['db']){
        if(! $system->init(@$_REQUEST['db']) ){
            //@todo - redirect to error page
            print_r($system->getError(),true);
            exit();
        }
    }else{
        header('Location: /../php/databases.php');
        exit();
    }
?>
<html>
    <head>
        <title>Rule Set Builder</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

        <link rel="stylesheet" type="text/css" href="../ext/jquery-ui-1.10.2/themes/heurist/jquery-ui.css" />
        <link rel="stylesheet" type="text/css" href="../style3.css">

        <script type="text/javascript" src="../ext/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
        <script type="text/javascript" src="../ext/jquery-ui-1.10.2/ui/jquery-ui.js"></script>


        <script type="text/javascript" src="../js/recordset.js"></script>
        <script type="text/javascript" src="../js/utils.js"></script>
        <script type="text/javascript" src="../js/hapi.js"></script>

        <script type="text/javascript" src="../apps/search/ruleBuilder.js"></script>
        <script type="text/javascript" src="../apps/search/resultList.js"></script>

        <script type="text/javascript">
            var db =  '<?=$_REQUEST['db']?>';
            var rules = <?=@$_REQUEST['rules']?$_REQUEST['rules']:'[]'?>;
        </script>
        <script type="text/javascript" src="ruleBuilderDialog.js"></script>

        <style>
            .rulebuilder{
                display: block !important;
                padding:4 4 4 0;
                width:99% !important;
                text-align:left !important;
            }
            .rulebuilder>div{
                text-align:left;
                display: inline-block;
                width:200px;
            }
            .rulebuilder>div>select{
                min-width:180px;
                max-width:180px;
            }
        </style>
    </head>
    <body style="overflow:hidden">
        <div style="height:100%">
            <div style="position:absolute;width:99%;top:0" class="rulebuilder">
                <div style="width:220px;font-weight:bold">Starting point (entity type)</div>
                <div style="width:195px;font-weight:bold">Relationship Field</div>
                <div style="width:195px;font-weight:bold">Relationship Type</div>
                <div style="width:195px;font-weight:bold">Target entity type</div>
                <div style="font-weight:bold">Optional Filter (query)</div>
            </div>

            <div style="position:absolute;width:99%;top:2em;bottom:4em;overflow-y:auto" id="level1">
            
                <div id="div_add_level"><button id="btn_add_level1">Add New Rule</button></div>
            </div>
            

            <!-- Slide-in help text - displays at start by default, can be closed and reopened -->
            <div id="helper" title="Rules and Rule sets">
                <!-- <div id="helper" class="ui-widget-content ui-corner-all">
                <h3 class="ui-widget-header ui-corner-all">Rules and Rule sets</h3> -->
                <p style="padding-bottom:1em">

                    A <b>rule</b> describes the set of pointers and relationships (including reverse pointers)
                    to follow from each of the records in the initial selection set (ie. the results of
                    a search) in order to add related records to the current result set. A rule can
                    comprise several steps out from the initial selection set.
                </p>
                <p style="padding-bottom:1em">
                    For instance a rule building on a search that retrieved a set of menus might add a
                    number of recipes referenced by the menu which in turn might add a number of ingredients
                    referenced by the recipe; however this rule will not add the restaurants which reference
                    the menus or the chefs referenced by the recipes as their creator. These would each be
                    a separate rule starting from the initial set of menus (Menus << Restaurants and
                    Menus >> Recipes >> Creators).
                </p>
                <p style="padding-bottom:1em">
                    <b>Rule Sets</b> may contain several rules. Rule sets can be saved - they appear in the Rule Sets
                    section of the navigation panel - and can be applied to any set of records retrieved by a
                    search. Rule sets are also saved as part of the saved search when the search is saved
                    after applying a rule set.
                </p>
                <p style="padding-bottom:1em">
                    When a rule set is applied, it operates on the initial selection set resulting from the query.
                    Each rule set applied <b>replaces</b> the results of the previous rule set - they are not additive.
                    If you need to apply several rules to build the set of records you require, they should all
                    be defined in one rule set.
                </p>
            </div>


            <div style="position:absolute;width:99%;height:2em;bottom:10px; text-align:right">
                
                <button id="btn_save">Save Rule Set</button>
                <!-- <button id="btn_apply">Apply Rules</button> -->
                <button id="btn_help">Help</button>
            </div>
        </div>
    </body>
</html>
