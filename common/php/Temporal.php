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

function temporalToHumanReadableString($value, $showoriginal_temporal=false){

		if (strpos($value,"|")!==false) {// temporal encoded date
			$value2 = $value;
            $cld = null;
			$tDate = array();
			$props = explode("|",substr_replace($value2,"",0,1)); // remove first verticle bar and create array
			foreach ($props as $prop) {//create an assoc array
				list($tag, $val) = explode("=",$prop);
				$tDate[$tag] = $val;
			}
            
            if ($tDate["CLD"] && $tDate["CL2"] && strtolower($tDate["CLD"])!='gregorian') {
                $cld = $tDate["CL2"]." ".$tDate["CLD"];
            }
            
            $is_greg_or_julian = ((!$tDate["CLD"]) || 
                                strtolower($tDate["CLD"])=='gregorian' || strtolower($tDate["CLD"])=='julian');

			switch ($tDate["TYP"]){
				case 's'://simple
					if (@$tDate['DAT']){
						$value = removeLeadingYearZeroes($tDate['DAT'], $is_greg_or_julian);
					}else{
						$value = "unknown temporal format";
					}
					break;
				case 'f'://fuzzy
					if (@$tDate['DAT']){
						$value = removeLeadingYearZeroes($tDate['DAT'], $is_greg_or_julian) .
									($tDate['RNG']? ' ' . convertDurationToDelta($tDate['RNG'],'±'):"");
					}else{
						$value = "unknown fuzzy temporal format";
					}
					break;
				case 'c'://carbon
					$value = (@$tDate['BPD']? '' . $tDate['BPD'] . ' BPD':
									@$tDate['BCE']? '' . $tDate['BCE'] . ' BCE': "");
					if ($value) {
						$value = $value.(@$tDate['DEV']? ' ' . convertDurationToDelta($tDate['DEV'],'±'):
											@$tDate['DVP']? ' ' . convertDurationToDelta($tDate['DVP'],'+').
													(@$tDate['DVN']?"/ ".convertDurationToDelta($tDate['DVN'],'-'):""):
											@$tDate['DVN']?" ".convertDurationToDelta($tDate['DVN'],'-'):"");
					}else{
						$value = "unknown carbon temporal format";
					}
					break;
				case 'p'://probability range
					if (@$tDate['PDB'] && @$tDate['PDE']){
						$value = "" . removeLeadingYearZeroes($tDate['PDB'], $is_greg_or_julian).
                                " to ". removeLeadingYearZeroes($tDate['PDE'], $is_greg_or_julian);
					}else if (@$tDate['TPQ'] && @$tDate['TAQ']){
						$value = "" . removeLeadingYearZeroes($tDate['TPQ'],$is_greg_or_julian).
                                    " to ". removeLeadingYearZeroes($tDate['TAQ'],$is_greg_or_julian);
					}else{
						$value = "unknown probability range temporal format";
					}
					break;
			}
            if($cld){
                $value = $cld.' (Gregorian '.$value.')';
            }            
			if($showoriginal_temporal){
				$value .= " [ $value2 ]";
			}
            
		}else{
			$value = removeLeadingYearZeroes($value);
		}

		return $value;
}

//
//
//
function removeLeadingYearZeroes($value, $is_greg_or_julian=true){

	$date = parseDateTime($value);

	if($date){

		$res = "";
		$isbce = false;

		if(@$date['year']){

			$isbce= ($date['year']<0);

			/*$res = intval($res);
			if($res==0 || $isbce){
				$isbce = true;
				$res = abs($res + 1);
			}*/
			$res = "".abs($date['year']);
		}
        
        if($is_greg_or_julian){

            $res2 = "";
            if(@$date['day']){
                $res2 = $date['day']; 
            }
            if(@$date['month']){
                $res2 = $res2." ".date('M', mktime(0, 0, 0, $date['month'], 1)); //strtotime($date['month'].'01')); 
            }
            
            $res = trim($res2."  ".$res);

        }else{
        
		    if(@$date['month']){
			    $res = $res."-".str_pad($date['month'],2,'0',STR_PAD_LEFT);
		    }
		    if(@$date['day']){
			    $res = $res."-".str_pad($date['day'],2,'0',STR_PAD_LEFT);
		    }
        }

		if(@$date['hour']>0 || @$date['minute']>0 || @$date['second']>0){
			if(!@$date['hour']) {
					$date['hour'] = 0;
			}

			if($date['hour']>0 || @$date['minute']>0 || @$date['second']>0){
				$res = $res." ".str_pad($date['hour'],2,'0',STR_PAD_LEFT);

				if(!@$date['minute']) { $date['minute'] = 0; }
				$res = $res.":".str_pad($date['minute'],2,'0',STR_PAD_LEFT);
			}
			if(@$date['second']>0){
				$res = $res.":".str_pad($date['second'],2,'0',STR_PAD_LEFT);
			}
		}


		if($isbce){
			$res = $res." BCE";
		}
		return $res;
	}else{
		return $value;
	}
}

