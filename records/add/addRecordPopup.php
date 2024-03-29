<?php

    /**
    * addRecordPopup: popup to make selections for adding records to the database
    *
    * @package     Heurist academic knowledge management system
    * @link        http://HeuristNetwork.org
    * @copyright   (C) 2005-2014 University of Sydney
    * @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
    * @author      Ian Johnson     <ian.johnson@sydney.edu.au>
    * @author      Tom Murtagh
    * @author      Kim Jackson
    * @license     http://www.gnu.org/licenses/gpl-3.0.txt GNU License 3.0
    * @version     3.2
    */

    /*
    * Licensed under the GNU License, Version 3.0 (the "License"); you may not use this file except in compliance
    * with the License. You may obtain a copy of the License at http://www.gnu.org/licenses/gpl-3.0.txt
    * Unless required by applicable law or agreed to in writing, software distributed under the License is
    * distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied
    * See the License for the specific language governing permissions and limitations under the License.
    */

    define('SAVE_URI', 'disabled');

    require_once(dirname(__FILE__).'/../../common/connect/applyCredentials.php');
    require_once(dirname(__FILE__).'/../../common/php/dbMySqlWrappers.php');

    if (! is_logged_in()) return;

    mysql_connection_select(DATABASE);

    
    $addRecDefaults   = @$_SESSION[HEURIST_SESSION_DB_PREFIX.'heurist']["display-preferences"]["record-add-defaults"];
//    if(!$addRecDefaults)  //backward cap
//    $addRecDefaults   = @$_SESSION[HEURIST_SESSION_DB_PREFIX.'heurist']["display-preferences"]["addRecordDefaults"]; //backward cap
    if ($addRecDefaults) {
        $defaults = explode(",",$addRecDefaults);
        $showAccessRights = (@$_SESSION[HEURIST_SESSION_DB_PREFIX.'heurist']["display-preferences"]["record-add-showaccess"]!="false");
    }else{
        $showAccessRights = true;
    }
?>

