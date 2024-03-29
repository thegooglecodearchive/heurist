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


/* not suited to t-1000 */

define('SAVE_URI', 'disabled');

require_once(dirname(__FILE__).'/../../common/connect/applyCredentials.php');
//require_once(dirname(__FILE__).'/../../common/php/dbMySqlWrappers.php');

require_once(dirname(__FILE__).'/../../records/disambig/testSimilarURLs.php');
require_once(dirname(__FILE__).'/../../records/files/fileUtils.php');
require_once(dirname(__FILE__).'/../../search/actions/actionMethods.php');

//require_once(dirname(__FILE__).'/../../common/t1000/.ht_stdefs');

if (! is_logged_in()) {
	header('Location: ' . HEURIST_BASE_URL . 'common/connect/login.php?db='.HEURIST_DBNAME);
	return;
}

mysql_connection_overwrite(DATABASE);

$nextmode = 'inputselect';

if (@$_REQUEST['shortcut']) {

	$_REQUEST['mode'] = 'Analyse';
	$_REQUEST['source'] = 'url';
	$_REQUEST['url'] = $_REQUEST['shortcut'];

}

if (@$_REQUEST['old_srcname']){
	$srcname = $_REQUEST['old_srcname'];
}


if (@$_REQUEST['mode'] == 'Analyse') {
	if (@$_REQUEST['source'] == 'file') {
		$src = file_get_contents($_FILES['file']['tmp_name']);
		$srcname = $_FILES['file']['name'];
	} else if (@$_REQUEST['source'] == 'url') {
		$_REQUEST['url'] = preg_replace('/#.*/', '', $_REQUEST['url']);

        
error_log("LOAD FROM ".$_REQUEST['url']);        

        $src = loadRemoteURLContentWithRange($_REQUEST['url'], null, false, 120);

/*        
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, @$_REQUEST['url']);
        
        if(defined("HEURIST_HTTP_PROXY")){
            curl_setopt($ch, CURLOPT_PROXY, HEURIST_HTTP_PROXY);
            if(defined("HEURIST_HTTP_PROXY_AUTH")){
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, HEURIST_HTTP_PROXY_AUTH);
            }
        }
        
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        
		$src = curl_exec($ch);
		$error = curl_error($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
error_log("CODE=".$error);        
        
		if (intval($code) >= 400)
			$error = 'URL could not be retrieved. <span style="font-weight: normal;">You might try saving the page you are importing, and then <a href="importHyperlinks.php">import from file</a>.</span>';
*/      
        if(!$src){
            $error = 'URL could not be retrieved. Verify your proxy setting in configuration file. <span style="font-weight: normal;">You might try saving the page you are importing, and then <a href="importHyperlinks.php">import from file</a>.</span>';
        }
            
		$srcname = @$_REQUEST['url'];
	}

	if (@$src) {
		$base_url = @$_REQUEST['url'];
		if (preg_match('!<base[^>]*href=["\']?([^"\'>\s]+)["\']?!is', $src, $url_match))
			$base_url = $url_match[1];
		$base_url_root = preg_replace('!([^:/])/.*!', '$1', $base_url);
		$base_url_base = preg_replace('!([^:/]/.*/)[^/]*$!', '$1', $base_url);
		if (substr($base_url_base, -1, 1) != '/') $base_url_base = $base_url_base . '/';
/*****DEBUG****///error_log($base_url);
/*****DEBUG****///error_log($base_url_root);
/*****DEBUG****///error_log($base_url_base);

		// clean up the page a little
		$src = preg_replace('/<!-.*?->/s', '', $src);
		$src = preg_replace('!<script.*?</script>!is', '', $src);
		$src = preg_replace('!<style.*?</style>!is', '', $src);

		// find the page title
		preg_match('!<title>([^><]*)</title>!is', $src, $title_matches);
		if (@$title_matches[1])
			$notes_src_str = " [source: '".$title_matches[1]."' (".$srcname.")]";
		else
			$notes_src_str = " [source: ".$srcname."]";

            
		preg_match_all('!(<a[^>]*?href=["\']?([^"\'>\s]+)["\']?[^>]*?'.'>(.*?)</a>.*?)(?=<a\s|$)!is', $src, $matches);

		/* get a list of the link-texts that we are going to ignore */
		$ignored = mysql__select_assoc('usrHyperlinkFilter', 'lcase(hyf_String)', '-1',
		                               'hyf_UGrpID is null or hyf_UGrpID='.get_user_id());
		$wildcard_ignored = array();
		if($ignored){
			foreach ($ignored as $key => $val) {
				$key_len = strlen($key);

				if (@$key[$key_len-1] == '*') {	/* wildcard at the end of the string only */
					unset($ignored[$key]);
					$wildcard_ignored[substr($key, 0, $key_len-1)] = $key_len - 1;
				}
			}
		}

		mysql_connection_select(USERS_DATABASE);
		$res = mysql_query('select ugr_MinHyperlinkWords from '.USERS_TABLE.' where '.USERS_ID_FIELD.' = '.get_user_id());
		$row = mysql_fetch_row($res);
		$word_limit = $row[0];	// minimum number of words that must appear in the link
		mysql_connection_overwrite(DATABASE);


		$urls = array();
		$notes = array();
		$last_url = '';
		for ($i=0; $i < count($matches[1]); ++$i) {
			// ignore javascript links, mozilla 'about' links
			if (preg_match('!^(javascript|about):!i', @$matches[2][$i])) {
				continue;
            }

			if (! preg_match('!^[-+.a-z]+:!i', @$matches[2][$i])) {	/* doesn't start with protocol -- a relative URL */
				if (substr($matches[2][$i], 0, 1) == '/')	/* starts with a slash -- relative to root */
					$matches[2][$i] = $base_url_root . $matches[2][$i];
				else
					$matches[2][$i] = $base_url_base . $matches[2][$i];

				//while (preg_match('!/\\.\\.(?:/|$)!', $matches[2][$i]))	/* remove ..s */
					//$matches[2][$i] = preg_replace('!(http://.+)(?:/[^/]*)/\\.\\.(/|$)!', '\\1\\2', $matches[2][$i]);

		/*****DEBUG****///		error_log($matches[1][$i]);
			}

			$matches[3][$i] = trim(preg_replace('/\s+/', ' ', str_replace('&nbsp;', ' ', strip_tags($matches[3][$i]))));

			$lcase = strtolower(html_entity_decode($matches[3][$i]));

			$forbidden = 0;
			if (@$ignored[$lcase]){
                    $forbidden = 1;            // ignore forbidden links  
            } 
			else {
				foreach ($wildcard_ignored as $wc => $len) {
					if (substr($lcase, 0, $len) == $wc) { $forbidden = 1; break; }
				}
			}
			if (! @$forbidden  and  substr($matches[3][$i], 0, 5) != 'http:') {
                
				if (($word_limit and ! $matches[3][$i])
				 or (substr_count($matches[3][$i], ' ')+1 < $word_limit)) {
                    $forbidden = 1;    // ignore short links   
                 }
			}
			if (@$forbidden) {
				if (@$last_url) {
                    $notes[$last_url] .= strip_tags(@$matches[1][$i]);   
                }
				continue;
			}

			/* matches[2] contains the URLs, matches[3] contains the text of the link */
			if (! @$urls[$matches[2][$i]]  or  @$matches[2][$i] == @$urls[$matches[2][$i]]) {
				$url = html_entity_decode($matches[2][$i]);

				// if ($matches[2][$i] != $matches[3][$i])
					$urls[$url] = $matches[3][$i];
				/* REMOVED LIMITATION: if the text of the link is the URL itself, omit it */
				//	$urls[$matches[2][$i]] = ;	// continue;
				$notes[$url] = strip_tags($matches[1][$i]);
				$last_url = $url;
			}
		}

		foreach ($notes as $url => $val) {
			$val = preg_replace('/\s*(\n)\s*/s', "$1", $val);
			$notes[$url] = preg_replace('/[ 	]+/s', ' ', $val);
		}

		$nextmode = 'printurls';
	}

} else if (@$_REQUEST['link']) {
	$urls = array();
	$max_no = max(array_keys($_REQUEST['link']));

	for ($i=1; $i <= $max_no; ++$i) {
		if ($_REQUEST['link'][$i])
			$urls[$_REQUEST['link'][$i]] = @$_REQUEST['title'][$i];
	}

	$nextmode = 'printurls';
}


