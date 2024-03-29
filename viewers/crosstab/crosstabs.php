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
*   Corsstabs analysis UI
*
* @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
* @copyright   (C) 2005-2013 University of Sydney
* @link        http://sydney.edu.au/heurist
* @version     3.1.0
* @license     http://www.gnu.org/licenses/gpl-3.0.txt GNU License 3.0
* @package     Heurist academic knowledge management system
*/
?>
<html>
<head>
    <title>Heurist crosstabs</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <link rel=stylesheet href="../../common/css/global.css" media="all">
    <link rel=stylesheet href="crosstabs.css" media="all">

    <script type="text/javascript" src="../../external/jquery/jquery-ui-1.10.2/jquery-1.9.1.js"></script>

</head>
<body style="padding:5px;overflow-x:auto;" class="popup">

  <script src="../../common/js/utilsLoad.js"></script>
  <script src="../../common/php/displayPreferences.php"></script>
  <script src="../../common/php/loadCommonInfo.php"></script>

  <script type="text/javascript" src="../../common/js/utilsUI.js"></script>
  <script type="text/javascript" src="crosstabs.js"></script>

  <div style="margin:0px auto; padding: 0.5em;">

    <div id="qform" class="disign-content" style="width:100%;">
        <div style="position: absolute;top:20px;left:550px"><img src="crosstabs_image.png"/></div>

        <fieldset>
            <div>
                <div class="fldheader"><label for="cbRectypes">Record Type</label></div>
                <div class="input-cell">
                    <select id="cbRectypes" onchange="crosstabsAnlysis.onRectypeChange(event)" class="text ui-widget-content ui-corner-all"></select>
                    <div style="font-size: 0.9em;">Note: choice of record type determines the list of fields avaiable but does not filter the results - the analysis is based on all records in the current query</div>
                </div>
            </div>
        </fieldset>

        <div style="height:2em">&nbsp;</div>

        <div id="nofields" style="padding-left:180px; font-weight:bold;">
            Select record type.
        </div>

        <fieldset id="vars" style="display:none;">
            <div>
                <div class="fldheader"><label for="cbRows">Var 1 (rows)</label></div>
                <div class="input-cell">
                    <select id="cbRows" name="row" onchange="crosstabsAnlysis.resetIntervals(event)" class="text ui-widget-content ui-corner-all" style="display: inline-block;"></select>
                    <button tt='row' class='showintervals collapsed'></button>
                    <div id="rowIntervals" class="ui-corner-all ui-widget-content crosstab-interval">Select field to set intervals</div>
                </div>
            </div>
            <div style="height:2em">&nbsp;</div>
            <div>
                <div class="fldheader"><label for="cbColumns">Var 2 (columns)</label></div>
                <div class="input-cell">
                    <select id="cbColumns" name="column" onchange="crosstabsAnlysis.resetIntervals(event)" class="text ui-widget-content ui-corner-all"></select>
                    <button tt="column" class='showintervals collapsed'></button>
                    <div id="columnIntervals" class="ui-corner-all ui-widget-content crosstab-interval">Select field to set intervals</div>
                </div>
            </div>
            <div style="height:2em">&nbsp;</div>
            <div>
                <div class="fldheader"><label for="cbPages">Var3 (pages)</label></div>
                <div class="input-cell"><select id="cbPages" name="page" onchange="crosstabsAnlysis.resetIntervals(event)" class="text ui-widget-content ui-corner-all"></select>
                    <button tt='page' class='showintervals collapsed'></button>
                    <div id="pageIntervals" class="ui-corner-all ui-widget-content crosstab-interval">Select field to set intervals</div>
                </div>
            </div>
        </fieldset>
        <fieldset id="shows" style="border-color: gray; border-style: solid; border-width: thin;display:none;">

            <div>
                <div class="fldheader"><label for="aggregationMode">Values</label></div>
                <div class="input-cell" style="padding-top: 4px;">

                <div class="crosstab-aggregation" style="margin-left: 0;"><input type="radio" checked value="count" id="aggregationModeCount" name="aggregationMode" onchange="crosstabsAnlysis.changeAggregationMode()">&nbsp;Counts</div>

                <div id="aggSum" class="crosstab-aggregation"><input type="radio" value="sum" name="aggregationMode" onchange="crosstabsAnlysis.changeAggregationMode()">&nbsp;Sum</div>
                <div id="aggAvg" class="crosstab-aggregation"><input type="radio" value="avg" name="aggregationMode" onchange="crosstabsAnlysis.changeAggregationMode()">&nbsp;Average</div>

                <div id="divAggField" class="crosstab-aggregation">of&nbsp;<select id="cbAggField" disabled="disabled" name="column" onchange="crosstabsAnlysis.changeAggregationMode()" class="text ui-widget-content ui-corner-all"></select></div>
                </div>
            </div>

            <div style="height:2em">&nbsp;</div>

            <div>
                <div class="fldheader"><label for="rbShowValue">Show</label></div>
                <div class="input-cell">
                    <input type="checkbox"  style="margin-left: 0;" checked id="rbShowValue">Values
                    <input type="checkbox" id="rbShowPercentRow">Row %
                    <input type="checkbox" id="rbShowPercentColumn">Column %
                    <input type="checkbox" checked id="rbShowTotals">Totals
                </div>
            </div>
            <div>
                <div class="fldheader"></div>
                <div class="input-cell" style="padding-top: 10px;">
                    <input type="checkbox"  style="margin-left: 0;" checked id="rbSupressZero">blank for zero values
                    <input type="checkbox" id="rbShowBlanks">show blank rows/columns
                </div>
            </div>

