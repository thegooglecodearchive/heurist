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
* importRecordsFromDelimited
* javascript functions for importing comma or tab delimited data 
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


FlexImport = (function () {

	function _addOpt(sel, val, text, selected) {
	return $("<option>")
		.val(val)
		.html(text)
		.attr('selected', selected ? true :false)
		.appendTo(sel)[0];
	}

	return {

	valSep : '|',
	fields: [],
	lineHashes: {},
	columnCount: 0,
	quote: '"',
	hasHeaderRow: false,
	recTypeSelect: null,
	recTypeSelectSavedMapping: null,
	recType: null,
	errorSummary: null,
	num_err_columns: 0,
	num_err_values: 0,
	workgroupSelect: null,
	workgroups: {},
	workgroupTags: {},
	colSelectors: [], //select html elements with list of detail types
    colKeys: [],  //detail type id - that gives unique key - for update purpose 
	cols: [],   //DetailType Id - selected dt for cvs columns
    cols_uniqkey: [], //array of dt id that compose unique key, or has the only value 'recId'
	subTypes: [],
	records: [],
	lineRecordMap: {},
	lineErrorMap: {},
	constChunkSize: 5,	// controls the number of records per request for saving records
						//FIXME  need to develop an algorithm for Chunk size
	recStart: 0,
	recEnd: 5,
	SavRecordChunk: [],
	currentStep: 1,

	clearRecords: function () {
		FlexImport.recStart = 0;
		FlexImport.recEnd = FlexImport.constChunkSize;
		var i;
		for (i in FlexImport.lineRecordMap) {
			delete FlexImport.lineRecordMap[i];
		}
		FlexImport.lineRecordMap = {};
		for (i in FlexImport.lineErrorMap) {
			delete FlexImport.lineErrorMap[i];
		}
		FlexImport.lineErrorMap = {};
		for (i in FlexImport.lineHashes) {
			delete FlexImport.lineHashes[i];
		}
		FlexImport.lineHashes = {};
		for (i = 0; i < FlexImport.records.length; ++i) {
			delete FlexImport.records[i];
		}
		FlexImport.records = [];
	},

	showProgress: function(){
		$("#div-progress").removeClass("hidden");
		$("#div-steps").addClass("hidden");
	},

	gotoStep: function(step_no){

		if($("#div-steps").hasClass("hidden")){
			$("#div-progress").addClass("hidden");
			$("#div-steps").removeClass("hidden");
		}

		$("#astep"+FlexImport.currentStep).toggleClass("hidden");
		$("#mstep"+FlexImport.currentStep).toggleClass("current");
		if(step_no == 1){
			$("#mstep1").removeClass("link");
			$('#mstep1').unbind('click');
		}
		if(step_no != 3){
			$("#mstep2").removeClass("link");
			$('#mstep2').unbind('click');
		}

		FlexImport.currentStep = step_no;
		$("#astep"+FlexImport.currentStep).toggleClass("hidden");
		$("#mstep"+FlexImport.currentStep).toggleClass("current");

		if(FlexImport.currentStep>1 && !$("#mstep1").hasClass("link")){
			$("#mstep1").addClass("link");
			$("#mstep1").click(function(){
				location.reload();
			});
		}
		if(FlexImport.currentStep==3 && !$("#mstep2").hasClass("link")){
			$("#mstep2").addClass("link");
			$("#mstep2").click(function(){
				FlexImport.createColumnSelectors(null);
				//FlexImport.gotoStep(2);
			});
		}

	},

	analyseCSV: function () {

		var txt = $("#csv-textarea").val();
		if(!txt || txt.length<3){
			alert('Please paste some data');
			return;
		}

		var separator = $("#csv-separator").val();
		var terminator = $("#csv-terminator").val();
		this.quote = $("#csv-quote").val();
		this.valSep = $("#val-separator").val();
		this.hasHeaderRow = $("#csv-header").attr("checked");
		var lineRegex, fieldRegex, doubleQuoteRegex;

		if (terminator == "\\n") terminator = "\n";

		var switches = (terminator == "\n") ? "m" : "";

		FlexImport.showProgress();
		setTimeout(function() {

			if (FlexImport.quote == "'") {
				lineRegex = new RegExp(terminator + "(?=(?:[^']*'[^']*')*(?![^']*'))", switches);
				fieldRegex = new RegExp(separator + "(?=(?:[^']*'[^']*')*(?![^']*'))", switches);
			} else {
				lineRegex = new RegExp(terminator + "(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))", switches);
				fieldRegex = new RegExp(separator + "(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))", switches);
			}
			doubleQuoteRegex = new RegExp(FlexImport.quote + FlexImport.quote, "g");

			var lines = txt.split(lineRegex);
			var i, l = lines.length, k = 0;
			for (i = 0; i < l; ++i) {
				if (lines[i].length > 0) {
					FlexImport.fields[k] = lines[i].split(fieldRegex);
					for (var j = 0; j < FlexImport.fields[k].length; ++j) {
						FlexImport.fields[k][j] = FlexImport.fields[k][j].replace(doubleQuoteRegex, FlexImport.quote);
                        FlexImport.fields[k][j] = FlexImport.fields[k][j].trim();
						FlexImport.columnCount = Math.max(FlexImport.columnCount, j + 1);
					}
					k++;
				}
			}
			//we have parsed the input so remove the textarea
			$("#csv-entry-div").remove();

			$("#info-p").html(
				"Found <b>" + (FlexImport.hasHeaderRow ? FlexImport.fields.length - 1:FlexImport.fields.length) + "</b> rows of data," +
				" in <b>" + FlexImport.fields[0].length + "</b> columns."
			);
	//"<a href='"+HeuristBaseURL+"import/delimited/importRecordsFromDelimited.html?db="+HAPI.database+"' ><b>Start over / import more</b></a>"

			FlexImport.createRecTypeOptions();

			FlexImport.loadSavedMappings();

			FlexImport.gotoStep(2);
		},200);
	},

	//load mapping from file
	reapplyMapping: function(){

			function _onLoadSavedMappingsContent(context){

				if(context.response.indexOf("Error:")==0){
					alert(context.response );
					return;
				}

				var savedMapping = context.response.split(",");

				if(savedMapping.length<1){
					alert("Error: Empty mapping");
					return;
				}

				var recTypeID = Number(FlexImport.recTypeSelect.value);

				var applyMapping = function(){
					var i, j=1,
						m = savedMapping.length,
						l = FlexImport.colSelectors.length;

					for (i = 0; i < l; ++i) {
						if(j<m){
							FlexImport.colSelectors[i].value = savedMapping[j];
							j++;
						}else{
							FlexImport.colSelectors[i].selectedIndex = 0;
						}
					}
				}


				if(recTypeID!==Number(savedMapping[0])){
					FlexImport.recTypeSelect.value = savedMapping[0];
					FlexImport.createColumnSelectors(applyMapping);
				}else{
					applyMapping.call(FlexImport);
				}

			}

			var baseurl = HeuristBaseURL+"import/delimited/importDelimitedMapping.php";
			var callback = _onLoadSavedMappingsContent;
			var params = "mode=load&file="+FlexImport.recTypeSelectSavedMapping.value+"&db=" + HAPI.database;
			top.HEURIST.util.sendRequest(baseurl, callback, params);

	},

	//load list of saved mappings
	loadSavedMappings: function () {

			function _onLoadSavedMappingsList(context){

				var savedMapping = context.response.split("|");

				var e = $("#rec-type-select-div")[0];
				e.appendChild(document.createTextNode("    or Saved mappings: "));
				FlexImport.recTypeSelectSavedMapping = e.appendChild(document.createElement("select"));
				FlexImport.recTypeSelectSavedMapping.onchange = function() {
						FlexImport.reapplyMapping();
				};
				var opt = document.createElement("option");
				opt.innerHTML = "to select...";
				opt.disabled = true;
				opt.selected = true;
				FlexImport.recTypeSelectSavedMapping.appendChild(opt);

				var i, l = savedMapping.length;
				for (i = 0; i < l; ++i) {
					opt = document.createElement("option");
					opt.value = savedMapping[i];
					opt.innerHTML = savedMapping[i];
					FlexImport.recTypeSelectSavedMapping.appendChild(opt);
				}

			}

			var baseurl = HeuristBaseURL+"import/delimited/importDelimitedMapping.php";
			var callback = _onLoadSavedMappingsList;
			var params = "mode=list&db=" + HAPI.database;
			top.HEURIST.util.sendRequest(baseurl, callback, params);

	},

	saveMappings: function () {

			function _onSaveMappingsList(context){
				alert(context.response);
			}

			var sel = FlexImport.recTypeSelect;

			if(sel && sel.selectedIndex>0){

				var recordType = sel.options[sel.selectedIndex].text;

				var i,
					atleastOne = false,
					content = [],
					l = FlexImport.colSelectors.length;

				content.push(sel.value);

				for 	(i = 0; i < l; ++i) {
					if (FlexImport.colSelectors[i].selectedIndex > 0) {
						atleastOne = true;
						content.push(FlexImport.colSelectors[i].value);
					}else{
						content.push("0");
					}
				}

				if(atleastOne){
					var baseurl = HeuristBaseURL+"import/delimited/importDelimitedMapping.php";
					var callback = _onSaveMappingsList;
					var params = "mode=save&db=" + HAPI.database+"&file="+recordType+"&content="+content.join(",");
					top.HEURIST.util.sendRequest(baseurl, callback, params);
				}else{
					alert("Define at least one mapping value");
				}

			}
	},

	createRecTypeOptions: function () {
		var e = $("#rec-type-select-div")[0];
		e.appendChild(document.createTextNode("Record types: "));
		FlexImport.recTypeSelect = e.appendChild(document.createElement("select"));
		FlexImport.recTypeSelect.onchange = function() { FlexImport.createColumnSelectors(null) };
		var opt = document.createElement("option");
		opt.innerHTML = "to select...";
		opt.disabled = true;
		opt.selected = true;
		FlexImport.recTypeSelect.appendChild(opt);
		var recTypes = HRecordTypeManager.getRecordTypes();
		var i, l = recTypes.length;
		for (i = 0; i < l; ++i) {
			opt = document.createElement("option");
			opt.value = recTypes[i].getID();
			opt.innerHTML = recTypes[i].getName();
			FlexImport.recTypeSelect.appendChild(opt);
		}
	},

	createColumnSelectors: function (applyMapping) {

		FlexImport.showProgress();
		setTimeout(function() {

		var e = $("#col-select-div")[0];

		FlexImport.recType = HRecordTypeManager.getRecordTypeById(FlexImport.recTypeSelect.value);

		// remove the previous record type record display since we will recreate it here
		$(e).empty();
		$("#records-div").empty();
		$("#records-div-info").empty();

/* Ian 14/3/12:
    I can't work out what this section does - it does not seem to set anything in the record created,
    even less give you a lsit of tags for the workgroup selected. It's not particualrly necessary, so easier to omit than fix.

		var p = e.appendChild(document.createElement("p"));
		p.appendChild(document.createTextNode("Apply workgroup tags for workgroup: "));
		FlexImport.workgroupSelect = p.appendChild(document.createElement("select"));
		FlexImport.workgroupSelect.onchange = function() {
			FlexImport.workgroupTags = {};
			if (this.value) {
				var wgkwds = HWorkgroupTagManager.getWorkgroupTags(FlexImport.workgroups[this.value]);
				var i, l = wgkwds.length;
				for (i = 0; i < l; ++i) {
					FlexImport.workgroupTags[wgkwds[i].getName()] = wgkwds[i];
				}
			}
		}

		var wgs = HWorkgroupManager.getWorkgroups();
		_addOpt(FlexImport.workgroupSelect, "", "select...");
		var i, l = wgs.length;
		for (i = 0; i < l; ++i) {
			FlexImport.workgroups[wgs[i].getID()] = wgs[i];
			_addOpt(FlexImport.workgroupSelect, wgs[i].getID(), wgs[i].getName());
		}
		p.appendChild(document.createTextNode(" NOTE: Workgroup tags must already exist. "));
		var a = p.appendChild(document.createElement("a"));
			a.target = "_blank";
			a.href = HeuristBaseURL + "admin/ugrps/editGroupTags.php?db="+HAPI.database
			a.innerHTML = "Create new tags";
		p.appendChild(document.createTextNode(" then start over"));
*/

		p = e.appendChild(document.createElement("p"));
		a = p.appendChild(document.createElement("a"));
		a.target = "_blank";
			a.href = HeuristBaseURL +"admin/describe/listRectypeDescriptions.php?db="+ HAPI.database +"#rt" + FlexImport.recType.getID();
		a.innerHTML = "Show field list for <b>" + FlexImport.recType.getName() + "</b>";


		p = e.appendChild(document.createElement("p"));
		var hh = p.appendChild(document.createElement("label"));
		hh.innerHTML = "bold in pulldown list = required";


		var i, l = FlexImport.columnCount;
		var table = document.createElement("table");
		table.id = "col-select-table";
		var tbody = table.appendChild(document.createElement("tbody"));
		//create header row if supplied
		var headerRow = null;
		if (FlexImport.hasHeaderRow){
			headerRow = FlexImport.fields[0];
			tr = tbody.appendChild(document.createElement("tr"));
			tr.id = "col-header-row";
			td = tr.appendChild(document.createElement("td"));
			td.innerHTML = "Column Heading"; //
			for (i = 0; i < headerRow.length; ++i) {
				td = tr.appendChild(document.createElement("td"));
				if(headerRow[i].charAt(0)=='"'){
					headerRow[i] = headerRow[i].substr(1,headerRow[i].length-2);
				}
				td.innerHTML = headerRow[i];
			}
		}
		//create row of field type selectors
		var tr = tbody.appendChild(document.createElement("tr"));
		tr.id = "col-select-row";
		var td, sel, opt, cbkey;

		tr = tbody.appendChild(document.createElement("tr"));
		tr.id = "col-select-row";

		td = tr.appendChild(document.createElement("td"));
		td.innerHTML = "<div id='lblKeyFields' style='width:100%;text-align:right;visibility:hidden'>Select key fields:</div><br>row number";
		for (i = 0; i < l; ++i) {
			// add column select header for selecting detail type for this column
			td = tr.appendChild(document.createElement("td"));
            cbkey = td.appendChild(document.createElement("input"));
            cbkey.type = 'checkbox';
            cbkey.style.visibility = 'hidden';
            cbkey.id = "cb"+i;
            
            FlexImport.colKeys[i] = cbkey;
            td.appendChild(document.createElement("br"));
            
			sel = td.appendChild(document.createElement("select"));
            sel.id = "sel"+i;
            
			sel.onchange = function() {

                var isRecHeaderFlds = (this.value == "null" || this.value == "url"  ||  this.value == "scratchpad"  ||
                    this.value == "tags"  ||  this.value == "wgTags");
                var  variety = null;    
                if(!isRecHeaderFlds){
                    var dt = HDetailManager.getDetailTypeById(this.value);
                    if(dt){
                        variety = dt.getVariety();    
                    }
                }
                
				if (this.value != "tags"  &&  this.value != "wgTags") {
					//search if this detail is in another column and remove it from the other column if it is
					// todo: should we stop people putting data from two columns into the same field? I think not. Maybe popup an alert
					var j, m = FlexImport.colSelectors.length;
					for (j = 0; j < m; ++j) {
						var s = FlexImport.colSelectors[j];
						if (s != this  &&  s.value == this.value && variety != HVariety.GEOGRAPHIC) {
							s.selectedIndex = 0;
							if (s.subTypeSelect) { alert("Warning: you've selected the same target field for more than one source field");
								// s.parentNode.removeChild(s.subTypeSelect);
							}
						}
					}
				}
                
                
                cbkey = document.getElementById("cb"+this.id.substr(3));
                var lbl = document.getElementById('lblKeyFields');
                $allcbs = $('[type="checkbox"][id^="cb"]');
                    
                if (isRecHeaderFlds || variety == HVariety.GEOGRAPHIC || variety == HVariety.FILE) {
                    cbkey.style.visibility = 'hidden';
                    lbl.style.visibility = 'hidden';
                    $allcbs.each(function() {
                        if(this.style.visibility == 'visible'){
                            lbl.style.visibility = 'visible';
                            return false;
                        }
                    });
                }else{
                    cbkey.checked = false;
                    cbkey.style.visibility = 'visible';  
                    lbl.style.visibility = 'visible';  
                }
                //if one of selection is record id disable all checkboxes
                
                $allcbs.attr('disabled', false);
                $allcbs.attr('title', 'Use as key field: Heurist will attempt to match key fields to identify existing records for update rather than addition of a new record');
                var $sels = $('select[id^="sel"]');
                $sels.each(function() {
                    if(this.value=='record id'){
                        $allcbs.attr('disabled', true);
                        $allcbs.attr('checked', false);
                        $allcbs.attr('title', 'De-select Record ID matching if you wish to match records based on other key fields');
                        var cbkey = document.getElementById("cb"+this.id.substr(3));
                        cbkey.checked = true;
                        return false;
                    }
                });
                
                
				// for types that have subtypes show select for subtypes
				if (!isRecHeaderFlds &&
					variety == HVariety.GEOGRAPHIC) {
					this.subTypeSelect = this.parentNode.appendChild(document.createElement("select"));
					var vals = [
						[ HGeographicType.POINT, "POINT" ],
						[ HGeographicType.BOUNDS, "BOUNDS" ],
						[ HGeographicType.POLYGON, "POLYGON" ],
						[ HGeographicType.PATH, "PATH" ],
						[ HGeographicType.CIRCLE, "CIRCLE"],
                        [ "lng", "POINT longitude (X)" ],
                        [ "lat", "POINT latitude (Y)" ],
					];
					var v, m = vals.length;
					for (v = 0; v < m; ++v) {
						var o = this.subTypeSelect.appendChild(document.createElement("option"));
						o.value = vals[v][0];
						o.innerHTML = vals[v][1];
					}
				} else {   //FIXME add code here to create selection of reference type data (currently handles only heurist ids)
					if (this.subTypeSelect) {
						this.parentNode.removeChild(this.subTypeSelect);
					}
				}
			};
            

			// fill in comlumn selector options for recType
			FlexImport.colSelectors[i] = sel;
			var columnName = "";
			// if the user supplied a header row and there is a collumn heading for the current column
			if (FlexImport.hasHeaderRow && headerRow && headerRow[i]){
				columnName = headerRow[i].toLowerCase();
				columnName = columnName.replace(/^\s*/,"");
				columnName = columnName.replace(/\s*$/,"");
			}
			opt = sel.appendChild(document.createElement("option"));
			opt.value = null; opt.innerHTML = "do not import";

			var grp = sel.appendChild(document.createElement("optgroup"));
			grp.label = (navigator.userAgent.indexOf('Firefox')>0)?" ":"---";

            _addOpt(sel, "record id", "Record ID", columnName == "record id(s)");
			_addOpt(sel, "tags", "Tag(s)", columnName == "tag(s)");
			_addOpt(sel, "wgTags", "Workgroup Tag(s)", columnName == "workgroup tag(s)");

			var reqDetailTypes = HDetailManager.getRequiredDetailTypesForRecordType(FlexImport.recType);
			var detailTypes = HDetailManager.getDetailTypesForRecordType(FlexImport.recType);
			var d, k, rdl = reqDetailTypes.length;
			var dl = detailTypes.length;

			var alist = [];

			alist.push({id:'url', name:'URL', selected:(columnName == 'url'), req:false});
			alist.push({id:"scratchpad", name:'Scratchpad', selected:(columnName == "scratchpad"), req:false});

			var recStructure = top.HEURIST.rectypes.typedefs[FlexImport.recType.getID()].dtFields;
			var dtyName_ind = top.HEURIST.rectypes.typedefs.dtFieldNamesToIndex.rst_DisplayName;
			var fieldTypes = top.HEURIST.detailTypes.typedefs;
			var dtyType_ind = top.HEURIST.detailTypes.typedefs.fieldNamesToIndex.dty_Type;

			for (d = 0; d < dl; ++d) {

				var det_id = detailTypes[d].getID();

				if("separator" != fieldTypes[det_id].commonFields[dtyType_ind])
				{

					var rdName = HDetailManager.getDetailNameForRecordType(FlexImport.recType, detailTypes[d]);
					var isrequired = false;

					for (k = 0; k < rdl; ++k) {
						if(det_id == reqDetailTypes[k].getID()){
							isrequired = true;
							break;
						}
					}

					//since HAPI returns generic field names rather than record specific - take the correct name from top.HEURIST
                    if(recStructure[det_id]){
					    rdName = recStructure[det_id][dtyName_ind];
                        alist.push({id:det_id, name:rdName, selected:(columnName == rdName.toLowerCase()), req:isrequired});
                    }
				}
			}

			//sort by name
			alist.sort(function (a,b){
				return a.name<b.name?-1:1;
			});

			grp = sel.appendChild(document.createElement("optgroup"));
			grp.label = (navigator.userAgent.indexOf('Firefox')>0)?" ":"---";

			//create options for select
			for (d = 0; d < alist.length; ++d) {
				var opt = _addOpt(sel, alist[d].id, alist[d].name, alist[d].selected);
				if(alist[d].req){
                    
					opt.className = "required";
				}
			}

//_addOpt(sel, val, text, selected)

/* it works!!! but it used before 2012-02-14
			var rdIndex = {};
			var grp = sel.appendChild(document.createElement("optgroup"));
			grp.label = "Required fields";
			//todo: doesn't list required fields under this heading, lsits required and optional under the Other fields heading
			for (d = 0; d < rdl; ++d) {
				var rdID = reqDetailTypes[d].getID();
				var rdName = HDetailManager.getDetailNameForRecordType(FlexImport.recType, reqDetailTypes[d]);
				var opt = _addOpt(sel, rdID,  rdName, columnName == rdName.toLowerCase());
				opt.className = "required";
				for (var r = 0; r < dl; ++r){
					if (rdID == detailTypes[r].getID()) {
						rdIndex[r] = true;
					}
				}
			}
			grp = sel.appendChild(document.createElement("optgroup"));
			grp.label = "Optional fields";
			for (d = 0; d < dl; ++d) {
				if (rdIndex[d]) continue;
				var rdName = HDetailManager.getDetailNameForRecordType(FlexImport.recType, detailTypes[d]);
				var opt = _addOpt(sel, detailTypes[d].getID(),rdName,columnName == rdName.toLowerCase());
			}
*/
			if(i<FlexImport.cols.length &&  FlexImport.cols[i]){
				sel.value = FlexImport.cols[i];
			}
		}//for
        
        
		// create rest of table filling it with the csv analysed data
		for (var i = FlexImport.hasHeaderRow ? 1:0; i < FlexImport.fields.length; ++i) {
			var inputRow = FlexImport.fields[i];
			if(top.HEURIST.util.isnull(inputRow)) {
				inputRow = "";
			}
			tr = tbody.appendChild(document.createElement("tr"));
			if (FlexImport.lineErrorMap[i]) {
				if(FlexImport.lineErrorMap[i].invalidRecord){
					tr.className = "invalidRecord";
					tr.title = FlexImport.lineErrorMap[i].invalidRecord;
				}else if(FlexImport.lineErrorMap[i].duplicateRecord){
					tr.className = "duplicateRecord";
					tr.title = FlexImport.lineErrorMap[i].duplicateRecord;
				}
			}
			td = tr.appendChild(document.createElement("td"));
			td.innerHTML = i + (FlexImport.hasHeaderRow?0:1); //row number
			//for each column
			for (var j = 0; j < FlexImport.columnCount; ++j) {
				td = tr.appendChild(document.createElement("td"));
				if (inputRow.length > j) {
					//strip quotes if necessary
					re = new RegExp ("^\\s*" + FlexImport.quote + "(.*)" +FlexImport.quote +"\\s*$");
					if (inputRow[j].toString().match(re)) {
						inputRow[j] = inputRow[j].toString().match(re)[1];
                        FlexImport.fields[i][j] = inputRow[j].trim();
					}
					if (FlexImport.lineErrorMap[i] && FlexImport.lineErrorMap[i][j]){
						//td.className = "invalidInput";
						var p = td.appendChild(document.createElement("p"));
						p.id = "wrin"+i+"_"+j;
						p.className = "errorMsg";
						p.innerHTML = FlexImport.lineErrorMap[i][j];

					} else {
						var str = inputRow[j];
						if(str.length>50){
							str = str.substring(0,50)+"..";
						}
						td.appendChild(document.createTextNode(str));
					}
				}
			}
		}

		e.appendChild(table);

		FlexImport.showErrorSummary(table);

		FlexImport.gotoStep(2);
		if(applyMapping) { applyMapping.call(FlexImport); }

        for (var i = 0; i<FlexImport.colSelectors.length; ++i) {
            FlexImport.colSelectors[i].onchange();
        }

        
        
		},200);
	}, //end createColumnSelectors

	//
	// creates the table with list of wrong and unrecognized values
	//
	showErrorSummary: function (before) {

		var eS = FlexImport.errorSummary;

		if(eS){

			var fieldTypes = top.HEURIST.detailTypes.typedefs;
			var dtyType_ind = top.HEURIST.detailTypes.typedefs.fieldNamesToIndex.dty_Type;

			var e = $("#col-select-div")[0];

			var table = document.createElement("table");
			table.id = "col-select-table";
			var tbody = table.appendChild(document.createElement("tbody"));

			var headerRow = FlexImport.fields[0];
			var haserr = false;

			for (var j = 0; j < FlexImport.columnCount; ++j) {
				if(eS[j]){
					tr = tbody.appendChild(document.createElement("tr"));
					td = tr.appendChild(document.createElement("td"));
					td.innerHTML = (FlexImport.hasHeaderRow)?headerRow[j]:("column "+j); //name of column with wrong values

					td = tr.appendChild(document.createElement("td"));
					td.style.width = 220;

					for (var errVal in eS[j]) {

						/*var temp = "";
						if (eS[j][i]) {
							temp += "Correct value : " + eS[j][i];
						}else {
							temp += "Enter a correct value here."
						}*/
						var p = td.appendChild(document.createElement("div"));
						var ed = p.appendChild(document.createElement("input"));
						ed.id = "edcorrect"+j +"-" + eS[j][errVal][0];
						ed.style.width = 120;
						ed.value = errVal;
						ed.className = "invalidInput";
						ed.cleared = false;
						/*ed.onfocus = function () {
								if (!this.cleared) {
									this.value = "";
									this.cleared = true;
								}
						};*/

						var btn = p.appendChild(document.createElement("button"));
						btn.col = j;
						btn.rows = eS[j][errVal];
						btn.incorrectValue = errVal?errVal:"";
						btn.innerHTML = "Modify data";
						btn.onclick = function () {

								//was FlexImport.fields[this.row][this.col] = this.value;
								var edinput = document.getElementById("edcorrect"+this.col+"-"+this.rows[0]);
								var incorrectValue = this.incorrectValue;
								var i, len = this.rows.length;
								for (i=0; i<len; i++) {
									if(incorrectValue==FlexImport.fields[this.rows[i]][this.col]){
										FlexImport.fields[this.rows[i]][this.col] = edinput.value;
										//find the table cell with wrong value
										var td_tocorrect = document.getElementById("wrin"+this.rows[i]+"_"+this.col);
										if(td_tocorrect){
											td_tocorrect.innerHTML = edinput.value;
										}
									}
								}
						};

						//td.appendChild(document.createElement("br"));
					}
					//var s = eS[j].join("<br/>");
					//td.innerHTML = s;

					td = tr.appendChild(document.createElement("td"));
					if(FlexImport.colSelectors[j].selectedIndex>0){

						//for enum and relmarker only only
						var dt_id = FlexImport.colSelectors[j].value;

						if("relmarker" == fieldTypes[dt_id].commonFields[dtyType_ind] ||
							"enum" == fieldTypes[dt_id].commonFields[dtyType_ind])
						{

							var a = td.appendChild(document.createElement("a"));
							a.href = "#";
							a.innerHTML = "Edit field definition";
							a.id2 = FlexImport.colSelectors[j].value;
							var _onEditClick = function (e){

								var dtid = e.target.id2;
								var url = HeuristBaseURL+
									"admin/structure/fields/editDetailType.html?db="+HAPI.database+"&detailTypeID="+dtid;

								top.HEURIST.util.popupURL(top, url,
								{	"close-on-blur": false,
									"no-resize": false,
										height: 700,
										width: 700,
									callback: function(context) {
									}
								});

							};
							a.onclick = _onEditClick;

						}
					}
					haserr = true;
				}
			}
			if(haserr){
				e.insertBefore(table, before);

				$("#btn_correct").hide();
				$("#btn_save").hide();
				$("#btn_prepare").show();
				$("#step3-info").html("Unrecognised values in imported data. After correction of values below click 'Prepare Records' again");

				var dvm = document.createElement("div");
				dvm.innerHTML = "<p>Edit values below and click Modify Data to change the values.<br/>"+
								"Alternatively, edit the field definitions with the <u>Edit field definition</u> link(s) to the right.</p>";
				e.insertBefore(dvm, table);
			}

		}
	},

    //
	// This function preloads all records necessary for REFERENCE detail types
    // clicked by Prepare Records
	loadReferencedRecords: function ()
	{
        if(top.HEURIST.util.isnull(FlexImport.recType)){
           alert('Select record type');
           return;
        }
        
        
		var detailType;
		var refCols = [];
		var recIDs = [];
		var recID = "";
		var valCheck = {};
        

		//get list of required field types
		var reqDetailTypes = HDetailManager.getRequiredDetailTypesForRecordType(FlexImport.recType);
		var k;
        
        FlexImport.cols_uniqkey = [];

		//detect what fields to be imported
		var i, l = FlexImport.colSelectors.length;
		for (i = 0; i < l; ++i) {
			if (FlexImport.colSelectors[i].selectedIndex > 0) {
				FlexImport.cols[i] = FlexImport.colSelectors[i].value;
                
                //@todo checkbox for tags and wgTags must be invisible
                if(FlexImport.cols[i]=="record id" ||
                    (FlexImport.colKeys[i].checked && FlexImport.cols[i]!=="tags"  &&  FlexImport.cols[i]!== "wgTags"))
                {
                     FlexImport.cols_uniqkey.push(FlexImport.colSelectors[i].value);
                }               
                
			}else if(i<FlexImport.cols.length){
				FlexImport.cols[i] = undefined;
			}
			FlexImport.subTypes[i] = FlexImport.colSelectors[i].subTypeSelect ? FlexImport.colSelectors[i].subTypeSelect.value : null;
			if ( FlexImport.cols[i]  &&  FlexImport.cols[i]!=="tags"   &&  FlexImport.cols[i]!== "wgTags" && FlexImport.cols[i] !== "url" && FlexImport.cols[i] !== "scratchpad") {
				detailType = HDetailManager.getDetailTypeById(FlexImport.cols[i]);
                if(detailType)    
                {
					for (k = 0; k < reqDetailTypes.length; ++k) {
						if(detailType.getID() == reqDetailTypes[k].getID()){
							reqDetailTypes.splice(k,1);
							break;
						}
					}


				    //mark which columns have the REFERENCE identifying data
				    if (detailType.getVariety() == HVariety.REFERENCE) {
					    if (HDetailManager.getDetailRepeatable(FlexImport.recType, detailType)) {
						    refCols[i]=2;//mark as repeatable  todo:SAW should store max value and constrain against that.
					    } else {
						    refCols[i]=1;//mark as single value
					    }
				    }
                }
			}
		}

		if(reqDetailTypes.length>0){
			var s = '';
			var recStructure = top.HEURIST.rectypes.typedefs[FlexImport.recType.getID()].dtFields;
			var dtyName_ind = top.HEURIST.rectypes.typedefs.dtFieldNamesToIndex.rst_DisplayName;

			for (k = 0; k < reqDetailTypes.length; ++k) {
					s = s + recStructure[reqDetailTypes[k].getID()][dtyName_ind]+'\n';
			}
			alert("The following required field types are not mapped:\n"+s);
			return;
		}

		// build string for the query of referenced heurist records to load into cache
		// FIXME  add code to handle record type field value queries for lookup type queries
		// Example- type:Person field:"Given names":Bruce
		if (refCols.length > 0) {
			for (var i = 0; i < FlexImport.fields.length; ++i) {
				for (var j in refCols) {
					if (!j) continue; // skip any undefined entries
					var tempRecIDs = FlexImport.fields[i][j];
					if (tempRecIDs) {
						if (tempRecIDs.search("/"+this.valSep+"/")){//if there is a
							tempRecIDs = tempRecIDs.split(this.valSep);
						}else{
							tempRecIDs = tempRecIDs.split(","); // split into array of ids with comma as delimiter
						}
						if (refCols[j] == 1) { // non repeatable so just take the first value. FIXME add display warning
							tempRecIDs = tempRecIDs.splice(0,1);
						}
						for (var k =0; k < tempRecIDs.length; k++) {
							recID = parseInt( tempRecIDs[k]);
							if (recID && !valCheck[recID]) {
								recIDs.push(recID);
								valCheck[recID] = true;
							}
						}
					}
				}
			}
		}
		if (recIDs.length > 0) {
			var myquery = "ids:" + recIDs.join(",");
			FlexImport.Loader.loadRecords(myquery)
		} else {
			FlexImport.createRecords();
		}
	},

	transformDates: function () {
		var i, j, detailType, val, reString, re, matches, dateTransform, indices,
		pad = function (s) {
			s = String(s);
			return s.length < 2  ?  "0" + s  :  s;
		};

		for (j = 0; j < FlexImport.cols.length; ++j) {
			if (parseInt(FlexImport.cols[j]) > 0) {
				detailType = HDetailManager.getDetailTypeById(FlexImport.cols[j]);
				if (detailType.getVariety() === HVariety.DATE) {
					dateTransform = null;
					for (i = 0; i < FlexImport.fields.length; ++i) {
						val = FlexImport.fields[i][j];
						if (val.match(/\|VER\=/i)) {	// temporal string so don't transform
							try{
								Temporal.parse(val);
							}catch(e){
								alert("Warning: temporal format in column " + j + ", row " + i + ": " + val +
										" will not create temporal object - " + e );
							}
							continue;
						}
						reString = "(\\d\\d?)\\/(\\d\\d?)\\/(\\d{4})";//check mm/dd/yyyy or dd/mm/yyyy
						re = new RegExp(reString);
						matches = val.match(re);
						if (matches) {
							if (matches[1] > 12) {
								if (dateTransform  &&  (dateTransform[0] != reString  ||  dateTransform[1][1] != 2)) {
									alert("Warning: inconsistent date format in column " + j + ", row " + i + ": " + val);
								} else {
									dateTransform = [ reString, [3, 2, 1] ];
								}
							} else if (matches[2] > 12) {
								if (dateTransform  &&  (dateTransform[0] != reString  ||  dateTransform[1][1] != 1)) {
									alert("Warning: inconsistent date format in column " + j + ", row " + i + ": " + val);
								} else {
									dateTransform = [ reString, [3, 1, 2] ];
								}
							}
						} else {
							reString = "(\\d{4})\\/(\\d\\d?)\\/(\\d\\d?)"; //check yyyy/mm/dd
							re = new RegExp(reString);
							matches = val.match(re);
							if (matches) {
								if (dateTransform  &&  dateTransform[0] != reString) {
									alert("Warning: inconsistent date format in column " + j + ", row " + i + ": " + val);
								} else {
									dateTransform = [ reString, [1, 2, 3] ];
								}
							}
						}
					}
					if (dateTransform) {
						re = new RegExp(dateTransform[0]);
						indices = dateTransform[1];
						for (i = 0; i < FlexImport.fields.length; ++i) {
							val = FlexImport.fields[i][j];
							matches = val.match(re);
							if (matches) {
								FlexImport.fields[i][j] =
										matches[indices[0]] + "-" +
									pad(matches[indices[1]]) + "-" +
									pad(matches[indices[2]]);
							}
						}
					}
				}
			}
		}
	},

	makeHash: function (fields) {
		hash = "";
		for (var j = 0; j < fields.length; ++j) {
			if (FlexImport.cols[j]  &&
				FlexImport.cols[j] != "tags"  &&
				FlexImport.cols[j] != "wgTags") {
				hash += fields[j];
			}
		}
		return hash;
	},


	createRecords: function () {
		var detailType;
		var record;
		var error;
		var val;

		FlexImport.showProgress();
		setTimeout(function() {

		FlexImport.clearRecords();
		FlexImport.colSelectors = [];
		$("#col-select-div").empty();

		//e.innerHTML += ("<p>If there are errors: <input type=button value=\"Go back\" onclick=\"FlexImport.createColumnSelectors();\"></p>");

		var e = $("#records-div")[0];

		var table = e.appendChild(document.createElement("table"));
		var tbody = table.appendChild(document.createElement("tbody"));
		var tr, td;

		// header row
		tr = tbody.appendChild(document.createElement("tr"));
		td = tr.appendChild(document.createElement("td"));
        td = tr.appendChild(document.createElement("td"));
        td.innerHTML = "ID";
		var tags = false;
		var kwds = false;
		var j, l = FlexImport.cols.length;
		for (j = 0; j < l; ++j) {
			if (! FlexImport.cols[j]  || FlexImport.cols[j]=="record id" || 
              (FlexImport.cols[j]=="tags" && tags)  ||  (FlexImport.cols[j]=="wgTags" && kwds)) continue;
             
			td = tr.appendChild(document.createElement("td"));
			if (FlexImport.cols[j] == "url") {
				td.innerHTML = "URL";
			} else if (FlexImport.cols[j] == "scratchpad") {
				td.innerHTML = "Scratchpad";
			} else if (FlexImport.cols[j] == "tags") {
				if (! tags) {
					tags = true;
					td.innerHTML = "Tag(s)";
				}
			} else if (FlexImport.cols[j] == "wgTags") {
				if (! kwds) {
					kwds = true;
					td.innerHTML = "Workgroup Tag(s)";
				}
			} else if (FlexImport.cols[j]) {
				detailType = HDetailManager.getDetailTypeById(FlexImport.cols[j]);
				td.innerHTML = HDetailManager.getDetailNameForRecordType(FlexImport.recType, detailType);
			}
		}

		var now = new Date();
		var y = now.getFullYear();
		var m = now.getMonth() + 1; if (m < 10) m = "0" + m;
		var d = now.getDate(); if (d < 10) d = "0" + d;
		var h = now.getHours(); if (h < 10) h = "0" + h;
		var min = now.getMinutes(); if (min < 10) min = "0" + min;
		var s = now.getSeconds(); if (s < 10) s = "0" + s;

		var importTag = "FlexImport " + y + "-" + m + "-" + d + " " + h + ":" + min + ":" + s;
		try {
			HTagManager.addTag(importTag);
		} catch(e) {
			alert("error adding Tag :" + e);
		}

		FlexImport.transformDates();
		FlexImport.reqDetailsMap = {};
		var reqDetails = HDetailManager.getRequiredDetailTypesForRecordType(FlexImport.recType);
		for (var r =0; r < reqDetails.length; ++r) {
			FlexImport.reqDetailsMap[reqDetails[r].getID()] = true;
		}

		FlexImport.errorSummary = new Array(FlexImport.columnCount);
		FlexImport.num_err_columns = 0;
		FlexImport.num_err_values = 0;
		FlexImport.num_invalid_records = 0;

		var dup_rec_mode = $('input[name=rg_duprec]:checked').val();

		// create records
		l = FlexImport.fields.length;
		var istart = (FlexImport.hasHeaderRow)?1:0,
			i,lineHash;
		for ( i = istart; i < l; i++) {
			record = null;
			lineHash = FlexImport.makeHash(FlexImport.fields[i]);

			if (FlexImport.lineHashes[lineHash] != undefined) {
				// we've already created a record for an identical input lineHash
				//ask user if this is a Duplicate or a different and unique record
				if ((dup_rec_mode==="0") || (dup_rec_mode==="2"  && confirm("Line " + i + " may be duplicate of line " + FlexImport.lineHashes[lineHash] +
					" (selected fields have identical values)\n\nOK = store as one record,\n\nCancel = create two records ")))
				{
					record = FlexImport.lineRecordMap[FlexImport.lineHashes[lineHash]];
					error = {"duplicateRecord":"duplicate of record on line " + FlexImport.lineHashes[lineHash]};
				}
			}
			if (!record) {
				FlexImport.lineHashes[lineHash] = i;
				var ret = FlexImport.createRecord(FlexImport.recType, importTag, FlexImport.fields[i]);
				record = ret.record;
				error = ret.error;
				if (!error || !error.invalidRecord) {	// only add valid records to be saved note erroneous optional data doesn't prohibit saving (it's ignored)
					FlexImport.records.push(record);
				}
			}
			FlexImport.lineRecordMap[i] = record;
			if (error) {
				FlexImport.lineErrorMap[i] = error;
			}

			// display record using the information from the initialized record (this ensures correct formating)
			tr = tbody.appendChild(document.createElement("tr"));
			if (error) {
				if (error.invalidRecord) {
					tr.className = "invalidRecord";
					tr.title = error.invalidRecord;
					FlexImport.num_invalid_records++;
				}else if (error.duplicateRecord) {
					tr.className = "duplicateRecord";
					tr.title = error.duplicateRecord;
				}
			}
			td = tr.appendChild(document.createElement("td"));
			td.innerHTML = i;
            td = tr.appendChild(document.createElement("td")); //record id
            td.id = "tdrecid"+i;
            td.innerHTML = record.getID();

			tags = false; kwds = false;
			for (var j = 0; j < FlexImport.fields[i].length; ++j) {
				if (! FlexImport.cols[j]  || FlexImport.cols[j]=="record id" || 
                        (FlexImport.cols[j]=="tags" && tags)  ||  (FlexImport.cols[j]=="wgTags" && kwds)) continue;

				var inputRow = FlexImport.fields[i];

				td = tr.appendChild(document.createElement("td"));
				if (FlexImport.cols[j] == "url") {
					td.innerHTML = "<p>" + record.getURL() + "</p>";
				} else if (FlexImport.cols[j] == "scratchpad") {
					td.innerHTML = "<p>" + record.getNotes() + "</p>";
				} else if (FlexImport.cols[j] == "tags") {
					if (! tags) {
						tags = true;
						td.innerHTML = "<p>" + record.getTags().join(", ") + "</p>";
					}
				} else if (FlexImport.cols[j] == "wgTags") {
					if (! kwds) {
						kwds = true;
						var temp = "<p>";
						var ks = record.getWgTags();
						for (var k = 0; k < ks.length; ++k) {
							temp += (k > 0 ? ", " : "") + ks[k].getName();
						}
						td.innerHTML = temp + "</p>";
					}
				} else if (FlexImport.cols[j]) { // display the other details from the created record
					detailType = HDetailManager.getDetailTypeById(FlexImport.cols[j]);
					td.innerHTML = "<p>" + record.getDetails(detailType).join("\n") + "</p>";
				}
				if (error && error[j]) {
					td.className = "invalidInput";
					td.innerHTML += "<p class=errorMsg>" + error[j] + "</p>";

						var eS = FlexImport.errorSummary;
						if(eS[j] && eS[j][inputRow[j]]){
//							if(eS[j].indexOf(inputRow[j])<0){
								FlexImport.num_err_values++;
								eS[j][inputRow[j]].push(i);
//							}
						}else{
							FlexImport.num_err_columns++;
							FlexImport.num_err_values++;
							if (!eS[j]) {
								eS[j] = {};
							}
							eS[j][inputRow[j]] = [i];
						}
				}
			} // for j in FlexImport.fields loop
		} // for i = 0 loop

        FlexImport.findExistingRecordsForUpdateMode(0);
        
		},200);
        
    },
    
    findExistingRecordsForUpdateMode:function(index){
   
        if(index>=FlexImport.records.length || FlexImport.cols_uniqkey.length<1){
        
            //setTimeout(function() {              }, 200);
            FlexImport.gotoStepSaveRecords();
            
            
        }else{
        
            // in case of UPDATE mode 
            // 1. create query string for each record 
            // 2. find the record 
            // 3. 
        
            //HRecord
            var record = FlexImport.records[index];
            var i, dtid, dt;
            var query = "";
            
            //1. compose search string based on markerd columns colKeys
            var l= FlexImport.cols_uniqkey.length;    //array of detail type ids
            for(i=0; i<l; ++i){
                dtid = FlexImport.cols_uniqkey[i];
                if(dtid == "record id"){
                    if(!top.HEURIST.util.isempty(record.getID())){
                        query = "ids:"+record.getID();
                    }else{
                        record.setID(null);
                    }
                    break;
                }else             
                if (dtid != "url"  &&  dtid != "scratchpad"  &&
                    dtid != "tags"  &&  dtid != "wgTags"){
                        dt = HDetailManager.getDetailTypeById(dtid);
                        if(dt && dt.getVariety() != HVariety.GEOGRAPHIC && dt.getVariety() != HVariety.FILE){
                            var vals = record.getDetails(dt);           
                            if(vals && vals.length>0 && vals[0] && !top.HEURIST.util.isempty(vals[0])){
                                query = query + " f:"+dtid+":"+vals[0].substr(0,50);
                            }
                        }
                }
            }
            
            if(dtid != "record id" && !top.HEURIST.util.isempty(query)){
                query = 't:'+FlexImport.recTypeSelect.value+query;
            }
            
            //2. perform search
            if(query){
                var baseSearch = new HSearch(query, null);
                var myLoader = new HLoader(
                    function(s, r) {    // onload
                        var ss = "", recID = null;
                        if (r.length == 1) { //exact found
                            var record_found = r[0];
                            recID = record_found.getID();
                            ss = recID;
                            //add special marker for all named details - to delete the existing ones
                            record.markDetailsForDelete();
                            
                        } else if (r.length > 1) {// more than 1
                            ss = "ambiguity: "+r.length+" recs";
                        } else {
                            ss = "not found. insert"
                        }
                        record.setID(recID);
                        var td = document.getElementById("tdrecid"+index);
                        td.innerHTML = ss;
                        FlexImport.findExistingRecordsForUpdateMode(index+1);
                    },
                    function(r,e) {
                        var td = document.getElementById("tdrecid"+index);
                        td.innerHTML = "error search";
                        FlexImport.findExistingRecordsForUpdateMode(index+1);
                    }                    
                );
                HeuristScholarDB.loadRecords(baseSearch, myLoader);                
                
            }else{
                FlexImport.findExistingRecordsForUpdateMode(index+1);
            }
        }
        
        
    },
    
    gotoStepSaveRecords:function(){

        FlexImport.gotoStep(3);

        // show command button for saving records
        var e = $("#records-div-info")[0];

        $("#btn_correct").show();
        $("#btn_save").hide();
        $("#btn_prepare").hide();
        $("#step3-info").html("Unrecognised values in imported data. Click 'Correct the Data' to change the values");
        $("#prepare-info-div").html("");

        if(FlexImport.num_err_values>0){
            $("#prepare-info-div").html("<div class='invalidInput'>There are "+FlexImport.num_err_values+" unexpected values in "+
                            FlexImport.num_err_columns+" columns. </div>");
            //e.innerHTML = "<p class='invalidInput'>There are "+FlexImport.num_err_values+" unexpected values in "+
            //                FlexImport.num_err_columns+" columns. "+
            //                "<input type=button value=\"Correct the data\" onclick=\"FlexImport.createColumnSelectors();\"></p>";
        }else if ( FlexImport.num_invalid_records > 0){
//            e.innerHTML = "<p><b>Invalid records are marked in red. If no specific message is shown, the most likely cause is that the data contains no value for a required field.</b></p>";
        }else{
            e.innerHTML += "<p><b>Records prepared for import:</b></p>";
            $("#step3-info").html("Records appear OK. Click 'Save records' to update database");
            $("#btn_correct").hide();
            $("#btn_save").show();
        }
    
    },
        
	startSaveRecords:function(){

       if (! confirm("This will attempt to save all the displayed records to " +
        (HAPI.database ? "the \""+HAPI.database+"\" Heurist database" : "Heurist") + ".\nAre you sure you want to continue?")){
            return;  
        } 

		var e = $("#records-div-info")[0];
		e.innerHTML = "";
		FlexImport.showProgress();

		setTimeout(function() {
			FlexImport.Saver.saveRecords();
		},200);
	},


	createRecord: function (recType, importTag, fields) {
		var hRec,err;

		var logError = function (key, msg) {
			if (!err) {
				err = {};
			}
			if (!err[key]) {
				err[key] = msg;
			}else{
				err[key] += "\n" + msg;
			}
		}
        
        
		if (top.HEURIST.magicNumbers && top.HEURIST.magicNumbers['RT_RELATION'] && recType.getID() == top.HEURIST.magicNumbers['RT_RELATION']) {//MAGIC NUMBER
			hRec = new HRelationship();
		}
		else {
			hRec = new HRecord();
		}
		hRec.setRecordType(recType);
		hRec.addToPersonalised();
		hRec.addTag(importTag);

        var geoLat = null,
            geoLng = null,
            geoDt;

		for (var j = 0; j < fields.length; ++j) {

			// skip unassigned columns
			if (! FlexImport.cols[j]  ||  FlexImport.cols[j] == "") {
				continue;
			}
            
			// get detail value
			val = fields[j];
			if (! val) {
				if (FlexImport.reqDetailsMap[FlexImport.cols[j]]) {
					var name = HDetailManager.getDetailNameForRecordType(FlexImport.recType,HDetailManager.getDetailTypeById(FlexImport.cols[j]));
					logError( j, "Null value found for required field : " + name + "(" + FlexImport.cols[j] + ")");
					logError("invalidRecord", " Missing value for " + name +".");
				}
				continue;
			}
			try {
				// set records detail value
                if (FlexImport.cols[j] == "record id") {
                    hRec.setID(val);
                }else if (FlexImport.cols[j] == "url") {
					hRec.setURL(val);
				} else if (FlexImport.cols[j] == "scratchpad") {
					hRec.setNotes(val);
				} else if (FlexImport.cols[j] == "tags") {
					var vals = val.split(",");
					for (var v = 0; v < vals.length; ++v) {
						HTagManager.addTag(vals[v]);	// ensure the tag exists
						hRec.addTag(vals[v]);
					}
				} else if (FlexImport.cols[j] == "wgTags") {
					var vals = val.split(",");
					for (var v = 0; v < vals.length; ++v) {
						if (FlexImport.workgroupTags[vals[v]]) {
							hRec.addWgTag(FlexImport.workgroupTags[vals[v]])
						}
					}
				} else if (FlexImport.cols[j]) { // detail is generic so prepare to addDetail
					detailType = HDetailManager.getDetailTypeById(FlexImport.cols[j]);
					var vals;
					if (HDetailManager.getDetailRepeatable(recType, detailType)) {
						vals = val.split( detailType.getVariety() == HVariety.GEOGRAPHIC ? "|" : this.valSep ); //FIXME multi-valued fields from CSV format might have | as delimiter
					} else {
						vals = [ val ];
					}

					if (detailType.getVariety() == HVariety.FILE) {
						 //create HFile object from given value
						 //@todo!!!!
						 this.initFile = this.createRecord;
						 for (var v = 0; v < vals.length; ++v) {
						 		//d.id, d.origName, d.fileSize, d.ext, d.URL, d.thumbURL, d.description
						 		vals[v] = new HFile(this, 0, '', 0, null, vals[v], null, '');
						 }
						 delete this.initFile;


					} else if (detailType.getVariety() == HVariety.GEOGRAPHIC) {
                        if(FlexImport.subTypes[j]=="lat"){
                            geoLat = vals;
                            geoDt = detailType; 
                            continue;
                        }else if(FlexImport.subTypes[j]=="lng"){
                            geoLng = vals;
                            continue;
                        }else{
						    for (var v = 0; v < vals.length; ++v) {
						    	vals[v] = new HGeographicValue(HGeographicType.abbreviationForType(FlexImport.subTypes[j]), vals[v]);
						    }
                        }
					} else if (detailType.getVariety() == HVariety.REFERENCE) {
						if (vals.length == 1 && vals[0].indexOf(",",0) != -1) {	// This is to capture the case of the user not understanding verticle bar as we train them to use comma ids:345,456,567
							vals = vals[0].split(",");
						}
						var l = vals.length;
						for (var v = 0; v < l; ++v) {
							var temp = vals[v];
							if(parseInt(vals[v])<1){// invalid recID
								logError(j,"<b>"+temp + "</b><br/>&nbsp;<br/>Record ID invalid");
                                vals.splice(v,1);    // remove the val in order to ignore it
                                v--;
                                l = vals.length;
							}else{
								vals[v] = HeuristScholarDB.getRecord(parseInt(vals[v])); //get record from cache
								if (!vals[v]) { //there was an error loading the referenced record so mark it
									logError(j,"<b>"+temp + "</b><br/>&nbsp;<br/>Record ID invalid or not found");
									vals.splice(v,1);	// remove the val in order to ignore it
									v--;
									l = vals.length;
								}else if (detailType.getConstrainedRecordTypeIDs &&	//check to make sure the record matches constraints
											detailType.getConstrainedRecordTypeIDs() &&
											detailType.getConstrainedRecordTypeIDs().length > 0 &&
											(detailType.getConstrainedRecordTypeIDs()).indexOf("" + vals[v].getRecordType().getID()) == -1){
									hRecTypes = detailType.getConstrainedRecordTypes();
									typeList = "";
									for ( index = 0; index < hRecTypes.length; index++) {
										typeList += hRecTypes[index].getName();
									}
									logError(j,"<b>" + temp + "</b><br/>&nbsp;<br/>Record ID not of appropriate type: " + typeList );
								}
							}
						}
					}
					// FIXME now we should also validate against constraints table for enum values.
					if (vals) {
                        //replace all details
						hRec.setDetails(detailType, vals);
					}
				}   
			}catch(e) {
				logError(j,e);
			}
		}
        
        if(geoLat && geoLng){
            var vals = [];
            var lat, lng;
            for (var v = 0; v < geoLat.length; ++v) {
                
                lat = parseFloat(geoLat[v]);
                lng = (v<geoLng.length)?parseFloat(geoLng[v]):NaN;
                if(!(isNaN(lat) || isNaN(lng))){
                    vals.push( new HGeographicValue("p", "POINT("+lng+" "+lat+")") );    
                }
                
            }
            if (vals.length>0) {
                hRec.setDetails(geoDt, vals);
            }
        }
        
        
         
		if ((!err || !err.invalidRecord) && !hRec.isValid()) { // if record is invalid and hasn't been flagged yet, must be a missing req detail
			logError("invalidRecord", " Missing required field(s).");
		}
		return {record : hRec, error : err};
	},

	outputCSV: function () {
		var $textarea, l, i, k, j, line, recordIDColumn, recordID, dtID, columnHeader;

		var csvField = function (s) {
			if (s.match(/"/)) {
				return '"' + s.replace(/"/g, '""') + '"';
			}
			else if (s.match(/,/)) {
				return '"' + s + '"';
			}
			else return s;
		};

		$textarea = $("<textarea id='csv-textarea'>");
		$("<div>").append($textarea).appendTo("body");

		line = [];
		for (i = 0; i < FlexImport.columnCount; ++i) {
			columnHeader = FlexImport.hasHeaderRow ? FlexImport.fields[0][i]:"";
			if (FlexImport.cols[i]) {
				if (recordIDColumn == undefined) {// insert the rec id just before the first import data column
					recordIDColumn = i;
					line.push(FlexImport.recType.getName() + " Record ID");
				}
				dtID = parseInt(FlexImport.cols[i]);
				if (dtID > 0) {
					line.push(HDetailManager.getDetailTypeById(dtID).getName());
				}
				else {
					line.push(columnHeader);
				}
			}
			else { // column not imported let the user know
				line.push((columnHeader? columnHeader + " ":"") + "(not imported)");
			}
		}
		$textarea.append(line.join(", ") + "\n");

		FlexImport.gotoStep(4);

        var rec, cntError = 0;
		l = FlexImport.fields.length;
		for (i = FlexImport.hasHeaderRow ? 1:0; i < l; ++i) {

			line = [];

			rec = FlexImport.lineRecordMap[i];
			if(rec.hasError()){
				cntError++;
				line.push(rec.getError());  //'<span style="color:red">'++'</span>'
			}else{
				recordID = rec.getID();
			}

			k = FlexImport.fields[i].length;
			for (j = 0; j < k; ++j) {
				if (j === recordIDColumn) {
					line.push(recordID);
				}
				line.push(csvField(FlexImport.fields[i][j]));
			}
			$textarea.append(line.join() + "\n");
		}

		if(cntError>0){

			$("#result-message").html('IMPORT UNSUCCESSFUL' +
	        '<p/>'+cntError+' record'+((cntError>1)?'s are':' is')+' not imported. Refer to error messages on the begining of record lines');
		}else{

			$("#result-message").html('IMPORT SUCCESSFUL' +
	        '<p/>Record IDs for the imported columns have been added as column ' + (recordIDColumn + 1) +
			'<br/>Copy and save these data immediately if there are additional fields to import, to allow use of the record IDs as record pointers'+
	        '<p style="color:#ff0000;">WARNING: you will lose the record IDs as soon as you click STEP1 (START OVER), so save the data below to a file first<br/>&nbsp;</p>');
		}


	}
	};

})();




FlexImport.Loader = (function () {
	// helper function to load all records for a given query
	// this is necessary because of the page limit set in HAPI
	function _loadAllRecords (query, options, loader) {
		var loadedrecords = [];
		var baseSearch = new HSearch(query, options);
		var bulkLoader = new HLoader(
			function(s, r) {	// onload
				loadedrecords.push.apply(loadedrecords, r);
				if (r.length < 100) {
					// we've loaded all the records: invoke the original loader's onload
					$("#results").html('<b>Loaded ' + loadedrecords.length + ' records </b>');
					loader.onload(baseSearch, r);
				}
				else { // more records to retrieve
					$("#results").html('<b>Loaded ' + loadedrecords.length + ' records so far ...</b>');

					//  do a search with an offset specified for retrieving the next page of records
					var search = new HSearch(query + " offset:"+loadedrecords.length, options);
					HeuristScholarDB.loadRecords(search, bulkLoader);
				}
			},
			loader.onerror
		);
		HeuristScholarDB.loadRecords(baseSearch, bulkLoader);
	}

	return {
		//loads records from server using a helper function to ensure that we get all the records
		loadRecords: function (myquery) {
			var loader = new HLoader(
				function(s, r) { // onload
					if(FlexImport.fields){
						$("#results").html('<b>Loaded ' + FlexImport.fields.length + ' records </b>');
					}else{
						$("#results").html('<b>No records loaded</b>');
					}
					FlexImport.createRecords();
				},
				function(s,e) { // onerror
					alert("failed to load: " + e);
				}
			);
			_loadAllRecords(myquery, null, loader);
		}
	};
})();


FlexImport.Saver = (function () {

	//loads chunkSize records into the savRecord array
	function _getChunk() {
		var i;
		FlexImport.recEnd = FlexImport.recStart + FlexImport.constChunkSize;
		if (FlexImport.recEnd > FlexImport.records.length) {
			FlexImport.recEnd = FlexImport.records.length;
		}

		FlexImport.SavRecordChunk = [];

		if (FlexImport.recEnd > FlexImport.recStart) {
			//get a Chunk of records to save
			for (i=0; i < (FlexImport.recEnd-FlexImport.recStart); i++) {
				FlexImport.SavRecordChunk[i] = FlexImport.records[i+FlexImport.recStart];
			}
			_saveChunk();
		} else {
			// finished
			FlexImport.outputCSV();
		}
	}

	//saves the records in savRecords array
	function _saveChunk() {

			var saver = new HSaver(
			function(r) {
				//$("#rec-type-select-div").empty();
				//$("#rec-type-select-div").empty();
				$("#records-div").html("Saved <b>" + FlexImport.recEnd+ "</b> records");
				// r.length gives the number of records in the chunk being saved
				FlexImport.recStart = FlexImport.recEnd;
				setTimeout(function() {
					_getChunk();
				},0);
			},
			function(r,e) {
				alert("record save failed: " + e);
			});
		HeuristScholarDB.saveRecords(FlexImport.SavRecordChunk, saver);

	}

	return {
		saveRecords: function () {
			_getChunk();
		}
	};
})();