$disambiguate_rec_ids = array();
if ((@$_REQUEST['mode'] == 'Bookmark checked links'  ||  @$_REQUEST['adding_tags'])  &&  @$_REQUEST['links'])
{

	$record_tobebookmarked = array();

	foreach (@$_REQUEST['links'] as $linkno => $checked) {
		if (! @$checked) continue;

		$rec_id = records_check(@$_REQUEST['link'][$linkno], @$_REQUEST['title'][$linkno], (@$_REQUEST['use_notes'][$linkno]? @$_REQUEST['notes'][$linkno] . @$notes_src_str : NULL), @$_REQUEST['rec_ID'][$linkno]);
		if ($rec_id && is_array($rec_id)) {
			// no exact match, just a list of nearby matches; get the user to select one
			$disambiguate_rec_ids[$_REQUEST['link'][$linkno]] = $rec_id;
			continue;
		}

		if (! @$rec_id) continue;	/* malformed URL */

		array_push($record_tobebookmarked, $rec_id);
	}

	if(count($record_tobebookmarked)>0){

		if (@$_REQUEST['adding_tags'] == 1) {
			$kwd = @$_REQUEST['wgTags'];
		} else {
			$kwd = @$_REQUEST['kwd'][$linkno];
		}

		//method to add bookmarks and tags
		$data = array();
		$data['rec_ids'] = $record_tobebookmarked;
		$data['tagString'] = $kwd;

		$res = bookmark_and_tag_record_ids($data);
		if(@$res['ok']){
			$success = $res['ok'];
		}else if (@$res['none']){
			$success = $res['none'];
		}else{
			$error = $res['problem'];
		}

	}else{
		$error = "Nothing to bookmark. Select links";
	}


	/*
	$bkmk_insert_count = 0;
	if (bookmark_insert(@$_REQUEST['link'][$linkno], @$_REQUEST['title'][$linkno], $kwd, $rec_id)){
		++$bkmk_insert_count;
	}
	if (@$bkmk_insert_count == 1){
		$success = 'Added one bookmark';
	}else if (@$bkmk_insert_count > 1){
		$success = 'Added ' . $bkmk_insert_count . ' bookmarks';
	}
	*/
}


