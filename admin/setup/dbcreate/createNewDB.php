<?php

    /**
    *
    * createNewDB.php: Create a new database by applying blankDBStructure.sql and coreDefinitions.txt
    *
    * @package     Heurist academic knowledge management system
    * @link        http://HeuristNetwork.org
    * @copyright   (C) 2005-2014 University of Sydney
    * @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
    * @author      Ian Johnson     <ian.johnson@sydney.edu.au>
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

    /**
    * Extensively modified 4/8/11 by Ian Johnson to reduce complexity and load new database in
    * a series of files with checks on each stage and cleanup code. New database creation functions
    * Oct 2014 by Artem Osmakov to replace command line execution to allow operation on dual tier systems
    */

    define('NO_DB_ALLOWED',1);
    require_once(dirname(__FILE__).'/../../../common/connect/applyCredentials.php');
    require_once(dirname(__FILE__).'/../../../common/php/dbUtils.php');

    // must be logged in anyway to define the master user for the database
    if (!is_logged_in()) {
        $spec_case = "";
        if(HEURIST_DBNAME=='H3Sandpit'){
            //special case - do no show database name
            $spec_case = "&register=1";
        }
        header('Location: ' . HEURIST_BASE_URL . 'common/connect/login.php?db='.HEURIST_DBNAME.$spec_case.'&last_uri='.urlencode(HEURIST_CURRENT_URL) );
        return;
    }

    // clean up string for use in SQL query
    function prepareDbName(){
        $db_name = substr(get_user_username(),0,5);
        $db_name = preg_replace("/[^A-Za-z0-9_\$]/", "", $db_name);
        return $db_name;
    }

