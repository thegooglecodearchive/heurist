<div id="resource">
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
* Render page for media record type
*
* @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
* @copyright   (C) 2005-2013 University of Sydney
* @link        http://sydney.edu.au/heurist
* @version     3.1.0
* @license     http://www.gnu.org/licenses/gpl-3.0.txt GNU License 3.0
* @package     Heurist academic knowledge management system
* @subpackage  applications
*/

	$val = $record->getDet(DT_SHORTSUMMARY);
	if($val){
?>
			<p>
				<?=$val ?>
			</p>
<?php
	}

	$media_type = $record->getFeatureTypeName();

	if($media_type=="image"){
			print getImageTag($record, 'resource-image', 'large');
	}else{

		$url = getFileURL($record, "direct");

	if($media_type=="video"){
?>
				<div id="media"></div>
				<script type="text/javascript">
					DOS.Media.playVideo(
						"media",
						"<?=$url ?>",
						"<?=getFileURL($record,"thumbnail2") ?>"
					);
				</script>
<?php
	}else if($media_type=="audio"){
?>
				<div id="media"></div>
				<script type="text/javascript">
					DOS.Media.playAudio(
						"media",
						"<?=$url ?>"
					);
				</script>
<?php
	}
	}
?>
	<p class="attribution">
			<?=makeMediaAttributionStatement($record) ?>
	</p>

	<p class="license">
			<?=makeLicenseIcon($record); ?>
	</p>
</div>