// filter the URLs (get rid of the ones already bookmarked)
if (@$urls) {
	$bkmk_urls = mysql__select_assoc('usrBookmarks left join Records on rec_ID = bkm_recID', 'rec_URL', '1', 'bkm_UGrpID='.get_user_id());
	$ignore = array();
	foreach ($urls as $url => $title){
		if (@$bkmk_urls[$url]) $ignore[$url] = 1;
	}
}

?>
<html>
<head>
	<title>Import Hyperlinks</title>

    <meta http-equiv="content-type" content="text/html; charset=utf-8">
	<link rel="stylesheet" type="text/css" href="<?=HEURIST_SITE_PATH?>common/css/global.css">
  	<link rel="stylesheet" type="text/css" href="<?=HEURIST_SITE_PATH?>common/css/edit.css">
   	<!-- link rel="stylesheet" type="text/css" href="<?=HEURIST_SITE_PATH?>common/css/admin.css" -->

    <style type="text/css">
		.input-header-cell {width:140px;min-width:140px;max-width:140px; vertical-align:baseline;}
		.input-header-cell input[type="radio"]{float:left;min-width:35px}
		.error {color:#C00; font-weight:bold;}
		.words {color: #6A7C99;}
		.similar_bm{text-align: left;width:100%;color: #6A7C99;}
		.similar_bm label{text-align: left;}
	</style>
</head>


<body class="popup" width=600 height=400 style="margin:10px;">

<script src="<?=HEURIST_SITE_PATH?>common/js/utilsLoad.js"></script>
<script src="<?=HEURIST_SITE_PATH?>common/js/utilsUI.js"></script>
<script type="text/javascript">
	top.HEURIST.baseURL="<?=HEURIST_SITE_PATH?>";
</script>
<script src="importHyperlinks.js"></script>
<script src="<?=HEURIST_SITE_PATH?>common/php/displayPreferences.php"></script>
<script>
//top.HEURIST.loadScript("<?=HEURIST_SITE_PATH?>common/php/loadUserInfo.php?db=<?=HEURIST_DBNAME?>");
</script>

<?php //this frame is needed for title lookup ?>
<iframe style="display: none;" name="grabber"></iframe>

<form action="importHyperlinks.php?db=<?=HEURIST_DBNAME?>" method="post" enctype="multipart/form-data" name="mainform" style="margin: 0px 3px;">

<input type="hidden" name="wgTags" id="wgTags">
<input type="hidden" name="adding_tags" value="0" id="adding_tags_elt">
<input type="hidden" name="titlegrabber_lock">

<?php
	if ($nextmode == 'inputselect') {
?>

<p>You can import bookmarks from your web browser favourites (exporting bookmarks creates an html file)
or by capturing hyperlinks from a web page (such as a list of links, or a page containing some
hyperlinks of interest).</p>

<p><b>Firefox</b>: Bookmarks <b>&nbsp;&gt;&nbsp;</b> Manage Bookmarks <b>&nbsp;&gt;&nbsp;</b> File <b>&nbsp;&gt;&nbsp;</b> Export, writes <tt>bookmarks.html</tt> by default</p>
<p style="margin-left: 20px;" class="normal"><b>IE</b>: File <b>&nbsp;&gt;&nbsp;</b> Import and Export <b>&nbsp;&gt;&nbsp;</b> Export Favourites, writes <tt>bookmark.htm</tt> by default</p>

<div class="separator_row" style="margin:20px 0 5px 0;"></div>

<?php		if (@$error) {	?>
 <div class="input-row"><div class="error"><?= $error ?></div></div>
<?php		} ?>
 <div class="input-row">
  <div class="input-header-cell"><input type="radio" name="source" value="file" id="radio_file">
  Import from file:</div>
  <div class="input-cell"><input type="file" name="file" size="41" onChange="document.getElementById('radio_file').checked = true;"></div>
 </div>
 <div class="input-row">
  <div class="input-header-cell"><input type="radio" name="source" value="url" id="radio_url">Import from URL:</div>
  <div class="input-cell"><input type="text" name="url" size="50" onChange="document.getElementById('radio_url').checked = true;"><input type="submit" name="mode" value="Analyse"></div>
 </div>


<?php
	} else if ($nextmode == 'printurls') {

/* removed by saw 2010/11/12 doesn't seemed to be used anymore
		$tags = mysql__select_array('usrTags', 'tag_Text', 'tag_UGrpID='.get_user_id().' order by tag_Text');
		$tag_options = '';
		foreach ($tags as $kwd)
			$tag_options .= '<option value="'.htmlspecialchars($kwd).'">'.htmlspecialchars($kwd)."</option>\n";
*/
		mysql_connection_select(USERS_DATABASE);
		$res = mysql_query('select ugr_MinHyperlinkWords from '.USERS_TABLE.' where '.USERS_ID_FIELD.' = '.get_user_id());
		$row = mysql_fetch_row($res);
		$word_limit = $row[0];	// minimum number of words that must appear in the link
		mysql_connection_overwrite(DATABASE);

?>
<h2 style="padding-left: 20px;">Import Hyperlinks</h2>
<p style="padding-left: 20px;">
Web links in <b><?= htmlspecialchars($srcname) ?></b>
<input type="hidden" name="old_srcname" value="<?= htmlspecialchars($srcname) ?>">

Note: the list only shows links which you have not already bookmarked.<br>
<?php if ($word_limit) { ?>
  Only links with at least <?= ($word_limit == 1)? 'one word' : $word_limit.' words' ?> are shown,
  and common
<?php } else { ?>Common<?php } ?>
  hyperlink texts are ignored.
  &nbsp;&nbsp;
  <input type="button" onClick="top.HEURIST.util.popupURL(top, '<?=HEURIST_SITE_PATH?>admin/profile/configImportSettings.php?db=<?=HEURIST_DBNAME?>', { callback: function() { document.forms[0].submit(); } });" value="Change settings">
<br />
We recommend bookmarking a few links at a time.<br />The list is reloaded after each addition and after change of settings.

<?php		if (@$error) {	?>
 <div class="input-row"><?= $error ?></div>
<?php		} ?>
<?php		if (@$success) {	?>
 <div class="input-row" style="color:#0000ff;font-weight:bold;"><?= htmlspecialchars($success) ?></div>
<?php		} ?>
<?php		if (@$disambiguate_rec_ids) { ?>
 <div class="input-row" style="color:blue;"">
  <b><?= (count($disambiguate_rec_ids) == 1)? 'One of your selected links is' : 'Some of your selected links are' ?>
  similar to record(s) already in the database.</b><br>
  The similar records are shown below: please select the appropriate page, or add a new URL to the database.<br>
  Then click on "Bookmark checked links" again.
 </div>
<?php		} ?>
</p>
 <div class="input-row" style="padding-left: 20px;">
   <a href="#" onClick="checkAll(); return false;">Check all</a>
   &nbsp;&nbsp;
   <a href="#" onClick="unCheckAll(); return false;">Uncheck all</a>
   &nbsp;&nbsp;
   <input type="button" name="mode" value="Bookmark checked links" style="font-weight: bold;" onClick="{doBookmark('<?=HEURIST_DBNAME?>');}">
 </div>



<?php
/* do two passes: first print any that need disambiguation, then do the rest */
		if (@$disambiguate_rec_ids) {
			$linkno = 0;
			foreach (@$urls as $url => $title) {
				++$linkno;
				if (! @$disambiguate_rec_ids[$url]) continue;

				print_link($url, $title);
				$ignore[$url] = 1;
			}

			print '<div class="input-row">&nbsp;</div>' . "\n";
		}

		$linkno = 0;
		foreach (@$urls as $url => $title) {
			++$linkno;
			if (@$ignore[$url]) {
				print '<input type="hidden" name="link['.$linkno.']" value="'.htmlspecialchars($url).'">';
				continue;	// already bookmarked; have to give the bogus element to keep PHP numbering in sync
			}

			print_link($url, $title);
		}

	}
?>
</form>

</body>
</html>
<?php
/* ----- END OF OUTPUT ----- */

function records_check($url, $title, $notes, $user_rec_id) {
	/*
	 * Look for a Records record corresponding to the given record;
	 * user_rec_id is the user's preference if there isn't an exact match.
	 * Insert one if it doesn't already exist;
	 * return the rec_ID, or 0 on failure.
	 * If there are a number of similar URLs, return a list of their rec_ids.
	 */

	// saw FIXME this should be
	$res = mysql_query('select rec_ID from Records where rec_URL = "'.mysql_real_escape_string($url).'" and (rec_OwnerUGrpID=0 or not rec_NonOwnerVisibility="hidden")');
	if (mysql_num_rows($res) > 0) {
		$bib = mysql_fetch_assoc($res);
		return $bib['rec_ID'];
	}

	if ($user_rec_id > 0) {
		$res = mysql_query('select rec_ID from Records where rec_ID = "'.mysql_real_escape_string($user_rec_id).'" and (rec_OwnerUGrpID=0 or not rec_NonOwnerVisibility="hidden")');
		if (mysql_num_rows($res) > 0) {
			$bib = mysql_fetch_assoc($res);
			return $bib['rec_ID'];
		}

	} else if (! $user_rec_id) {

		$rec_ids = similar_urls($url);
		if ($rec_ids) return $rec_ids;
/*
		$par_url = preg_replace('/[?].*'.'/', '', $url);
		if (substr($par_url, strlen($par_url)-1) == '/')	// ends in a slash; remove it
			$par_url = substr($par_url, 0, strlen($par_url)-1);

		$res = mysql_query('select rec_ID from Records where rec_URL like "'.mysql_real_escape_string($par_url).'%" and (rec_OwnerUGrpID=0 or not rec_NonOwnerVisibility="hidden")');
		if (mysql_num_rows($res) > 0) {
			$rec_ids = array();
			while ($row = mysql_fetch_row($res))
				array_push($rec_ids, $row[0]);
			return $rec_ids;
		}
*/
	}

	// no similar URLs, no exactly matching URL, or user has explicitly selected "add new URL"
	//insert the main record
	if (mysql__insert('Records', array(
		'rec_RecTypeID' => RT_INTERNET_BOOKMARK,
		'rec_URL' => $url,
		'rec_Added' => date('Y-m-d H:i:s'),
		'rec_Modified' => date('Y-m-d H:i:s'),
		'rec_Title' => $title,
		'rec_ScratchPad' => $notes,
		'rec_AddedByUGrpID' => get_user_id()
	))) {
		$rec_id = mysql_insert_id();
		//add title input-cell
		mysql__insert('recDetails', array(
			'dtl_RecID' => $rec_id,
			'dtl_DetailTypeID' => DT_NAME,
			'dtl_Value' => $title
		));
		//add notes input-cell
		if($notes){
			mysql__insert('recDetails', array(
				'dtl_RecID' => $rec_id,
				'dtl_DetailTypeID' => DT_EXTENDED_DESCRIPTION,
				'dtl_Value' => $notes
			));
		}
		return $rec_id;
	}

	return 0;
}

/*  ARTEM : Now we use common function from actionMethods.php

	 * Insert a new bookmark with the relevant input-cells;
	 * return true on success,
	 * return false on failure, or if the Records record is already bookmarked by this user.

function bookmark_insert($url, $title, $tags, $rec_id) {

	$res = mysql_query('select * from usrBookmarks where bkm_recID="'.mysql_real_escape_string($rec_id).'"
	                                              and bkm_UGrpID="'.get_user_id().'"');
	//if already bookmarked then return
	if (mysql_num_rows($res) > 0) return 0;
	//insert the bookmark
	if (mysql__insert('usrBookmarks', array(
		'bkm_recID' => $rec_id,
		'bkm_Added' => date('Y-m-d H:i:s'),
		'bkm_Modified' => date('Y-m-d H:i:s'),
		'bkm_UGrpID' => get_user_id())))
	{
		$bkm_ID = mysql_insert_id();
		// find the tag ids for each tag.
		$all_tags = mysql__select_assoc('usrTags', 'lower(tag_Text)', 'tag_ID', 'tag_UGrpID='.get_user_id());
		$input_tags = explode(',', $tags);
		$tag_ids = array();

//		$kwd_string = '';
		foreach ($input_tags as $tag) {
			if ( @$all_tags[strtolower(trim($tag))] ) {
				array_push($tag_ids, $all_tags[strtolower(trim($tag))]);
//				if ($kwd_string) $kwd_string .= ',';
//				$kwd_string .= trim($kwd);
			}
		}


error_log(">>>>".print_r($tag_ids,true));

//		mysql_query('delete from usrRecTagLinks where kwl_pers_id='.$bkm_ID);
		if ($tag_ids) {
			$insert_stmt = '';
			$tgi_count = 0;
			foreach ($tag_ids as $tag_id) {
				if ($insert_stmt) $insert_stmt .= ',';
				$insert_stmt .= '(' . $rec_id . ',' . $tag_id . ',' . ++$tgi_count . ')';
			}

			$insert_stmt = 'insert into usrRecTagLinks (rtl_RecID, rtl_TagID, rtl_Order) values ' . $insert_stmt;
			mysql_query($insert_stmt);
		}

		return 1;
	} else {
		return 0;
	}
}
*/


function my_htmlspecialchars_decode($str) {
	return str_replace(array('&nbsp;', '&amp;', '&quot;', '&lt;', '&gt;', '&copy;'), array(' ', '&', '"', '<', '>', '(c)'), $str);
}

function print_link($url, $title) {
	global $linkno;
	global $disambiguate_rec_ids;
	global $notes;

?>
<div class="input-row" style="background-color:#CCCCCC; padding-left: 40px; width:80%;">
		<input type="checkbox" name="links[<?= $linkno ?>]" value="1" class="check_link" id="flag<?= $linkno ?>" <?= @$_REQUEST['links'][$linkno]? 'checked' : '' ?> onChange="var t=document.getElementById('t<?= $linkno ?>').value; var n=document.getElementById('n<?= $linkno ?>').value; if (!this.checked || n.length > t.length) { var e=document.getElementById('un<?= $linkno ?>'); if(e) e.checked = this.checked; }">
		&nbsp;<input type="text" name="title[<?= $linkno ?>]" value="<?= $title ?>" style="width:70%; font-weight: bold; background-color: #eee;" id="t<?= $linkno ?>">
		<input type="hidden" name="alt_title[<?= $linkno ?>]" value="<?= $title ?>" id="at<?= $linkno ?>">
		<input type="hidden" name="link[<?= $linkno ?>]" value="<?= htmlspecialchars($url) ?>" id="u<?= $linkno ?>">

  		&nbsp;&#91;<a href="<?= $url ?>" target="_blank"><span class="button">Visit</span></a>&#93;&nbsp;
		<input type="button" style="padding-top: 2px;height:23px !important;font-weight:normal;font-size:1em;" name="lookup[<?= $linkno ?>]" value="Lookup" title="Lookup title from URL"
			onClick="{lookup_revert(this, <?= $linkno ?>);}" id="lu<?= $linkno ?>">
		<input type="hidden" name="kwd[<?= $linkno ?>]" value="<?= htmlspecialchars(@$_REQUEST['kwd'][$linkno]) ?>" id="key<?= $linkno ?>">
</div>

<div class="input-row" style="padding-left: 60px;">
  <a target=_blank href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($url) ?></a>
</div>
<div class="input-row" style="padding-left: 60px;">
	<div style="display:inline-block;width:30px;vertical-align: middle;">
		<input style="margin: 0px;" type="checkbox" name="use_notes[<?= $linkno ?>]" value="1" id="un<?= $linkno ?>" class="use_notes_checkbox" title="Use Notes">
      	<input type="hidden" name="notes[<?= $linkno ?>]" id="n<?= $linkno ?>" value="<?= @$_REQUEST['notes'][$linkno]? str_replace('"', '\\"', htmlspecialchars($_REQUEST['notes'][$linkno])) : str_replace('"', '\\"', htmlspecialchars($notes[$url])) ?>">
	  </div>
      <div style="display:inline-block;width:70%;max-height:5.5em;text-overflow: ellipsis; overflow:hidden; white-space:normal;"><?= @$_REQUEST['notes'][$linkno]? htmlspecialchars($_REQUEST['notes'][$linkno]) : wordwrap($notes[$url], 50, "\n", true) ?>
      </div>
      <small class="words">
<?php
	if (@$_REQUEST['notes'][$linkno])
		$word_count = str_word_count($_REQUEST['notes'][$linkno]);
	else
		$word_count = str_word_count($notes[$url]);
	if ($word_count == 1) {
		print '1 word';
	} else if ($word_count > 1) {
		print "$word_count words";
	}
?></small>
</div>

<?php
	if (@$disambiguate_rec_ids[$url]) {
?>
<div class="input-row">
	<div class="similar_bm">
		<span>
			<input type="radio" name="rec_ID[<?= $linkno ?>]" value="-1" checked="checked" onClick="selectExistingLink(<?= $linkno ?>);">
			<b>New (add this URL to the database)</b>
		</span>
	</div>

<?php
		$res = mysql_query('select * from Records where rec_ID in (' . join(',', $disambiguate_rec_ids[$url]) . ')');
		$all_bibs = array();
		while ($row = mysql_fetch_assoc($res))
			$all_bibs[$row['rec_ID']] = $row;

		foreach ($disambiguate_rec_ids[$url] as $rec_id) {
			$row = $all_bibs[$rec_id];
?>
	<div class="similar_bm">
		<span>
			<input type="radio" name="rec_ID[<?= $linkno ?>]" value="<?= $row['rec_ID'] ?>" onClick="selectExistingLink(<?= $linkno ?>);">
			<?= htmlspecialchars($row['rec_Title']) ?>
		</span>&nbsp;&nbsp;
		<a style ="font-size: 80%; text-decoration:none;" target="_testwindow" href="<?= htmlspecialchars($row['rec_URL']) ?>"><?php
				if (strlen($row['rec_URL']) < 100)
					print (common_substring($row['rec_URL'], $url));
				else
					print (common_substring(substr($row['rec_URL'], 0, 90) . '...', $url));
		?></a><br>
	</div>

<?php
		}
?>
  </div>
<?php
	}
?>
 <div class="input-row">&nbsp;</div>
<?php
}


function common_substring($url, $base_url) {
	for ($i=0; $i < strlen($url) && $i < strlen($base_url); ++$i) {
		if ($url[$i] != $base_url[$i]) break;
	}

	return '<span style="color: black;">'.htmlspecialchars(substr($url, 0, $i)).'</span>'.htmlspecialchars(substr($url, $i));
}

?>
