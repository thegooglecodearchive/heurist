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

var lastElt = null;
var selectedDay = null;
var callback = null;


function getDaysInMonth(month, year) {
	// Return the number of days in the given month of the given year
	// month is standard JS-type month (0=January, ... 11=December)

	var daysInMonth = Array(31, null, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

	if (month != 1) return daysInMonth[month];
	else {
		if (year % 4 != 0  ||  (year % 100 == 0  &&  year % 400 != 0))	// not a leap-year
			return 28;
		else
			return 29;
	}
}


function getCalendarRows(month, year) {
	// Return an array of arrays.
	// Each top-level array corresponds to a row of the calendar (a week, starting Sunday)
	// Each bottom-level array is either null (an empty slot to pad out to seven days a week)
	// or a date (1 .. 31)

	var myDate = new Date(year, month, 1, 12);

	var i;
	var startDay = myDate.getDay();
	var daysInMonth = getDaysInMonth(month, year);

	var calRows = new Array();
	for (i=0; i < Math.ceil((startDay+daysInMonth)/7); ++i)
		calRows[i] = new Array();

	for (i=0; i < daysInMonth; i++) {
		var day = startDay+i;
		calRows[Math.floor(day / 7)][day % 7] = i+1;
	}

	return calRows;
}


function getCalendarString(month, year) {
	// Return a string giving an HTML calendar component

	var monthsInYear = Array('January', 'February', 'March', 'April', 'May', 'June',
	                         'July', 'August', 'September', 'October', 'November', 'December');

	var calRows = getCalendarRows(month, year);
	var calString = '';


	var prevMonthURL = '*' + ((month==0)? (year-1)+'/12' : year+'/0'+(month)) + '/01';
	var nextMonthURL = '*' + ((month==11)? (year+1)+'/01' : year+'/0'+(month+2)) + '/01';
	var prevYearURL = '*' + (year-1) + '/0' + (month+1) + '/01';
	var nextYearURL = '*' + (year+1) + '/0' + (month+1) + '/01';

//<img src="../../common/images/calendar-ll-arrow.gif">	

	calString +=  '<table id="cal">';
	calString +=   '<tr><td colspan="7" id="cal_header"><table border=0 cellpadding=2 cellspacing=0 width=100%><tr>' +
			'<td id="monthyearnav">'+
				'<div onClick="init_calendar(\''+prevYearURL+'\')" title="Previous year" id="arrow_ll"></div>' +
				'<div onClick="init_calendar(\''+prevMonthURL+'\')" title="Previous year" id="arrow_l"></div>' +
			'</td>' +
			'<td id="cal_month">&nbsp;' + monthsInYear[month] + '&nbsp;' + year + '&nbsp;</td>' +
			'<td id="monthyearnav" style="text-align: right;">' +
				'<div onClick="init_calendar(\''+nextMonthURL+'\')" title="Previous year" id="arrow_r"></div>' +
				'<div onClick="init_calendar(\''+nextYearURL+'\')" title="Previous year" id="arrow_rr"></div>' +
			'</td>' +
				'</tr></table></td></tr>';
	calString +=   '<tr class="cal_daysofweek"><td>S</td><td>M</td><td>T</td><td>W</td><td>T</td><td>F</td><td>S</td></tr>';

	var i, j;
	for (i=0; i < calRows.length; ++i) {
		calString += '<tr class="cal_days">';
		for (j=0; j < 7; ++j) {
			if (calRows[i][j]) {
				var monthStr = month+1;
				var dayStr = calRows[i][j];
				if (monthStr < 10) monthStr = '0' + monthStr;
				if (dayStr < 10) dayStr = '0' + dayStr;
				calString += '<td class="cal_day" id="'+year+'-'+monthStr+'-'+dayStr +'">'+calRows[i][j]+'</td>';
			} else
				calString += '<td class="cal_disabled"></td>';
		}
		calString += '</tr>';
	}

	calString += '</table>';

	return calString;
}


function initCalendar(date, suppressDate) {
	// Draw a calendar with the given date selected (unless suppressDate is true).
	// If no date is specified, draw the current month.

	var date_ = date? date : (new Date());

	var day = date_.getDate();
	var month = date_.getMonth();
	var year = date_.getFullYear();

	document.getElementById("calendar-div").innerHTML = getCalendarString(month, year);

	if (date  &&  ! suppressDate) {
		if (day < 10) day = '0' + day;
		month = month+1;
		if (month < 10) month = '0' + month;

		var dateString = year + '-' + month + '-' + day;
		var day_elt = document.getElementById(dateString);
		if (day_elt) {
			selectedDay = day_elt;
			day_elt.className += ' cal_day_selected';
		}
	}
	var now = new Date();
	year = now.getFullYear();
	month = (now.getMonth() + 1);	if (month < 10) month = "0" + month;
	day = now.getDate();			if (day < 10) day = "0" + day;
	var dateString = year + '-' + month + '-' + day;
	var day_elt = document.getElementById(dateString);
	if (day_elt  &&  day_elt != selectedDay) {
		day_elt.className += ' cal_day_today';
	}
}


function parseDate(date) {
	// If the date is in Euro/Aussie format (dd/mm/yyyy or dd-mm-yyyy) then change it to US format (mm/dd/yyyy).
	// Return a Date object.

	var bits = date.split('/');
	if (bits.length == 1) bits = date.split('-');

	if (bits.length == 3) {
		if (parseInt(bits[0]) <= 31  &&  parseInt(bits[1]) <= 12)					// mm/dd/yyyy
			return new Date(Date.parse(bits[1] + '/' + bits[0] + '/' + bits[2]));
		else if (parseInt(bits[2]) <= 31  &&  parseInt(bits[1]) <= 12)				// yyyy/mm/dd
			return new Date(Date.parse(bits[1] + '/' + bits[2] + '/' + bits[0]));
	}

	var usecs = Date.parse(date);
	if (!usecs)
		return null;
	else
		return new Date(usecs);
}


function init_calendar(initDate, _callback) {
	if (initDate.charAt(0) == '*') {	// month and year supplied
		initCalendar(parseDate(initDate.substring(1)), true);
	} else {				// specific date supplied
		if (initDate) initCalendar(parseDate(initDate));
		else initCalendar(null);
	}
	if(_callback){
		callback = _callback;
	}
}

/* old way
if (location.hash.length > 1) {
	init_calendar(location.hash.substring(1));
} else {
	var now = new Date();
	var year = now.getFullYear();
	var month = (now.getMonth() + 1);	if (month < 10) month = "0" + month;
	var day = now.getDate();			if (day < 10) day = "0" + day;
	init_calendar("*" + year + "-" + month + "-" + day);
}
*/

document.onclick = function(e) {
	if (!e) e = event;
	target = e.srcElement? e.srcElement : e.target;
	if (target.className.match(/\bcal_day\b/)) {
		if (selectedDay) selectedDay.className = selectedDay.className.replace(/ cal_day_selected/, '');
		selectedDay = target;
		target.className += ' cal_day_selected';

		if(callback){
			callback.apply(this, [selectedDay.id]);
		}
/* old way
		if(window.opener && window.opener.document && window.opener.document.getElementById(dateId)){

		   window.opener.document.getElementById(dateId).value=selectedDay.id;
		   window.opener.calendarRollOver.close();
		}
		window.close(selectedDay.id);
*/
	}
}
document.onmouseover = function(e) {
	if (!e) e = event;
	target = e.srcElement? e.srcElement : e.target;
	if (lastElt) {
		lastElt.className = lastElt.className.replace(/ cal_day_over/, '');
		lastElt = null;
	}
	if (target.className.match(/\bcal_day\b/)) {
		lastElt = target;
		lastElt.className += ' cal_day_over';
	}
}
