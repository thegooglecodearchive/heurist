var g_version = "1";


function DetailTypeManager() {

	var _className = "DetailTypeManager",
	_ver = g_version,				//version number for data representation
	showTimer,
	hideTimer;
	var db = (top.HEURIST.parameters.db? top.HEURIST.parameters.db :
				(top.HEURIST.database.name?top.HEURIST.database.name:''));

	//keep tablea and source - to avoid repeat load and for filtering
	var arrTables = [],
	arrDataSources = [];

	var currentTipId;
	var needHideTip = true;

	var _groups = [],  //for dropdown list
	_deleted = [], //keep removed types to exclude on filtering
	_cloneHEU = null; //keep Heurist for rollback in case user cancels group/visibility editing

	//object to send changes (visibility and group belong) for update on server
	var _oDetailType = {detailtype:{
			colNames:{common:['dty_ShowInLists','dty_DetailTypeGroupID']},
			defs: {}
	}};

	var _lblNotice, //label that notify that some thing was changed - visibility of group
	_btnSave,   //button to send changes to server side
	_updatesCnt = 0; //number of affected field types

	var tabView = new YAHOO.widget.TabView();

	//public members
	var that = {
		name : "App",
		init: function(){
			_init();
		},
		doGroupSave: function(){ _doGroupSave(); },
		doGroupDelete: function(){ _doGroupDelete(); },
		doGroupCancel: function(){ _doGroupCancel(); },
		hasChanges: function(){ return  (_updatesCnt>0); }
	}

	return that;

	//
	//
	//
	function _init()
	{
		var ind = 0;
		//
		// init tabview with names of group
		for (var dtg_ID in top.HEURIST.detailTypes.groups) {

			var grpName = top.HEURIST.detailTypes.groups[dtg_ID].name;
			var grpDescription = top.HEURIST.detailTypes.groups[dtg_ID].description;
			_addNewTab(ind, dtg_ID, grpName, grpDescription);
			ind++;
		} //for

		tabView.addTab(new YAHOO.widget.Tab({
			id: "newGroup",
			label: "<label title='Create new group, edit or delete the existing group' style='font-style:italic'>edit</label>",
			content:
			('<div id="formGroupEditor" style="width:600px; padding:40px">'+
			'<h3 style="width:100%;text-align:center">Create a new detail group or edit the existing one</h3><br/>'+
			'<div class="dtyField"><label class="dtyLabel">Group:</label><select id="edGroupId" onchange="onGroupChange()"></select></div>'+
			'<div class="dtyField"><label class="dtyLabel">Name:</label><input id="edName" style="width:300px"/></div>'+
			'<div class="dtyField"><label class="dtyLabel">Descrption:</label><input id="edDescription" style="width:300px"/></div>'+
			'<div style="text-align: center; margin:auto; padding-top:5;">'+
			'<input id="btnGrpSave" style="display:inline-block" type="button" value="Save" onclick="{DetailTypeManager.doGroupSave()}" />'+
			'<input id="btnGrpCancel" type="button" value="Cancel" onclick="{DetailTypeManager.doGroupCancel()}" />'+
			'</div><hr width="50%"/>'+
			'<div style="text-align: center; margin:auto; padding-top:5;">'+
			'Select group above for <input id="btnGrpDelete" type="button" value="Deletion" onclick="{DetailTypeManager.doGroupDelete()}" />'+
			'</div>'+
			'</div>')
		}));

		tabView.appendTo("modelTabs");

		var bookmarkedTabViewState = YAHOO.util.History.getBookmarkedState("tabview");
		var initialTabViewState = bookmarkedTabViewState || "tab0";

		YAHOO.util.History.register("tabview", initialTabViewState, function (state) {
			tabView.set("activeIndex", state.substr(3));  //restre the index from history
		});

		YAHOO.util.History.onReady(function () {
			var currentState;

			initTabView();

			currentState = YAHOO.util.History.getCurrentState("tabview");

			if(currentState && currentState.length>3) {
				tabView.set("activeIndex", currentState.substr(3));  //restore active tab from history
			}
		});

		try {
			YAHOO.util.History.initialize("yui-history-field", "yui-history-iframe");
		} catch (e) {
			initTabView();
		}


	}//end _init

	//
	// adds new tab and into 3 spec arrays
	//
	function _addNewTab(ind, grpID, grpName, grpDescription)
	{
		if(grpDescription==undefined || grpDescription==""){
			grpDescription = "Describe this group!";
		}

		_groups.push({value:grpID, text:grpName});

		tabView.addTab(new YAHOO.widget.Tab({
			id: grpID,
			label: "<label title='"+grpDescription+"'>"+grpName+"</label>",
			content:
			('<div>'+                   //for="filter"
			'<div style="display:inline-block;"><label>Filter by name:</label>'+
			'<input type="text" id="filter'+grpID+'" value="">'+
			'&nbsp;&nbsp;<input type="checkbox" id="filter'+grpID+'vis" value="1" style="padding-top:5px;">&nbsp;Show active only</div>'+
			'<div style="float:right; text-align:right">'+
			'<input type="button" id="btnAdd'+grpID+'" value="Add Field Type"/>'+
			'</div></div>'+
			'<div id="tabContainer'+grpID+'"></div></div>')

		}), ind);

		arrTables.push(null);
		arrDataSources.push(null);
	}

	//
	// on changing of tab - create and fill datatable for particular group
	//
	function _handleTabChange (e) {

		hideTimer = null;
		needHideTip = true;
		_hideToolTip();

		var id = e.newValue.get("id");
		if(id=="newGroup"){
			//fill combobox on edit group form
			var sel = YAHOO.util.Dom.get('edGroupId');

			//celear selection list
			while (sel.length>0){
				sel.remove(0);
			}
			var option = document.createElement("option");
			option.text = "new group";
			option.value = "-1";
			try {
				// for IE earlier than version 8
				sel.add(option, sel.options[null]);
			}catch (e){
				sel.add(option,null);
			}


			for (var i in _groups)
			if(i!=undefined){

				var option = document.createElement("option");
				option.text = _groups[i].text;
				option.value = _groups[i].value;
				try {
					// for IE earlier than version 8
					sel.add(option, sel.options[null]);
				}catch (e){
					sel.add(option,null);
				}
			} // for

			YAHOO.util.Dom.get('edName').value = "";
			YAHOO.util.Dom.get('edDescription').value = "";

		}else if (e.newValue!=e.prevValue)
		{
			initTabContent(e.newValue);
		}
	}// end _handleTabChange

	//
	//add listener for tabview
	//
	function initTabView () {
		tabView.addListener("activeTabChange", _handleTabChange);

		//init the content for the first tab (table and buttons)
		tabView.set("activeIndex", 0);
	}

	// =============================================== START DATATABLE INIT
	//
	// create the content of tab: buttons and datatable
	//
	function initTabContent(tab){

		var dtg_ID = tab.get('id');
		//alert('init>>>>'+dtg_ID);

		//does not work var dt = YAHOO.util.Dom.get("datatable"+dtg_ID);

		var currentTabIndex = tabView.get('activeIndex');
		var dt = arrTables[currentTabIndex];

		if(dt==undefined || dt==null){

			var arr = [];

			//create datatable and fill it valurs of particular group
			for (var dty_ID in top.HEURIST.detailTypes.typedefs) {
				if(dty_ID!="commomFieldNames")
				{
					var td = top.HEURIST.detailTypes.typedefs[dty_ID];
					var deftype = td.commonFields;
					//only for this group and  visible in UI
					if(deftype[7]==dtg_ID){
						var aUsage = top.HEURIST.detailTypes.rectypeUsage[dty_ID];
						var iusage = aUsage == undefined ? 0 : aUsage.length;
						// add order in group, name, help, type and status,
						// doc will be hidden (for pop-up)
						// last 3 columns for actions
						arr.push([dty_ID, (deftype[5]==1),
						deftype[3],deftype[0],deftype[4],deftype[2],deftype[6],deftype[1],
						dtg_ID, iusage]);
					}
				}
			}
			//alert(' len:'+arr.length);

			var myDataSource = new YAHOO.util.LocalDataSource(arr,{
				responseType : YAHOO.util.DataSource.TYPE_JSARRAY,
				responseSchema : {
					fields: [ "id", "vis", "order", "name", "help",  "type", "status",
					"description", "grp_id", "usage"]
				},
				doBeforeCallback : function (req,raw,res,cb) {
					// This is the filter function
					var data  = res.results || [],
					filtered = [],
					i,l;

					if (req) {

						var fvals = req.split("|");

						var sByName   = fvals[0].toLowerCase();
						var iByVisibility = fvals[1];

						// when we change the table, the datasource is not changed
						// thus we need an additional filter to filter out the deleted rows
						// and rows that were moved to another groups
						var currentTabIndex = tabView.get('activeIndex');
						var dtg_ID = tabView.getTab(currentTabIndex).get('id');

						for (i = 0, l = data.length; i < l; ++i) {

							// when we change the table, the datasource is not changed
							//thus we need to update visibility manually
							var dty_ID = data[i].id;
							var df = _oDetailType.detailtype.defs[dty_ID];
							if(df!=undefined){
								data[i].vis = df.common[0];
								data[i].grp_id = df.common[1];
							}

							if ((data[i].name.toLowerCase().indexOf(sByName)>-1)
							&& (dtg_ID == data[i].grp_id)
							&& (_deleted.indexOf(dty_ID)<0)
							&& (iByVisibility==0 || data[i].vis==iByVisibility))
							{
								filtered.push(data[i]);
							}
						}
						res.results = filtered;
					}

					return res;
				}
			});

			/*var formatterDelete = function(elLiner, oRecord, oColumn, oData) {
			//if(oRecord.getData("field3") > 100) { YAHOO.util.Dom.replaceClass(elLiner.parentNode, "down", "up");
			elLiner.innerHTML = ' <img src="delete_icon.png">';
			};
			// Add the custom formatter to the shortcuts
			YAHOO.widget.DataTable.Formatter.formatterDelete = formatterDelete;*/

			var myColumnDefs = [
			{ key: "id", label: "<u>Code</u>", sortable:true, width:20, className:'left' },
			{ key: "vis", label: "Vis", sortable:false, width:20, formatter:YAHOO.widget.DataTable.formatCheckbox, className:'center' },
			{ key: "order", hidden:true },
			{ key: "name", label: "<u>Name</u>", sortable:true, className:'bold_column', width:200,
				formatter: function(elLiner, oRecord, oColumn, oData) {
					var str = oRecord.getData("name");
					var tit = "";
					if(str.length>30) {
						tit = str;
						str = str.substr(0,30)+"&#8230";
					}
					elLiner.innerHTML = '<label title="'+tit+'">'+str+'</label>';
			}},
			{ key: "help", label: "Description", sortable:false, width:200,
				formatter: function(elLiner, oRecord, oColumn, oData) {
					var str = oRecord.getData("description");
					var tit = "";
					if(str == null){
						str = "";
					}else if (str.length>30) {
						tit = str;
						str = str.substr(0,30)+"&#8230";
					}
					elLiner.innerHTML = '<label title="'+tit+'">'+str+'</label>';
			}},
			{ key: "type", label: "<u>Data Type</u>", sortable:true },
			{ key: "status", label: "<u>Status</u>", sortable:true },
			{ key: "description",   hidden:true},
			{ key: "grp_id", label: "Group", sortable:false, width:90,
				formatter:YAHOO.widget.DataTable.formatDropdown, dropdownOptions:_groups},
			{ key: null, label: "Edit", sortable:false, width:20, formatter: function(elLiner, oRecord, oColumn, oData){
					elLiner.innerHTML = '<a href="#edit"><img src="../../common/images/edit_icon.png" width="16" height="16" border="0" title="Edit" /><\/a>'} },
			{ key: null, label: "Del", sortable:false, formatter: function(elLiner, oRecord, oColumn, oData){
					elLiner.innerHTML = '<a href="#delete"><img src="../../common/images/delete_icon.png" width="16" height="16" border="0" title="Delete" /><\/a>'} },
			//{ key: "info", label: "Info", sortable:false, formatter: function(elLiner, oRecord, oColumn, oData){
			//	elLiner.innerHTML = '<a href="#info"><img src="../../common/images/info_icon.png" width="16" height="16" border="0" title="Info" /><\/a>'} },
			{ key: "usage", label: "<u>Usage</u>", sortable:true, width:20 }
			];


			var myConfigs = {
				//selectionMode: "singlecell",
				paginator : new YAHOO.widget.Paginator({
					rowsPerPage: 100, // REQUIRED
					totalRecords: arr.length, // OPTIONAL

					// use an existing container element
					//containers: 'dt_pagination',

					// use a custom layout for pagination controls
					template: "{PageLinks} Show {RowsPerPageDropdown} per page",

					// show all links
					pageLinks: YAHOO.widget.Paginator.VALUE_UNLIMITED,

					// use these in the rows-per-page dropdown
					rowsPerPageOptions: [25, 50, 100]

				})
			};

			dt = new YAHOO.widget.DataTable('tabContainer'+dtg_ID, myColumnDefs, myDataSource, myConfigs);

			//dt.subscribe("cellClickEvent", this.singleCellSelectDataTable.onEventSelectCell);

			//click on action images
			dt.subscribe('linkClickEvent', function(oArgs){
				YAHOO.util.Event.stopEvent(oArgs.event);

				var dt = this;
				var elLink = oArgs.target;
				var oRecord = dt.getRecord(elLink);
				var dty_ID = oRecord.getData("id");

				//                 alert("Action "+elLink.hash+" for:"+dty_ID);
				if(elLink.hash == "#edit"){

					_onAddEditFieldType(dty_ID, 0);

				}else if(elLink.hash == "#delete"){

					var iUsage = oRecord.getData('usage');
					if(iUsage<1){
						if(_needToSaveFirst()) return;

						var r=confirm("Delete field type#"+dty_ID+" '"+oRecord.getData('name')+"?");
						if (r==true) {

							function _updateAfterDelete(context) {

								if(context.error == undefined){
									dt.deleteRow(oRecord.getId(), -1);
									_deleted.push( dty_ID );
									alert("Field type #"+dty_ID+" was deleted");
									top.HEURIST.detailTypes = context.detailTypes;
									_cloneHEU = null;
								} else {
									// if error is property of context it will be shown by getJsonData
									//alert("Deletion failed. "+context.error);
								}
							}

							var baseurl = top.HEURIST.baseURL + "admin/structure/saveStructure.php";
							var callback = _updateAfterDelete;
							var params = "method=deleteDT&db="+db+"&dtyID=" + dty_ID;
							top.HEURIST.util.getJsonData(baseurl, callback, params);

						}
					}else{
						alert("Impossible to delele field type in usage");
					}
				}

			});


			dt.subscribe('dropdownChangeEvent', function(oArgs){
				var elDropdown = oArgs.target;
				var record = this.getRecord(elDropdown);
				var column = this.getColumn(elDropdown);
				var newValue = elDropdown.options[elDropdown.selectedIndex].value;
				var oldValue = record.getData(column.key);
				var recordIndex = this.getRecordIndex(record);
				var recordKey = record.getData('recordKey');
				if(newValue!=oldValue){
					//this.deleteRow(recordIndex);
					var data = record.getData();
					data['grp_id'] = newValue;

					///var newTabIndex = elDropdown.selectedIndex; //getTabIndexByGroup(newValue);

					//remove destination table
					_removeTable(newValue, false);

					/*
					@ todo
					// show flashed message
					needHideTip = true;
					var my_tooltip = $("#toolTip2");
					my_tooltip.mouseover(null);
					my_tooltip.mouseout(null);

					var xy = [$(window).width()/2 - 100, $(window).height()/2 - 50 + $(window).scrollTop()];
					_showToolTipAt(my_tooltip, xy);
					my_tooltip.html("<b>AAAAAAAAA</b>");
					hideTimer = null;
					hideTimer = window.setTimeout(_hideToolTip, 4000);
					*/

					//remove from this table and refresh another one
					window.setTimeout(function() {
						dt.deleteRow(record.getId(), -1);
					}, 100);

					//keep the track of changes in special object
					_updateDetailType(record);
				}
			});

			//subscribe on checkbox event (visibility)
			dt.subscribe("checkboxClickEvent", function(oArgs) {
				var elCheckbox = oArgs.target;
				var oRecord = dt.getRecord(elCheckbox);
				var data = oRecord.getData();
				data['vis'] = elCheckbox.checked;//?1:0;

				//var recindex = dt.getRecordIndex(oRecord);
				//dt.updateRow(recindex, data);

				//keep the track of changes in special array
				_updateDetailType(oRecord);
			});

			//
			// keep the changes in object that will be send to server
			//
			function _updateDetailType(oRecord)
			{
				var dty_ID = oRecord.getData('id');
				var newvals = [(oRecord.getData('vis')?1:0), oRecord.getData('grp_id')];

				//keep copy
				if(_cloneHEU==null) _cloneHEU = cloneObj(top.HEURIST.detailTypes);
				//update HEURIST
				var td = top.HEURIST.detailTypes.typedefs[dty_ID];
				var deftype = td.commonFields;
				deftype[5] = newvals[0]; //visibility
				deftype[7] = newvals[1]; //group

				//update keep object
				var dt_def = _oDetailType.detailtype.defs[dty_ID];
				if(dt_def==undefined){
					_oDetailType.detailtype.defs[dty_ID] = {common:newvals};
					_updatesCnt++;
				}else{
					_oDetailType.detailtype.defs[dty_ID].common = newvals;
				}

				if(_lblNotice==null){
					_lblNotice = YAHOO.util.Dom.get("lblNoticeAboutChanges");
					_btnSave   = YAHOO.util.Dom.get("btnSave");
					_btnSave.onclick = _updateDetailTypeOnServer;
				}

				_lblNotice.innerHTML = 'You have changed <b>'+_updatesCnt+'</b> field type'+((_updatesCnt>1)?'s':'');
				_btnSave.style.display = 'block';
			}

			//mouse over help colums shows the datailed description
			dt.on('cellMouseoverEvent', function (oArgs) {
				clearHideTimer();

				var forceHideTip = true;
				var textTip = null;
				var target = oArgs.target;
				var column = this.getColumn(target);

				/*if (column!=null && column.key == 'help') {
				var record = this.getRecord(target);
				var description = record.getData('description') || 'no further description';
				var xy = [parseInt(oArgs.event.clientX,10) + 10 ,parseInt(oArgs.event.clientY,10) - 20 ];
				var textTip = '<p>'+description+'</p>';

				}else*/
				if(column!=null && column.key == 'usage') {
					var record = this.getRecord(target);
					var dty_ID = description = record.getData('id');

					if(currentTipId!=dty_ID){
						currentTipId=dty_ID;

						var xy = [parseInt(oArgs.event.clientX,10), parseInt(oArgs.event.clientY,10) - 20 ];

						//find all records that reference this type
						var aUsage = top.HEURIST.detailTypes.rectypeUsage[dty_ID];
						if(aUsage != undefined){
							var textTip = '<div style="padding-left:20px;padding-top:4px">Used in the following record types</div><ul>';
							for (var k in aUsage) {
								textTip = textTip + "<li><a href='editRecStructure.html?db="+db+"&rty_ID="+aUsage[k]+"'>"+top.HEURIST.rectypes.names[aUsage[k]]+"</a></li>";
							}
							textTip = textTip + "</ul></p>";
						}
					}else{
						forceHideTip = false;
					}
				}

				if(textTip!=null){

					needHideTip = true;

					var my_tooltip = $("#toolTip2");

					my_tooltip.mouseover(clearHideTimer2);
					my_tooltip.mouseout(hideToolTip2);

					_showToolTipAt(my_tooltip, xy);

					//lft = my_tooltip.css('left');
					my_tooltip.html(textTip);
					hideTimer = window.setTimeout(_hideToolTip, 5000);
				}
				else if(forceHideTip)
				{
					_hideToolTip();
				}
			});
			dt.on('cellMouseoutEvent', function (oArgs) {
				hideTimer = window.setTimeout(_hideToolTip, 2000);
			});

			function hideToolTip2(){
				needHideTip = true;
			}
			function clearHideTimer2(){
				needHideTip = false;
				clearHideTimer();
			}

			arrTables[currentTabIndex] = dt;
			arrDataSources[currentTabIndex] = myDataSource;


			var filter = YAHOO.util.Dom.get('filter'+dtg_ID);
			filter.onkeyup = function (e) {
				clearTimeout(filterTimeout);
				setTimeout(updateFilter,600);  };

			var filtervis = YAHOO.util.Dom.get('filter'+dtg_ID+'vis');
			filtervis.onchange = function (e) {
				clearTimeout(filterTimeout);
				updateFilter();  };

			var btnAdd = YAHOO.util.Dom.get('btnAdd'+dtg_ID);
			btnAdd.onclick = function (e) {
				var currentTabIndex = tabView.get('activeIndex');
				var grpID = tabView.getTab(currentTabIndex).get('id');
				_onAddEditFieldType(0, grpID);
			};


			/*
			YAHOO.util.Event.on('filter','onkeyup',function (e) {
			clearTimeout(filterTimeout);
			setTimeout(updateFilter,600);
			});    */


		} //if(dt==undefined || dt==null)
	}//initTabContent =============================================== END DATATABLE INIT

	//
	//
	//
	function _removeTable(grpID, needRefresh){

		if(grpID!=undefined && grpID>0)
		{
			var tabIndex = _getIndexByGroupId(grpID);

			var ndt = arrTables[tabIndex];
			if(ndt!=null){

				//find parent tab
				var tab = YAHOO.util.Dom.get('tabContainer'+grpID);
				for (var i = 0; i < tab.children.length; i++) {
					tab.removeChild(tab.childNodes[0]);
				}
				// need to refill the destionation table,
				// otherwise datasource is not updated
				arrTables[tabIndex] = null; //.addRow(record.getData(), 0);

				var currIndex = tabView.get('activeIndex');
				if(tabIndex == currIndex && needRefresh)
				{
					initTabContent(tabView.getTab(tabIndex));
				}

			}
		}
	}


	//  SAVE BUNCH OF TYPES =============================================================
	//
	// send updates to server (group belong and visibility)
	//
	function _updateDetailTypeOnServer(event) {
		var str = YAHOO.lang.JSON.stringify(_oDetailType);
		//alert("Stringified changes: " + str);

		if(str != null) {
			//_updateResult(""); //debug
			//return;//debug

			var baseurl = top.HEURIST.baseURL + "admin/structure/saveStructure.php";
			var callback = _updateResult;
			var params = "method=saveDT&db="+db+"&data=" + str;
			top.HEURIST.util.getJsonData(baseurl, callback, params);
		}
	}
	//
	// after saving a bunch of field types
	//
	function _updateResult(context) {
		if(!context) {
			alert("An error occurred trying to contact the database");
		}else{
			var error = false;
			var report = "";

			for(var ind in context.result)
			if(ind!=undefined){
				var item = context.result[ind];
				if(isNaN(item)){
					alert("An error occurred: " + item);
					error = true;
				}else{
					detailTypeID = Number(item);
					if(report!="") report = report + ",";
					report = report + detailTypeID;
				}
			}

			if(!error) {
				if(report.indexOf(",")>0){
					alert("Field types with IDs :"+report+ " were succesfully updated");
				}else{
					alert("Field type with ID " + report + " was succesfully  updated");
				}
				_clearGroupAndVisibilityChanges(false);
			}
			top.HEURIST.detailTypes = context.detailTypes;
			_cloneHEU = null;
		}
	}
	//
	// clear all changes with visibility and groups
	//
	function _clearGroupAndVisibilityChanges(withReload){

		_updatesCnt = 0;
		_oDetailType.detailtype.defs = {}; //clear keeptrack
		_btnSave.style.display = 'none';
		_lblNotice.innerHTML = '';

		if(_cloneHEU) top.HEURIST.detailTypes = cloneObj(_cloneHEU);
		_cloneHEU = null;

		if(withReload){
			for(var ind in arrTables)
			if(ind!=undefined){
				_removeTable( _getGroupByIndex(ind), true);
			}
		}
	}
	//
	// if user chnaged visibility of group, it is required to save changes before new edit
	// (otherwise HEURIST will be rewritten and we get the mess)
	//
	function _needToSaveFirst(){
		if(_updatesCnt>0){
			var r = confirm("You have made changes. Before new edit you have to save them. Save?");
			if (r) {
				_updateDetailTypeOnServer(null);
			}else{
				_clearGroupAndVisibilityChanges(true);
			}
			return true;
		}else{
			return false;
		}
	}

	//  SAVE BUNCH OF TYPES ======================================================== END

	//
	//
	function clearHideTimer(){
		if (hideTimer) {
			window.clearTimeout(hideTimer);
			hideTimer = 0;
		}
	}
	function _hideToolTip(){
		if(needHideTip){
			currentTipId = null;
			clearHideTimer();
			var my_tooltip = $("#toolTip2");
			my_tooltip.css( {
				left:"-9999px"
			});
		}
	}

	//
	// prevents out of border
	//
	function _showToolTipAt(_tooltip, xy){

		var border_top = $(window).scrollTop();
		var border_right = $(window).width();
		var border_height = $(window).height();
		var left_pos;
		var top_pos;
		var offset = 5;
		if(border_right - (offset *2) >= _tooltip.width() +  xy[0]) {
			left_pos = xy[0]+offset;
		} else {
			left_pos = border_right-_tooltip.width()-offset;
		}

		if((border_top + offset *2) >=  xy[1] - _tooltip.height()) {
			top_pos = border_top + offset + xy[1]; //
		} else {
			top_pos = border_top + xy[1] - _tooltip.height()-offset;
		}
		if(top_pos + _tooltip.height() > border_top+border_height){
			top_pos	= border_top + border_height - _tooltip.height()-5;
		}


		//var lft = _tooltip.css('left');
		_tooltip.css( {
			left:left_pos+'px', top:top_pos+'px'
		});
	}

	//
	// filtering by name
	// listenter is activated along with dataTable creation
	//
	var filterTimeout = null;
	function updateFilter() {
		// Reset timeout
		filterTimeout = null;

		var currentTabIndex = tabView.get('activeIndex');
		var dtable = arrTables[currentTabIndex];
		var dsource = arrDataSources[currentTabIndex];

		// Reset sort
		var state = dtable.getState();
		state.sortedBy = {key:'name', dir:YAHOO.widget.DataTable.CLASS_ASC};

		var grpID = _getGroupByIndex(currentTabIndex);

		var filterval = YAHOO.util.Dom.get('filter'+grpID).value;
		var filtervis = YAHOO.util.Dom.get('filter'+grpID+'vis').checked?1:0;

		// Get filtered data
		dsource.sendRequest(filterval+'|'+filtervis,{
			success : dtable.onDataReturnInitializeTable,
			failure : dtable.onDataReturnInitializeTable,
			scope   : dtable,
			argument : { pagination: { recordOffset: 0 } } // to jump to page 1
		});
	}

	//
	// listener of add button
	//
	function _onAddEditFieldType(dty_ID, dtg_ID){

		if(_needToSaveFirst()) return;

		var url = top.HEURIST.basePath + "admin/structure/editDetailType.html?db="+db;
		if(dty_ID>0){
			url = url + "&detailTypeID="+dty_ID; //existing
		}else{
			url = url + "&groupID="+dtg_ID; //new one
		}

		top.HEURIST.util.popupURL(top, url,
		{   "close-on-blur": false,
			"no-resize": false,
			height: 630,
			width: 680,
			callback: function(context) {
				if(context==null){
					// alert("Edition is cancelled");
				}else{

					//update id
					var dty_ID = Math.abs(Number(context.result[0]));

					//if user changes group in popup need update both  old and new group tabs
					var grpID_old = -1;
					if(Number(context.result[0])>0){
						grpID_old = top.HEURIST.detailTypes.typedefs[dty_ID].commonFields[7];
					}

					//refresh the local heurist
					top.HEURIST.detailTypes = context.detailTypes;
					_cloneHEU = null;

					//detect what group
					var grpID = top.HEURIST.detailTypes.typedefs[dty_ID].commonFields[7];

					_removeTable(grpID, true);
					if(grpID_old!=grpID)
					_removeTable(grpID_old, true);
					/*
					//is it current tab
					var ind = _getIndexByGroupId(grpID);
					var ind_old = _getIndexByGroupId(grpID_old);

					//refresh tables
					var tabIndex = tabView.get('activeIndex');

					var ndt = arrTables[ind];
					if(ndt!=null){
					arrTables[ind] = null;
					//if it is current tab force datatable refresh
					if(tabIndex == ind)
					{
					initTabContent(tabView.getTab(tabIndex));
					}
					}
					if(ind_old>=0){
					ndt = arrTables[ind_old];
					if(ndt!=null){
					arrTables[ind_old] = null;
					if (tabIndex == ind_old)
					{
					initTabContent(tabView.getTab(tabIndex));
					}
					}
					}*/

				}
			}
		});
	}

	//============================================ GROUPS
	//
	// managing goups
	//
	function _doGroupSave()
	{
		if(_needToSaveFirst()) return;

		var sel = YAHOO.util.Dom.get('edGroupId'),
		name = YAHOO.util.Dom.get('edName').value,
		description = YAHOO.util.Dom.get('edDescription').value,
		grpID = sel.options[sel.selectedIndex].value,
		grp; //object in HEURIST

		var orec = {dettypegroups:{
				colNames:['dtg_Name','dtg_Description'],
				defs: {}
		}};


		//define new or exisiting
		if(grpID<0) {
			grp = {name: name, description:description};
			orec.dettypegroups.defs[-1] = [];
			orec.dettypegroups.defs[-1].push({values:[name, description]});
		}else{
			//for existing - rename
			grp = top.HEURIST.detailTypes.groups[grpID];
			grp.name = name;
			grp.description = description;
			orec.dettypegroups.defs[grpID] = [name, description];
		}

		//top.HEURIST.detailTypes.groups[grpID] = grp;
		var str = YAHOO.lang.JSON.stringify(orec);

		//alert(str);

		if(str!=null){
			var baseurl = top.HEURIST.baseURL + "admin/structure/saveStructure.php";
			var callback = _updateOnSaveGroup;
			var params = "method=saveDTG&db="+db+"&data=" + str;

			top.HEURIST.util.getJsonData(baseurl, callback, params)
		}

		//make this tab active
		function _updateOnSaveGroup(context){
			//for new - add new tab
			if(context['0'].error!=undefined){
				alert(context['0'].error);
			}else{
				var ind;
				top.HEURIST.detailTypes = context['1'];
				_cloneHEU = null;

				if(grpID<0){
					grpID = context['0'].result;
					ind = _groups.length;
					_addNewTab(ind, grpID, name, description)
				}else{
					//update label
					var ind = _getIndexByGroupId(grpID);
					if(ind>=0){
						var tab = tabView.getTab(ind);
						var el = tab._getLabelEl();
						el.innerHTML = "<label title='"+description+"'>"+name+"</label>";
						_groups[ind].text = name;
					}
				}
				tabView.set("activeIndex", ind);
			}
		}
	}

	//
	//
	//
	function _doGroupDelete(){

		if(_needToSaveFirst()) return;

		var sel = YAHOO.util.Dom.get('edGroupId');
		var grpID = sel.options[sel.selectedIndex].value;

		if(grpID<0) return;

		var grp = top.HEURIST.detailTypes.groups[grpID]

		if(grp.types!=undefined)
		{
			alert("There are types that belong to this group. Impossible to delete such group");
		}else{
			var r=confirm("Confirm the deletion of group '"+grp.name+"'");
			if (r) {
				var ind;
				//
				function _updateAfterDeleteGroup(context) {
					if(context.error!=undefined){
						// alert(context.error);
					}else{
						//remove tab from tab view and select 0 index
						_groups.splice(ind, 1);
						arrTables.splice(ind, 1);
						arrDataSources.splice(ind, 1);

						tabView.removeTab(tabView.getTab(ind));
						tabView.set("activeIndex", 0);
						top.HEURIST.detailTypes = context.detailTypes;
						_cloneHEU = null;
					}
				}


				//1. find index of tab to be removed
				ind = _getIndexByGroupId(grpID);
				if(ind>=0){

					var baseurl = top.HEURIST.baseURL + "admin/structure/saveStructure.php";
					var callback = _updateAfterDeleteGroup;
					var params = "method=deleteDTG&db="+db+"&dtgID=" + grpID;
					top.HEURIST.util.getJsonData(baseurl, callback, params);
				}

			}
		}

	}
	//
	// just hide tab and back to previos one
	//
	function _doGroupCancel(){
		tabView.set("activeIndex", tabView.get('activeIndex'));
	}

	//
	//
	//
	function _getIndexByGroupId(grpID){
		var ind = -1;
		for (ind in _groups)
		if(ind>=0 && _groups[ind].value==grpID){
			return ind;
			break;
		}
		return ind;
	}
	//
	//
	//
	function _getGroupByIndex(ind){
		return _groups[ind].value;
	}


};

//
//general functions
//
function onGroupChange() {
	var sel = YAHOO.util.Dom.get('edGroupId'),
	edName = YAHOO.util.Dom.get('edName'),
	edDescription = YAHOO.util.Dom.get('edDescription'),
	grpID = sel.options[sel.selectedIndex].value;

	if(grpID<0){
		edName.value = "";
		edDescription.value = "";
	}else{
		edName.value = top.HEURIST.detailTypes.groups[grpID].name;
		edDescription.value = top.HEURIST.detailTypes.groups[grpID].description;
	}

}

//
// deep cloning of object
//
function cloneObj(o) {
	if(typeof(o) != "object") return o;

	if(o == null) return o;

	var newO = new Object();

	for(var i in o) newO[i] = cloneObj(o[i]);

	return newO;
}