?>
<html>
    <head>
        <title>Create New Heurist Database</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="../../../common/css/global.css">
        <link rel="stylesheet" type="text/css" href="../../../common/css/admin.css">
        <link rel="stylesheet" type="text/css" href="../../../common/css/edit.css">


        <style>
            .detailType {width:180px !important}
        </style>

        <script>

            function hideProgress(){
                var ele = document.getElementById("loading");
                if(ele){
                    ele.style.display = "none";
                }
            }


            function showProgress(force){
                var ele = document.getElementById("loading");
                if(force) ele.style.display = "block";
                if(ele.style.display != "none"){
                    ele = document.getElementById("divProgress");
                    if(ele){
                        ele.innerHTML = ele.innerHTML + ".";
                        setTimeout(showProgress, 500);
                    }
                }
            }


            // does a simple word challenge to allow admin to globally restrict new database creation
            function challengeForDB(){
                var pwd_value = document.getElementById("pwd").value;
                if(pwd_value==="<?=$passwordForDatabaseCreation?>"){
                    document.getElementById("challengeForDB").style.display = "none";
                    document.getElementById("createDBForm").style.display = "block";
                }else{
                    alert("Password incorrect, please check with system administrator. Note: password is case sensitive");
                }
            }


            function onKeyPress(event){

                event = event || window.event;
                var charCode = typeof event.which == "number" ? event.which : event.keyCode;
                if (charCode && charCode > 31)
                {
                    var keyChar = String.fromCharCode(charCode);
                    if(!/^[a-zA-Z0-9$_]+$/.test(keyChar)){
                        event.cancelBubble = true;
                        event.returnValue = false;
                        event.preventDefault();
                        if (event.stopPropagation) event.stopPropagation();
                        return false;
                    }
                }
                return true;
            }


            function setUserPartOfName(){
                var ele = document.getElementById("uname");
                if(ele.value==""){
                    ele.value = document.getElementById("ugr_Name").value.substr(0,5);
                }
            }


            function onBeforeSubmit(){
                <?php if(!is_logged_in()) { ?>
                    function __checkMandatory(field, label) {
                        if(document.getElementById(field).value==="") {
                            alert(label+" is mandatory field");
                            document.getElementById(field).focus();
                            return true;
                        }else{
                            return false;
                        }
                    }


                    // check mandatory fields
                    if(
                        __checkMandatory("ugr_FirstName","First name") ||
                        __checkMandatory("ugr_LastName","Last name") ||
                        __checkMandatory("ugr_eMail","Email") ||
                        //__checkMandatory("ugr_Organisation","Institution/company") ||
                        //__checkMandatory("ugr_Interests","Research Interests") ||
                        __checkMandatory("ugr_Name","Login") ||
                        __checkMandatory("ugr_Password","Password")
                    ){
                        return false;
                    }


                    if(document.getElementById("ugr_Password").value!==document.getElementById("ugr_Password2").value){
                        alert("Passwords are different");
                        document.getElementById("ugr_Password2").focus();
                        return false;
                    }
                    <?php } ?>

                var ele = document.getElementById("createDBForm");
                if(ele) ele.style.display = "none";

                showProgress(true);
                return true;
            }

        </script>
    </head>

    <body class="popup">

        <?=(@$_REQUEST['popup']=="1"?"":"<div class='banner'><h2>Create New Database</h2></div>") ?>

        <div id="page-inner" style="overflow:auto">
        <div id="loading" style="display:none">
            <img src="../../../common/images/mini-loading.gif" width="16" height="16" />
            <strong><span id="divProgress">&nbsp;Creating database, please wait</span></strong>
        </div>

        <?php
            $newDBName = "";
            // Used by buildCrosswalks to detemine whether to get data from coreDefinitions.txt (for new database)
            // or by querying an existing Heurist database using getDBStructureAsSQL (for crosswalk)
            $isNewDB = false;

            global $errorCreatingTables; // Set to true by buildCrosswalks if error occurred
            global $done; // Prevents the makeDatabase() script from running twice
            $done = false; // redundant
            $isCreateNew = true;

            if(isset($_POST['dbname'])) {
                $isCreateNew = false;
                $isHuNI = ($_POST['dbtype']=='1');
                $isFAIMS = ($_POST['dbtype']=='2');

                /* TODO: verify that database name is unique
                $list = mysql__getdatabases();
                $dbname = $_POST['uname']."_".$_POST['dbname'];
                if(array_key_exists($dbname, $list)){
                echo "<h3>Database '".$dbname."' already exists. Choose different name</h3>";
                }else{
                */

                echo_flush( '<script type="text/javascript">showProgress(true);</script>' );

                makeDatabase(); // this does all the work <<<*************************************************

                echo_flush( '<script type="text/javascript">hideProgress();</script>' );
            }

            if($isCreateNew){
            ?>

            <div id="challengeForDB" style="<?='display:'.(($passwordForDatabaseCreation=='')?'none':'block')?>;">
                <h3>Enter the password set by your system administrator for new database creation:</h3>
                <input type="password" maxlength="64" size="25" id="pwd">
                <input type="button" onclick="challengeForDB()" value="OK" style="font-weight: bold;" >
            </div>


            <div id="createDBForm" style="<?='display:'.($passwordForDatabaseCreation==''?'block':'none')?>;padding-top:20px;">
                <form action="createNewDB.php?db=<?= HEURIST_DBNAME ?>&popup=<?=@$_REQUEST['popup']?>"
                    method="POST" name="NewDBName" onsubmit="return onBeforeSubmit()">

                    <div style="border-bottom: 1px solid #7f9db9;padding-bottom:10px; padding-top: 10px;">
                        <input type="radio" name="dbtype" value="0" id="rb1" checked="true" /><label for="rb1"
                            class="labelBold">Standard database</label>
                        <div style="padding-left: 38px;padding-bottom:10px">
                            Gives an uncluttered database with essential record & field types. Recommended for general use
                        </div>
                        <input type="radio" name="dbtype" value="1" id="rb2" /><label for="rb2" class="labelBold">HuNI Core schema</label>
                        <div style="padding-left: 38px;">The <a href="http://huni.net.au" target=_blank>
                                Humanities Networked Infrastructure (HuNI)</a>
                            core entities and field definitions, facilitating harvesting into the HuNI aggregate
                        </div>
                        <input type="radio" name="dbtype" value="2" id="rb3" disabled="true"/><label for="rb3" class="labelBold">
                            FAIMS Core schema (not yet available)</label>
                        <div style="padding-left: 38px;">The <a href="http://fedarch.org" target=_blank>
                                Federated Archaeological Information Management System (FAIMS)</a>
                            core entities and field definitions, providing a minimalist framework for archaeological fieldwork databases</div>

                        <p><ul>
                            <li>After the database is created, we suggest visiting Browse Templates and Import Structure menu entries to
                                download pre-configured templates or individual record types and fields.</li>
                            <li>New database creation may take up to 20 seconds. New databases are created on the current server.</li>
                            <li>You will become the owner and administrator of the new database.</li>
                        </ul><p>
                    </div>

                    <h3>Enter a name for the new database:</h3>
                    <div style="margin-left: 40px;">
                        <!-- user name used as prefix -->
                        <b><?= HEURIST_DB_PREFIX ?>
                            <input type="text" maxlength="20" size="6" name="uname" id="uname" onkeypress="{onKeyPress(event)}"
                                style="padding-left:3px; font-weight:bold;" value=<?=(is_logged_in()?prepareDbName():'')?> > _  </b>
                        <input type="text" maxlength="64" size="25" name="dbname"  onkeypress="{onKeyPress(event);}">
                        <input type="submit" name="submit" value="Create database" style="font-weight: bold;"  >
                        <p>The user name prefix is editable, and may be blank, but we suggest using a consistent prefix <br>
                        for personal databases so that all your personal databases appear together in the list of databases.<p></p>
                    </div>
                </form>
            </div> <!-- createDBForm -->
            <?php
            }


            function arraytolower($item)
            {
                return strtolower($item);
            }

            function makeDatabase() { // Creates a new database and populates it with triggers, constraints and core definitions

                global $newDBName, $isNewDB, $done, $isCreateNew, $isHuNI, $isFAIMS, $errorCreatingTables;

                $error = false;
                $warning=false;

                if (isset($_POST['dbname'])) {

                    // Check that there is a current administrative user who can be made the owner of the new database
                    $message = "MySQL username and password have not been set in configIni.php ".
                    "or heuristConfigIni.php<br/> - Please do so before trying to create a new database.<br>";
                    if(ADMIN_DBUSERNAME == "") {
                        if(ADMIN_DBUSERPSWD == "") {
                            echo $message;
                            return;
                        }
                        echo $message;
                        return;
                    }
                    if(ADMIN_DBUSERPSWD == "") {
                        echo $message;
                        return;
                    } // checking for current administrative user


                    // Create a new blank database
                    $newDBName = trim($_POST['uname']).'_';

                    if ($newDBName == '_') {$newDBName='';}; // don't double up underscore if no user prefix
                    $newDBName = $newDBName . trim($_POST['dbname']);
                    $newname = HEURIST_DB_PREFIX . $newDBName; // all databases have common prefix then user prefix

                    $list = mysql__getdatabases();
                    $list = array_map("arraytolower", $list);
                    if(in_array(strtolower($newDBName), $list)){
                        echo ("<p class='error'>Error: database '".$newname."' already exists. Choose different name<br/></p>");
                        $isCreateNew = true;
                        return false;
                    }

                    if(!createDatabaseEmpty($newDBName)){
                        $isCreateNew = true;
                        return false;
                    }


                    // Run buildCrosswalks to import minimal definitions from coreDefinitions.txt into the new DB
                    // yes, this is badly structured, but it works - if it ain't broke ...
                    $isNewDB = true; // flag of context for buildCrosswalks, tells it to use coreDefinitions.txt

                    require_once('../../structure/import/buildCrosswalks.php');

                    // errorCreatingTables is set to true by buildCrosswalks if an error occurred
                    if($errorCreatingTables) {
                        echo ("<p class='error'>Error importing core definitions from ".
                            ($isHuNI?"coreDefinitionsHuNI.txt":(($isFAIMS)?"coreDefinitionsFAIMS.txt":"coreDefinitions.txt")).
                            " for database $newname<br>");
                        echo ("Please check whether this file is valid; consult Heurist support if needed</p>");
                        cleanupNewDB($newname);
                        return false;
                    }

                    // Get and clean information for the user creating the database
                    if(!is_logged_in()) {
                        $longName = "";
                        $firstName = $_REQUEST['ugr_FirstName'];
                        $lastName = $_REQUEST['ugr_LastName'];
                        $eMail = $_REQUEST['ugr_eMail'];
                        $name = $_REQUEST['ugr_Name'];
                        $password = $_REQUEST['ugr_Password'];
                        $department = '';
                        $organisation = '';
                        $city = '';
                        $state = '';
                        $postcode = '';
                        $interests = '';

                        $s = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789./';
                        $salt = $s[rand(0, strlen($s)-1)] . $s[rand(0, strlen($s)-1)];
                        $password = crypt($password, $salt);

                    }else{
                        mysql_connection_insert(DATABASE);
                        $query = mysql_query("SELECT ugr_LongName, ugr_FirstName, ugr_LastName, ugr_eMail, ugr_Name, ugr_Password, " .
                            "ugr_Department, ugr_Organisation, ugr_City, ugr_State, ugr_Postcode, ugr_Interests FROM sysUGrps WHERE ugr_ID=".                                    get_user_id());
                        $details = mysql_fetch_row($query);
                        $longName = mysql_real_escape_string($details[0]);
                        $firstName = mysql_real_escape_string($details[1]);
                        $lastName = mysql_real_escape_string($details[2]);
                        $eMail = mysql_real_escape_string($details[3]);
                        $name = mysql_real_escape_string($details[4]);
                        $password = mysql_real_escape_string($details[5]);
                        $department = mysql_real_escape_string($details[6]);
                        $organisation = mysql_real_escape_string($details[7]);
                        $city = mysql_real_escape_string($details[8]);
                        $state = mysql_real_escape_string($details[9]);
                        $postcode = mysql_real_escape_string($details[10]);
                        $interests = mysql_real_escape_string($details[11]);
                    }

                    //	 todo: code location of upload directory into sysIdentification, remove from edit form (should not be changed)
                    //	 todo: might wish to control ownership rather than leaving it to the O/S, although this works well at present


                    createDatabaseFolders($newDBName);

                    // Prepare to write to the newly created database
                    mysql_connection_insert($newname);

                    // Make the current user the owner and admin of the new database
                    mysql_query('UPDATE sysUGrps SET ugr_LongName="'.$longName.'", ugr_FirstName="'.$firstName.'",
                        ugr_LastName="'.$lastName.'", ugr_eMail="'.$eMail.'", ugr_Name="'.$name.'",
                        ugr_Password="'.$password.'", ugr_Department="'.$department.'", ugr_Organisation="'.$organisation.'",
                        ugr_City="'.$city.'", ugr_State="'.$state.'", ugr_Postcode="'.$postcode.'",
                        ugr_interests="'.$interests.'" WHERE ugr_ID=2');
                    // TODO: error check, although this is unlikely to fail

                    echo "<hr>";
                    echo "<h2>New database '$newDBName' created successfully</h2>";

                    echo "<p><strong>Admin username:</strong> ".$name."<br />";
                    echo "<strong>Admin password:</strong> &#60;<i>same as account currently logged in to</i>&#62;</p>";

                    echo "<p>Click here to log in to your new database: <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><a href=\"".
                    HEURIST_BASE_URL."?db=".$newDBName."\" title=\"\" target=\"_new\">".
                    HEURIST_BASE_URL."?db=".$newDBName.
                    "</a></b>&nbsp;&nbsp;&nbsp;&nbsp; <i>(we suggest bookmarking this link)</i></p>";

                    // TODO: automatically redirect to the new database in a new window
                    // this is a point at which people tend to get lost

                    return false;
                } // isset

            } //makedatabase
        ?>
    </body>
</html>