//
//
//
function parseDateTime($value) {
	$isbce= (strpos($value,"-")===0);
	if($isbce){
		$value = substr($value,1);
	}

	//if (preg_match('/^P([^T]*)T?(.*)$/', $value, $matches)) { // valid ISO Duration split into date and time

	if($value && strlen($value)>10 && strpos($value,"T")>0){
		$matches = explode("T",$value);
	}else if($value && strlen($value)>10 && strpos($value," ")>0){
		$matches = explode(" ",$value);
	}else{
		$matches = array();
		$matches[0] = $value;
	}

		$date = @$matches[0];
		$time = @$matches[1];
		$res = array();

		if ($date) {
			if (preg_match('/[YMD]/',$date)){ //char separated version 6Y5M8D
				preg_match('/(?:(\d+)Y)?(?:(\d|0\d|1[012])M)?(?:(0?[1-9]|[12]\d|3[01])D)?/',$date,$matches);
			}else{ //delimited version  0004-12-06
				preg_match('/^(?:(\d\d\d\d)[-\/]?)?(?:(1[012]|0[23]|[23](?!\d)|0?1(?!\d)|0?[4-9](?!\d))[-\/]?)?(?:([12]\d|3[01]|0?[1-9]))?\s*$/',$date,$matches);
			}
			if (@$matches[1]) $res['year'] = intval($matches[1])*($isbce?-1:1);
			if (@$matches[2]) $res['month'] = intval($matches[2]);
			if (@$matches[3]) $res['day'] = intval($matches[3]);
		}
		if ($time) {
			if (preg_match('/[HMS]/',$time)){ //char separated version 6H5M8S
				preg_match('/(?:(0?[1-9]|1\d|2[0-3])H)?(?:(0?[1-9]|[0-5]\d)M)?(?:(0?[1-9]|[0-5]\d)S)?/',$time,$matches);
			}else{ //delimited version  23:59:59
				//preg_match('/(?:(0?[1-9]|1\d|2[0-3])[:\.])?(?:(0?[1-9]|[0-5]\d)[:\.])?(?:(0?[1-9]|[0-5]\d))?/',$time,$matches);

				//preg_match('/^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$/',$time,$matches);

				$matches = explode(":",$time);
				$matches = array_merge(array($time), $matches);
			}

			$res['hour'] = (@$matches[1])? intval($matches[1]):0;
			$res['minute'] = (@$matches[2])?intval($matches[2]):0;
			$res['second'] = (@$matches[3])?intval($matches[3]):0;
		}

		return count($res)?$res:null;
}
function convertDurationToDelta($value,$prefix = "") {
	$date = parseDateTime($value);
	if ($date) { // valid ISO Duration split into date and time

		return (@$date['year'] ? "$prefix".$date['year'] :
				@$date['month'] ? "$prefix".$date['month'] :
				@$date['day'] ? "$prefix".$date['day'] :
				@$date['hour'] ? "$prefix".$date['hour'] :
				@$date['minute'] ? "$prefix".$date['minute'] :
				@$date['second'] ? "$prefix".$date['second'] :
				"");

	}
}

/**
* simplify temporal to simple date (for kml export)
*
* @param mixed $value
*/
function temporalToSimple($value){
		if ($value && strpos($value,"|")!==false) {// temporal encoded date
			$value2 = $value;
			$tDate = array();
			$props = explode("|",substr_replace($value2,"",0,1)); // remove first verticle bar and create array
			foreach ($props as $prop) {//create an assoc array
				list($tag, $val) = explode("=",$prop);
				$tDate[$tag] = $val;
			}
			switch ($tDate["TYP"]){
				case 's'://simple
				case 'f'://fuzzy
					if (@$tDate['DAT']){
						$value = $tDate['DAT'];
					}else{
						$value = null;
					}
					break;
				case 'c'://carbon
					$value = null;
					break;
				case 'p'://probability range
					if (@$tDate['PDB'] && @$tDate['PDE']){
						$value = "".$tDate['PDB'];
					}else if (@$tDate['TPQ'] && @$tDate['TAQ']){
						$value = "".$tDate['TPQ'];
					}else{
						$value = null;
					}
					break;
			}
		}
		//ART - @todo rewrite - it is ugly!!!
		if($value && strlen($value)>10 && strpos($value," ")==10){
				$value = substr_replace($value,"T",10,1);
		}
		return $value;
}

?>