<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <link rel=stylesheet href="<?=HEURIST_SITE_PATH?>common/css/global.css">
        <link rel=stylesheet href="<?=HEURIST_SITE_PATH?>common/css/edit.css">
        <link rel=stylesheet href="<?=HEURIST_SITE_PATH?>common/css/admin.css">
        <title>Add new record</title>

        <script src="<?=HEURIST_SITE_PATH?>external/jquery/jquery.js"></script>

        <script>
            //		rt, wg_id,vis, kwd, tags, restrict Access;
            var defaults = [ <?= ($addRecDefaults ? $addRecDefaults :'') ?>];
            var usrID = <?= get_user_id() ?> ;
            var defAccess = '<?= HEURIST_NEWREC_ACCESS?HEURIST_NEWREC_ACCESS:"viewable"?>';
            var defOwnerID = <?=in_array(HEURIST_NEWREC_OWNER_ID,get_group_ids())?HEURIST_NEWREC_OWNER_ID:0?>;
            $(document).ready(function() {
                $("#show-adv-link").click(function() {
                    $(this).hide();
                    $('#advanced-section').show();
                    return false;
                });

                // assign onchange handle to update_link for values used in link
                $("#rectype_elt, #restrict_elt, #rec_OwnerUGrpID, #tag, #rec_NonOwnerVisibility, #add-link-title, #add-link-tags").
                change(update_link);
                if(defaults && defaults.length > 0){
                    if(defaults[0]){
                        $("#rectype_elt").val(defaults[0]);
                    }
                    if(defaults[2]){
                        $("#rec_NonOwnerVisibility").val(defaults[2]);
                    }
                    if(defaults[4]){
                        $("#add-link-tags").val(defaults[4]);
                    }
                    if(defaults[5]){
                        if(navigator.userAgent.indexOf('Safari')>0){
                            var event = document.createEvent("HTMLEvents");
                            event.initEvent("click", true, true);
                            document.getElementById("restrict_elt").dispatchEvent(event);
                        }else{
                            $("#restrict_elt").click();
                        }
                    }
                    if(defaults[1]){
                        $("#rec_OwnerUGrpID").val(parseInt(defaults[1]));
                    }
                    buildworkgroupTagselect(defaults[1] ? parseInt(defaults[1]) : null, defaults[3] ? decodeURIComponent(defaults[3]) : null );
                }else{
                    var matches = location.search.match(/wg_id=(\d+)/);
                    buildworkgroupTagselect(matches ? matches[1] : null);
                    
                    // now user has to define access right explicitly
                    //$("#rec_NonOwnerVisibility").val(defAccess);
                    //$("#rec_OwnerUGrpID").val(parseInt(defOwnerID));
                }
                update_link();

                showCurrentAccessSettings();
            });

            function buildworkgroupTagselect(wgID, keyword) {
                var i, l, kwd, val;
                $("#tag").empty();
                $("<option value='' selected></option>").appendTo("#tag"); //(select workgroup tag)
                l = top.HEURIST.user.workgroupTagOrder.length;
                for (i = 0; i < l; ++i) {
                    kwd = top.HEURIST.user.workgroupTags[top.HEURIST.user.workgroupTagOrder[i]];
                    if (! wgID  ||  wgID == kwd[0]) {
                        val = top.HEURIST.workgroups[kwd[0]].name + "\\" + kwd[1];
                        $("<option value='" + val + "'"+ (keyword && val == keyword ? " selected":"") +">" + kwd[1] + "</option>").appendTo("#tag");
                    }
                }
            }


            function update_link() {
                var base = "<?= HEURIST_BASE_URL?>records/add/addRecord.php?addref=1&db=<?=HEURIST_DBNAME?>";
                var link = base + compute_args();

                var tags = $("#add-link-tags").val();
                var title = $("#add-link-title").val();

                if (tags) {
                    link += (link.match(/&tag=/))  ?  "," + tags  :  "&tag=" + tags;
                }

                // removed Ian 19/9/08 - title in form is confusing
                // if (title) {
                //	link += "&t=" + title;
                // }
                // added Ian 19/9/08 -  simple guidleine for user of URL - only on the link, not on the insert
                link += "&t=";

                if (! parseInt($("#rectype_elt").val())) {
                    link = "";
                }

                $("#add-link-input").val(link);

                $("#broken-kwd-link").hide();
                //setup link to search for records add to a workgroup with tag by a non-member
                if ($("#tag").val()) {
                    $("#broken-kwd-link").show()[0].href =
                    "../../search/search.html?w=all&q=tag:\"" + $("#tag").val().replace(/\\/, "") + "\"" +
                    " -tag:\"" + $("#tag").val() + "\"";
                }
            }

            function compute_args() {
                var extra_parms = '';
                if (document.getElementById('restrict_elt').checked) {
                    var wg_id = parseInt(document.getElementById('rec_OwnerUGrpID').value);
                    if (!isNaN(wg_id)) {
                        if ( wg_id != usrID) { //by default we use current user
                            extra_parms = '&rec_owner=' + wg_id;
                        }
                        extra_parms += '&rec_visibility=' + document.getElementById('rec_NonOwnerVisibility').value;

                        var kwdList = document.getElementById('tag');
                        var tags = $("#add-link-tags").val();
                        if (wg_id != usrID && kwdList.selectedIndex > 0) {
                            extra_parms += "&tag=" + encodeURIComponent(kwdList.options[kwdList.selectedIndex].value);
                        }
                    }
                }

                rt = parseInt(document.getElementById('rectype_elt').value);

                if (rt) {
                    return '&rec_rectype='+rt + extra_parms;
                }

                return '';
            }

            function addRecord(e) {
                if (! e) e = window.event;

                var extra_parms = '',
                rt;
                var wg_id = parseInt(document.getElementById('rec_OwnerUGrpID').value);
                var vis = document.getElementById('rec_NonOwnerVisibility').value;
                var kwdList = document.getElementById('tag');
                var cbShowAccessRights = document.getElementById('restrict_elt');
                var tags = $("#add-link-tags").val();
                extra_parms = '&rec_owner=' + wg_id;
                extra_parms += '&rec_visibility=' + vis;
                var sError2 = '',
                    sError1 = '';
                if (true || cbShowAccessRights.checked) {
                    if (wg_id>=0) {

                        if (wg_id != usrID && kwdList.selectedIndex > 0) {
                            extra_parms += "&tag=" + encodeURIComponent(kwdList.options[kwdList.selectedIndex].value);
                        }
                    }else {
                        sError1 = 'record owner';
                    }
                }
                if(vis=="-1"){
                       sError2 ='visibility outside of owner group';
                }

                if(sError1!='' || sError2!=''){
                        if(sError1!='' && sError2!=''){
                            sError2 = ' and '+sError2;
                        }
                        alert('Please select '+sError1+sError2);
                        cbShowAccessRights.checked = true;
                        showHideAccessSettings(cbShowAccessRights);
                        //document.getElementById('rec_OwnerUGrpID').focus();
                        //document.getElementById('rec_NonOwnerVisibility').focus();
                        return;
                }
                
                
                rt = parseInt(document.getElementById('rectype_elt').value);
                //Since 2012-12-13 Ian asked to disable it again! if (! rt) rt = <?=RT_NOTE?> ;
                //added ian 19/9/08 to re-enable notes as default
                if(rt<1){
                    alert('Please select record type');
                    return;
                }


                if (tags) {
                    extra_parms += (extra_parms.match(/&tag=/))  ?  "," + tags  :  "&tag=" + tags;
                    // warning! code assumes that &tag= is at the end of string
                }
                if ( <?= @$_REQUEST['related'] ? '1' : '0' ?> ) {
                    extra_parms += '&related=<?= @$_REQUEST['related'] ?>';
                    if (<?= @$_REQUEST['reltype'] ? '1' : '0' ?>) {
                        extra_parms += '&reltype=<?= @$_REQUEST['reltype'] ?>';
                    }
                }
                // added to pass on the title if the user got here from add.php? ... &t=  we just pass it back around
                extra_parms += '<?= @$_REQUEST['t'] ? '&t='.$_REQUEST['t'] : '' ?>';


                if (true || document.getElementById('defaults_elt').checked) {  //always save
                    defaults = [ rt, wg_id,"\"" + vis +"\"", "\"" + encodeURIComponent(kwdList.options[kwdList.selectedIndex].value) +"\"",
                        "\"" + tags + "\"", document.getElementById('restrict_elt').checked?1:0];
                        
                    top.HEURIST.util.setDisplayPreference('record-add-defaults', defaults.join(","));
                    top.HEURIST.util.setDisplayPreference('record-add-showaccess', cbShowAccessRights.checked?"true":"false" );
                }else{
                    top.HEURIST.util.setDisplayPreference('record-add-defaults', "");
                }


                window.open('<?= HEURIST_BASE_URL?>records/add/addRecord.php?addref=1&db=<?=HEURIST_DBNAME?>&rec_rectype='+rt + extra_parms);

            }

            function cancelAdd(e) {
                /*
                if (! e) e = window.event;
                if (document.getElementById('defaults_elt').checked) {//save settings
                    var rt = parseInt(document.getElementById('rectype_elt').value);
                    var wg_id = parseInt(document.getElementById('rec_OwnerUGrpID').value);
                    var vis = document.getElementById('rec_NonOwnerVisibility').value;
                    var kwdList = document.getElementById('tag');
                    var tags = $("#add-link-tags").val();
                    defaults = [ rt, wg_id,"\"" + vis +"\"", "\"" + encodeURIComponent(kwdList.options[kwdList.selectedIndex].value) +"\"",
                        "\"" + tags + "\"", document.getElementById('restrict_elt').checked?1:0];
                    top.HEURIST.util.setDisplayPreference('record-add-defaults', defaults.join(","));
                }else{ //reset saved setting
                    top.HEURIST.util.setDisplayPreference('record-add-defaults', "");
                }
                */
                window.close();

            }

            function showCurrentAccessSettings(){
                var cs = document.getElementById('currSettings');
                cs.innerHTML = "Owner: "+$("#rec_OwnerUGrpID option:selected").text()+'. Visibility: '
                +$("#rec_NonOwnerVisibility option:selected").text()+
                (($("#tag option:selected").text()!='')?'. Tags: '+$("#tag option:selected").text():'');
            }

            function showHideAccessSettings(ele){
                document.getElementById('maintable').className = ele.checked? '' : 'hide_workgroup';
                var cs = document.getElementById('currSettings');
                cs.style.display = ele.checked? 'none' : 'block';
                if(!ele.checked){
                    showCurrentAccessSettings();
                }
            }

        </script>

        <style type=text/css>
            .hide_workgroup .resource.workgroup { display: none !important;overflow:hidden; }
            #add-link-input { width: 100%; }
            #add-link-tags {width : 100%;}
            p {line-height: 14px;}
            .input-cell a {background:none !important; padding:0}
        </style>

    </head>

    <body class="popup" width=500 height=500 style="font-size: 11px;">


        <div id=maintable<?= (@$_REQUEST['wg_id'] > 0 || $showAccessRights) ? "" : " class=hide_workgroup" ?>>
            <div><?php
                    print  ''. @$_REQUEST['error_msg'] ? $_REQUEST['error_msg'] . '' : '' ;
                ?>
            </div>

            <!-- Record type to be added -->
            <div class="input-row" style=" margin-top: 20px;">
                <div class="input-header-cell" style="color:red; font-weight:bold;">Type of record to add</div>
                <div class="input-cell">
                    <?php
                        $res = mysql_query("select distinct rty_ID,rty_Name,rty_Description, rtg_Name
                            from defRecTypes left join defRecTypeGroups on rtg_ID = rty_RecTypeGroupID
                        where rty_ShowInLists = 1 order by rtg_Order, rtg_Name, rty_OrderInGroup, rty_Name");
                    ?>
                    <select name="ref_type" style="font-weight:bold; margin: 3px; margin-bottom:10px;" id="rectype_elt"
                        title="New record type" >
                        <option selected disabled value="0">select ...</option>
                        <?php
                            $section = "";
                            while ($row = mysql_fetch_assoc($res)) {
                                if ($row["rtg_Name"] != $section) {
                                    if ($section) print "</optgroup>\n";
                                    $section = $row["rtg_Name"];
                                    print '<optgroup label="' . htmlspecialchars($section) . '">';
                                }
                            ?>
                            <option value="<?= $row["rty_ID"] ?>"
                                title="<?= htmlspecialchars($row["rty_Description"]) ?>" ><?= htmlspecialchars($row["rty_Name"]) ?></option>
                            <?php
                            }//while
                            if ($section) print "</optgroup>\n";
                        ?>
                    </select>
                </div>
            </div>


            <div class="input-row" style="text-align: center;<?= (@$_REQUEST['wg_id']>0 || $showAccessRights) ? "display:none" : ""?>" id="currSettings">
            </div>

            <div class="input-row">

                <!-- Vsn 3.2 July 2014 - removed checkbox for display of settings, simply display them -->
                <div class="input-header-cell" >Show access settings
                </div>
                <!-- No longer displayed, defaulted to checked -->
                <div class="input-cell" >
                    <input type="checkbox" name="rec_workgroup_restrict" id="restrict_elt" value="1"
                        onclick="showHideAccessSettings(this);"
                        style="vertical-align: middle; margin: 0; padding: 0;"<?= (@$_REQUEST['wg_id']>0 || $showAccessRights) ? " checked" : ""?>>
                </div>

                <div class="resource workgroup" style="margin:10px 0">
                    <div class="input-row workgroup">
                        <div class="input-header-cell">Record owner (group or individual)</div>
                        <div class="input-cell">
                            <select name="rec_OwnerUGrpID" id="rec_OwnerUGrpID" style="width: 200px;"
                                onChange="buildworkgroupTagselect(options[selectedIndex].value)">
<?php
 if(!$addRecDefaults){
                                print '<option value="-1" selected>select...</option>';    
 }
?>
                                <option value="0">Everyone (no restriction)</option>
                                <?php
                                    print "      <option value=".get_user_id().(@$_REQUEST['wg_id']==get_user_id() ? " selected" : "").
                                    ">".htmlspecialchars(get_user_name())." </option>\n";
                                    $res = mysql_query('select '.GROUPS_ID_FIELD.', '.GROUPS_NAME_FIELD.' from '.USERS_DATABASE.'.'.
                                        USER_GROUPS_TABLE.' left join '.USERS_DATABASE.'.'.GROUPS_TABLE.' on '.GROUPS_ID_FIELD.'='.
                                        USER_GROUPS_GROUP_ID_FIELD.' where '.USER_GROUPS_USER_ID_FIELD.'='.get_user_id().' and '.
                                        GROUPS_TYPE_FIELD.'!="Usergroup" order by '.GROUPS_NAME_FIELD);
                                    $wgs = array();
                                    while ($row = mysql_fetch_row($res)) {
                                        print "      <option value=".$row[0].(@$_REQUEST['wg_id']==$row[0] ? " selected" : "").">".
                                        htmlspecialchars($row[1])." </option>\n";
                                        array_push($wgs, $row[0]);
                                    }
                                ?>
                            </select>
                        </div>
                    </div> <!-- resource workgroup -->

                    <div class="input-row workgroup">
                        <div class="input-header-cell">Outside this group record is</div>
                        <div class="input-cell">
                            <select name="rec_NonOwnerVisibility" id="rec_NonOwnerVisibility" style="width: 200px;">
<?php
 if(!$addRecDefaults){
                                print '<option value="-1" selected>select...</option>';    
 }
?>
                                <option value="hidden">Hidden (restricted to owners)</option>
                                <option value="viewable">Viewable (logged-in users only)</option>
                                <option value="pending">Pending (marked for potential publication)</option>
                                <option value="public">Public (automatically published to hml etc.)</option>
                            </select>
                        </div>
                    </div> <!-- input-row workgroup -->

                </div>
            </div>

            <!-- This checkbox hidden vsn 3.2 July 2014 - it makes no sense not to have it checked -->
            <div class="input-row" style="display: none;">
                <div class="input-header-cell" title="Default to these values for future additions (until changed)">Set as defaults</div>
                <div class="input-cell">
                    <input type="checkbox" name="use_as_defaults" id="defaults_elt" value="1" checked
                        style="margin: 0; padding: 0; vertical-align: middle;">

                </div>
            </div>

            <div class="actionButtons">
                <input type="button" class="add" style="float:left; margin-left:250px;" value="Add Record" onClick="addRecord(event);">
                <input type="button" value="Cancel" style="float:left; margin-left:20px; margin-bottom:25px;" onClick="cancelAdd(event);" id="note_cancel">
            </div>

        </div>


        <br />&nbsp;<br />
        <a id="show-adv-link" href="#" style="font-weight: bold; margin-left:40px; ">
            more ... (hyperlink addition, tags)</a>
        <div id=advanced-section style="display: none;">
            <div class="input-row separator">
                <div class="input-header-cell"><strong>Advanced</strong></div>
            </div>
            <div class="input-row">
                <div class="input-header-cell">Add these personal tags</div>
                <div class="input-cell"><input id=add-link-tags></div>
            </div>
            <div class="input-row workgroup">
                <div class="input-header-cell">Add this workgroup tag</div>
                <div class="input-cell"><select name="tag" id="tag" style="width: 200px;"></select></div>
            </div>

            <div class="input-row">
                <div class="input-header-cell">
                    Hyperlink this URL in your web page or a desktop shortcut
                    to provide one-click addition of Heurist records with these characteristics:</div>
                <div class="input-cell"><textarea id=add-link-input style="height:90px"></textarea>
                    <div class="prompt">
                        <a id=broken-kwd-link target=_blank style="display: none;">
                            Click here to search for records added by non workgroup members using the above link</a>
                    </div>
                </div>
            </div>
        </div>



    </body>
</html>
