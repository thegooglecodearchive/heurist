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


	require_once(dirname(__FILE__).'/../../common/connect/applyCredentials.php');
	require_once(dirname(__FILE__).'/../../common/php/dbMySqlWrappers.php');
	require_once(dirname(__FILE__).'/../../search/getSearchResults.php');

	//if (! is_admin()) return;
	if (! is_logged_in()) return;


	$fuzziness = intval($_REQUEST['fuzziness']);
	if (! $fuzziness) $fuzziness = 20;

	$bibs = array();
	$dupes = array();
	$dupekeys = array();
	$recsGivenNames = array();
	$dupeDifferences = array();

	$recIDs = null;
	$result = null;
	if (@$_REQUEST['q']){
		$_REQUEST['l'] = -1;  // tell the loader we want all the ids
		$result = loadSearch($_REQUEST,false,true); //get recIDs for the search
		if ($result['resultCount'] > 0 && $result['recordCount'] > 0) {
			$recIDs = $result['recIDs'];
		}
	}
	// error output
	if (!@$_REQUEST['q'] || array_key_exists("error", @$result) || @$result['resultCount'] != @$result['recordCount']){
		//error has occured tell the user and stop
	?>

	<html>
		<head>
			<title>Heurist duplicate records for search results set</title>
            <meta http-equiv="content-type" content="text/html; charset=utf-8">
			<link rel=stylesheet type=text/css href='<?=HEURIST_SITE_PATH?>common/css/global.css'>
			<link rel=stylesheet type=text/css href='<?=HEURIST_SITE_PATH?>common/css/publish.css'>

			<style type="text/css">
				.banner h2{padding:5px 10px; display: table-cell; vertical-align: middle;}
			</style>

		</head>
		<body class="popup">

			<!--<a id=home-link href='../../'>
			<div id=logo title="Click the logo at top left of any Heurist page to return to your Favourites"></div>
			</a>
			<div id=page>
			<div class="banner">
			<h2>Duplicate records in search results set</h2>
			</div>
			An error occured while retrieving the set of records to compare:
			-->
			<?php
				if (!@$_REQUEST['q']){
					print "You must supply a query in order to specify the search set of records.";
				} else if (array_key_exists("error", $result)){
					print "Error loading search: " . $result['error'];
				} else if (@$result['resultCount'] != @$result['recordCount']){
					print " The number of recIDs returned is not equal to the total number in the query result set.";
				}
			?>

			<!--</div>-->
		</body>
	</html>

	<?php
		return;
	} // end of error output

	mysql_connection_insert(DATABASE);

	$res = mysql_query('select snd_SimRecsList from recSimilarButNotDupes');
	while ($row = mysql_fetch_assoc($res)){
		array_push($dupeDifferences,$row['snd_SimRecsList']);
	}

	if ($_REQUEST['dupeDiffHash']){
		foreach($_REQUEST['dupeDiffHash'] as $diffHash){
			if (! in_array($diffHash,$dupeDifferences)){
				array_push($dupeDifferences,$diffHash);
				$res = mysql_query('insert into recSimilarButNotDupes values("'.$diffHash.'")');
			}
		}
	}

	mysql_connection_select(DATABASE);
	//mysql_connection_select("`heuristdb-nyirti`");   //for debug
	//FIXME  allow user to select a single record type
	//$res = mysql_query('select rec_ID, rec_RecTypeID, rec_Title, dtl_Value from Records left join recDetails on dtl_RecID=rec_ID and dtl_DetailTypeID=160 where rec_RecTypeID != 52 and rec_RecTypeID != 55 and not rec_FlagTemporary order by rec_RecTypeID desc');
	$crosstype = false;
	$personMatch = false;
	$relRT = (defined('RT_RELATION')?RT_RELATION:0);
	$perRT = (defined('RT_PERSON')?RT_PERSON:0);
	$surnameDT = (defined('DT_GIVEN_NAMES')?DT_GIVEN_NAMES:0);
	$titleDT = (defined('DT_NAME')?DT_NAME:0);

	if (@$_REQUEST['crosstype']){
		$crosstype = true;
	}
	if (@$_REQUEST['personmatch']){
		$personMatch = true;
		$res = mysql_query("select rec_ID, rec_RecTypeID, rec_Title, dtl_Value from Records left join recDetails on dtl_RecID=rec_ID and dtl_DetailTypeID=$surnameDT where ". (strlen($recIDs) > 0 ? "rec_ID in ($recIDs) and " : "") ."rec_RecTypeID = $perRT and not rec_FlagTemporary order by rec_ID desc");    //Given Name
		while ($row = mysql_fetch_assoc($res)) {
			$recsGivenNames[$row['rec_ID']] = $row['dtl_Value'];
		}
		$res = mysql_query("select rec_ID, rec_RecTypeID, rec_Title, dtl_Value from Records left join recDetails on dtl_RecID=rec_ID and dtl_DetailTypeID=$titleDT where ". (strlen($recIDs) > 0 ? "rec_ID in ($recIDs) and " : "") ."rec_RecTypeID = $perRT and not rec_FlagTemporary order by dtl_Value asc");    //Family Name

	} else{
		$res = mysql_query("select rec_ID, rec_RecTypeID, rec_Title, dtl_Value from Records left join recDetails on dtl_RecID=rec_ID and dtl_DetailTypeID=$titleDT where ". (strlen($recIDs) > 0 ? "rec_ID in ($recIDs) and " : "") ."rec_RecTypeID != $relRT and not rec_FlagTemporary order by rec_RecTypeID desc");
	}

	$rectypes = mysql__select_assoc('defRecTypes', 'rty_ID', 'rty_Name', '1');

	while ($row = mysql_fetch_assoc($res)) {
		if ($personMatch){
			if($row['dtl_Value']) $val = $row['dtl_Value'] . ($recsGivenNames[$row['rec_ID']]? " ". $recsGivenNames[$row['rec_ID']]: "" );
		}else {
			if ($row['rec_Title']) $val = $row['rec_Title'];
			else $val = $row['dtl_Value'];
		}
		$mval = metaphone(preg_replace('/^(?:a|an|the|la|il|le|die|i|les|un|der|gli|das|zur|una|ein|eine|lo|une)\\s+|^l\'\\b/i', '', $val));

		if ($crosstype || $personMatch) { //for crosstype or person matching leave off the type ID
			$key = ''.substr($mval, 0, $fuzziness);
		} else {
			$key = $row['rec_RecTypeID'] . '.' . substr($mval, 0, $fuzziness);
		}

		$typekey = $rectypes[$row['rec_RecTypeID']];

		if (! array_key_exists($key, $bibs)) $bibs[$key] = array(); //if the key doesn't exist then make an entry for this metaphone
		else { // it's a dupe so process it
			if (! array_key_exists($typekey, $dupes)) $dupes[$typekey] = array();
			if (!array_key_exists($key,$dupekeys))  {
				$dupekeys[$key] =  1;
				array_push($dupes[$typekey],$key);
			}
		}
		// add the record to bibs
		$bibs[$key][$row['rec_ID']] = array('type' => $typekey, 'val' => $val);
	}

	ksort($dupes);
	foreach ($dupes as $typekey => $subarr) {
		array_multisort($dupes[$typekey],SORT_ASC,SORT_STRING);
	}