<!--     OLD WAY
            <div>
                <div class="fldheader"><label for="rbShowValue">Show Values</label></div>
                <div class="input-cell"><input type="checkbox" checked id="rbShowValue"></div>
            </div>
            <div>
                <div class="fldheader"><label for="rbShowPercentRow">Show Percent</label></div>
                <div class="input-cell">

                <input type="checkbox" id="rbShowPercentRow">by row
                <input type="checkbox" id="rbShowPercentColumn">by column</div>
            </div>
            <div>
                <div class="fldheader"><label for="rbShowTotalsRow">Show Totals</label></div>
                <div class="input-cell">
                <input type="checkbox" id="rbShowTotalsRow">by row
                <input type="checkbox" id="rbShowTotalsColumn">by column</div>
            </div>
            <div>
                <div class="fldheader"><label for="rbSupressZero">Show blank for zero values</label></div>
                <div class="input-cell"><input type="checkbox" checked id="rbSupressZero"></div>
            </div>
            <div>
                <div class="fldheader"><label for="rbSupressBlankRow">Hide blank</label></div>
                <div class="input-cell">
                    <input type="checkbox"  id="rbSupressBlankRow">rows
                    <input type="checkbox"  id="rbSupressBlankColumn">columns
                    <input type="checkbox"  id="rbSupressBlankPage">pages
                </div>
            </div>
-->
        </fieldset>
        <div style="text-align:center;padding-top:1em; display:none;" id="btnPanels">
            <button onclick="crosstabsAnlysis.doSave()">Save</button>
            <button onclick="crosstabsAnlysis.doCancel()">Cancel</button>
            <button onclick="crosstabsAnlysis.doRetrieve()">Run</button>
        </div>
    </div>

    <div id="inporgress" style="display:none;">
        <div id="pmessage" class="progress-message">Requesting</div>
    </div>

    <div id="divres" class="output-content" style="display:none;">
    </div>

  </div>

<script  type="text/javascript">

$(document).ready(function() {
    crosstabsAnlysis = CrosstabsAnlysis('<?=$_REQUEST['db']?>', '<?=@$_REQUEST['q']?>', '<?=@$_REQUEST['w']?>');
});
</script>

</body>
</html>