?><html>
	<head>
		<title>Heurist duplicate records for search results set</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel=stylesheet type=text/css href='<?=HEURIST_SITE_PATH?>common/css/global.css'>
		<link rel=stylesheet type=text/css href='<?=HEURIST_SITE_PATH?>common/css/publish.css'>

		<style type="text/css">
			.banner h2{padding:5px 10px; display: table-cell; vertical-align: middle;}
		</style>
		<script language="javascript">
			function fixDupes(url) {
				top.HEURIST.search.popupLink(url);
			}
		</script>
	</head>
	<body class="popup">

		<!--<a id=home-link href='../../'>
		<div id=logo title="Click the logo at top left of any Heurist page to return to your Favourites"></div>
		</a>
		<div id=page>
		<div class="banner">
		<h2>Duplicate records in search results set</h2>
		</div>-->
		<form>
			<div class="wizard-box roundedBoth">
				Select fuzziness: <select name="fuzziness" id="fuzziness" onChange="form.submit();">
					<option value=3>3</option>
					<option value=4 <?= $fuzziness == 4  ? "selected" : "" ?>>4</option>
					<option value=5 <?= $fuzziness == 5 ? "selected" : "" ?>>5</option>
					<option value=6 <?= $fuzziness == 6 ? "selected" : "" ?>>6</option>
					<option value=7 <?= $fuzziness == 7 ? "selected" : "" ?>>7</option>
					<option value=8 <?= $fuzziness == 8 ? "selected" : "" ?>>8</option>
					<option value=9 <?= $fuzziness == 9 ? "selected" : "" ?>>9</option>
					<option value=10 <?= $fuzziness >= 10 && $fuzziness < 12 ? "selected" : "" ?>>10</option>
					<option value=12 <?= $fuzziness >= 12 && $fuzziness < 15 ? "selected" : "" ?>>12</option>
					<option value=15 <?= $fuzziness >= 15 && $fuzziness < 20 ? "selected" : "" ?>>15</option>
					<option value=20 <?= $fuzziness >= 20 && $fuzziness < 25 ? "selected" : "" ?>>20</option>
					<option value=25 <?= $fuzziness >= 25 && $fuzziness < 30 ? "selected" : "" ?>>25</option>
					<option value=30 <?= $fuzziness >= 30 ? "selected" : "" ?>>30</option>
				</select>
				<input type="hidden" name="db" id="db" value="<?=HEURIST_DBNAME?>">
				characters of metaphone must match
				<div id=searchString>Search string: <input type="text" name="q" id="q" value="<?= @$_REQUEST['q'] ?>" /></div>
			</div>
			<div style="padding:5px">
				<p>Cross-record-type matching will attemp to match titles of different record types. This is potentially a long search with many matching results. Increasing fuzziness will reduce the number of matches.<p>
				<div style="padding:5px"><input type="checkbox" class="options" name="crosstype" id="crosstype" value=1 <?= $crosstype ? "checked" : "" ?>  onclick="form.submit();"> Match across record types</div>
				<div style="padding:5px"><input type="checkbox" class="options" name="personmatch" id="personmatch" value=1   onclick="form.submit();"> Match people by surname first</div>
			</div>
			<?php
				if (@$_REQUEST['w']) {
				?>
				<input type="hidden" name="w" id="w" value="<?= $_REQUEST['w'] ?>" />
				<?php
				}
			?>
			<?php
				if (@$_REQUEST['ver']) {
				?>
				<input type="hidden" name="ver" id="ver" value="<?= $_REQUEST['ver'] ?>" />
				<?php
				}
			?>
			<?php
				if (@$_REQUEST['stype']) {
				?>
				<input type="hidden" name="stype" id="stype" value="<?= $_REQUEST['stype'] ?>" />
				<?php
				}
			?>
			<?php
				if (@$_REQUEST["db"]) {
				?>
				<input type="hidden" name="db" id="db" value="<?= $_REQUEST["db"] ?>" />
				<?php
				}

				unset($_REQUEST['personmatch']);

				$cnt = 0;
				foreach ($dupes as $rectype => $subarr) {
					foreach ($subarr as $index => $key) {
					$diffHash = array_keys($bibs[$key]);
					sort($diffHash,SORT_ASC);
					$diffHash = join(',',$diffHash );
					if (in_array($diffHash,$dupeDifferences)) continue;
						$cnt ++;
					}
				}
				print '<div id=dupeCount>' . $cnt . ' potential groups of duplicates</div><div class=duplicateList>';

				print "<div>Note dupes button applies to one set. To apply to several sets at once, check the boxes then click any <b>not dupes</b> button</div>";

				foreach ($dupes as $rectype => $subarr) {
					foreach ($subarr as $index => $key) {
						$diffHash = array_keys($bibs[$key]);
						sort($diffHash,SORT_ASC);
						$diffHash = join(',',$diffHash );
						if (in_array($diffHash,$dupeDifferences)) continue;
						print '<div class=duplicateGroup>';
						print '<div style="font-weight: bold;"><input type="checkbox" name="dupeDiffHash[] title="Check to indicate that all records in this set are not duplicates of one another." id="'.$key.
						'" value="' . $diffHash . '">&nbsp;&nbsp;';
						print $rectype . ' &nbsp;&nbsp;&nbsp;&nbsp;';
						print '<input type="button" value="&nbsp;not dupes&nbsp;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
						print '<a onClick=top.HEURIST.search.popupLink("'.HEURIST_BASE_URL.'admin/verification/combineDuplicateRecords.php?bib_ids=' . join(',', array_keys($bibs[$key])).'","small")>merge this group</a>&nbsp;&nbsp;&nbsp;&nbsp;';
						print '<a title="View in new search window" target="_new" href="'.HEURIST_BASE_URL.'search/search.html?q=ids:'.join(",",array_keys($bibs[$key])).'&db='.HEURIST_DBNAME.'"><img src="'.HEURIST_BASE_URL.'common/images/jump.png"></a>&nbsp;&nbsp;&nbsp;&nbsp;';
						print '</div>';
						print '<ul>';
						foreach ($bibs[$key] as $rec_id => $vals) {
							$res = mysql_query('select rec_URL from Records where rec_ID = ' . $rec_id);
							$row = mysql_fetch_assoc($res);
							print '<li>'.($crosstype ? $vals['type'].'&nbsp;&nbsp;' : '').
							'<a target="_new" href="'.HEURIST_BASE_URL.'records/view/viewRecord.php?saneopen=1&recID='.$rec_id.'&db='.HEURIST_DBNAME.'">'.$rec_id.': '.htmlspecialchars($vals['val']).'</a>';
							if ($row['rec_URL'])
							print '&nbsp;&nbsp;&nbsp;<span style="font-size: 70%;">(<a target="_new" href="'.$row['rec_URL'].'">' . $row['rec_URL'] . '</a>)</span>';
							print '</li>';
						}
						print '</ul>';
						print '</div>';
					}
				}
			?>
			</div>
		</form>
		</div>
	</body>
</html>
