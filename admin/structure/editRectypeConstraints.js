/**
* editRectypeConstraints.js
* DetailTypeManager object for listing and searching of detail types
*
* @version 2012.0822
* @author: Artem Osmakov
*
* @copyright (C) 2005-2012 University of Sydney Digital Innovation Unit.
* @link: http://HeuristScholar.org
* @license http://www.gnu.org/licenses/gpl-3.0.txt
* @package Heurist academic knowledge management system
* @todo
**/
var g_version = "1";

var constraintManager;

//aliases
var Dom = YAHOO.util.Dom,
	Hul = top.HEURIST.util;

function ConstraintManager() {

	var _className = "ConstraintManager",
		_ver = g_version,				//version number for data representation
		_myDataTable,
		_myDataSource,
		_myTermTable,
		_myTermSource,
		_currentPair,
		_currentPairRecord;

	var db = (top.HEURIST.parameters.db? top.HEURIST.parameters.db :
				(top.HEURIST.database.name?top.HEURIST.database.name:''));

	//
	//
	//
	function _init()
	{

			var arr = [],
				rec_ID,
				target_rec_ID,
				term_ID,
				fi_trm_label = top.HEURIST.terms.fieldNamesToIndex.trm_Label;


			createRectypeSelector("selSrcRectypes", true);
			createRectypeSelector("selTrgRectypes", true);

			//fi = top.HEURIST.rectypes.names;

			//create datatable and fill it values of particular group
			for (rec_ID in top.HEURIST.rectypes.constraints) {

				if(!Hul.isnull(rec_ID))
				{
					for (target_rec_ID in top.HEURIST.rectypes.constraints[rec_ID].byTarget) {
						if(!Hul.isnull(target_rec_ID))
						{
							var terms = [],
								hasAny = false;
							
							for (term_ID in top.HEURIST.rectypes.constraints[rec_ID].byTarget[target_rec_ID] ) {
								if(Hul.isnull(term_ID)) continue;
								
								var notes = top.HEURIST.rectypes.constraints[rec_ID].byTarget[target_rec_ID][term_ID].notes;
								var limit = top.HEURIST.rectypes.constraints[rec_ID].byTarget[target_rec_ID][term_ID].limit;
								if(limit=='unlimited') limit=0;
								
								if(term_ID=='any'){
									
									hasAny = true;
									terms.push(['null',
												'any',
												limit,
												notes,
												false]);
									
								}else if(!isNaN(Number(term_ID))){
									terms.push([term_ID,
												top.HEURIST.terms.termsByDomainLookup.relation[term_ID][fi_trm_label],
												limit,
												notes,
												false]);
								}
							}//for

							var sname = (rec_ID=='any')?rec_ID: top.HEURIST.rectypes.names[rec_ID];
							if(rec_ID=='any')  { rec_ID = 0; }
							var tname = (target_rec_ID=='any')?target_rec_ID: top.HEURIST.rectypes.names[target_rec_ID];
							if(target_rec_ID=='any')  { target_rec_ID = 0; }

							arr.push([Number(rec_ID),
									sname,
									Number(target_rec_ID),
									tname,
									terms.length,
									terms,
									hasAny ]);
						}
					}
				}
			}

			_initTable(arr);
	}

	/**
	* Creates and (re)fill datatable
	*/
	function _initTable(arr)
	{

	//if datatable exists, only refill ==========================
				if(!Hul.isnull(_myDataTable)){

					// all stuff is already inited, change livedata in datasource only
					_myDataSource.liveData = arr;

					//refresh table
					_myDataSource.sendRequest("", {
								success : _myDataTable.onDataReturnInitializeTable,
								failure : _myDataTable.onDataReturnInitializeTable,
								scope   : _myDataTable,
								argument : { pagination: { recordOffset: 0 } } // to jump to page 1
					});

					return;
				}

	//create new datatable ==========================

								_myDataSource = new YAHOO.util.LocalDataSource(arr, {
									responseType : YAHOO.util.DataSource.TYPE_JSARRAY,
									responseSchema : {
										fields: ["src_id", "src_name", "trg_id", "trg_name", "count", "terms", "hasAny"]
									}
								});

								var myColumnDefs = [
			{ key: "src_id", label: "From", sortable:true, className:'right',resizeable:false, width:5},
			{ key: "src_name", label: " ", sortable:true},
			{ key: "trg_id", label: "To", sortable:true, className:'right',resizeable:false, width:5},
			{ key: "trg_name", label: " ", sortable:true},
			{ key: "count", label: "Count", sortable:true, className:'right',resizeable:false, width:5},
			{ key: null, label: "Edit", sortable:false, width:5,
				formatter: function(elLiner, oRecord, oColumn, oData) {
elLiner.innerHTML = '<a href="#edit_item"><img src="../../common/images/edit-pencil.png" width="16" height="16" border="0" title="Edit"><\/a>';}
			}
								];


		var myConfigs = {};

		_myDataTable = new YAHOO.widget.DataTable('tabContainer', myColumnDefs, _myDataSource, myConfigs);


		//click on action images
		_myDataTable.subscribe('linkClickEvent', function(oArgs){


				var dt = this;
				var elLink = oArgs.target;
				var oRecord = dt.getRecord(elLink);

				if(elLink.hash === "#edit_item") {
					YAHOO.util.Event.stopEvent(oArgs.event);
					_editConstraint(oRecord);

				}/*else if(elLink.hash === "#delete_item"){

					YAHOO.util.Event.stopEvent(oArgs.event);

						var value = confirm("Do you really want to delete this pair?"); // '"+oRecord.getData('fullname')+"'?");
						if(value) {

							function _updateAfterDelete(context) {

								if(Hul.isnull(context) || !context){
									alert("Unknown error on server side");
								}else if(Hul.isnull(context.error)){
									dt.deleteRow(oRecord.getId(), -1);
									top.HEURIST.rectypes = context.rectypes;
									alert("Constraint pair was deleted");
								}
							}

							var baseurl = top.HEURIST.baseURL + "admin/structure/saveStructure.php";
							var callback = _updateAfterDelete;
							var params = "method=deleteRTC&db="+db+"&srcID=" + oRecord.getData("src_id") + "&trgID=" + oRecord.getData("trg_id");
							top.HEURIST.util.getJsonData(baseurl, callback, params);

						}
				}*/

		});

		// Subscribe to events for row selection
		_myDataTable.subscribe("rowMouseoverEvent", _myDataTable.onEventHighlightRow);
		_myDataTable.subscribe("rowMouseoutEvent", _myDataTable.onEventUnhighlightRow);

		_initTable(arr);

	}//end of initialization =====================


	/**
	* Creates and (re)fill TERMS datatable
	*/
	function _initTermTable(arr)
	{

	//if datatable exists, only refill ==========================
				if(!Hul.isnull(_myTermTable)){

					// all stuff is already inited, change livedata in datasource only
					_myTermSource.liveData = arr;

					//refresh table
					_myTermSource.sendRequest("", {
								success : _myTermTable.onDataReturnInitializeTable,
								failure : _myTermTable.onDataReturnInitializeTable,
								scope   : _myTermTable,
								argument : { pagination: { recordOffset: 0 } } // to jump to page 1
					});

					return;
				}

//									terms.push([term_ID, top.HEURIST.rectypes.constraints[rec_ID].byTarget[target_rec_ID][term_ID].limit, 'notes']);

	//create new datatable ==========================

								_myTermSource = new YAHOO.util.LocalDataSource(arr, {
									responseType : YAHOO.util.DataSource.TYPE_JSARRAY,
									responseSchema : {
										fields: ["trm_id", "trm_label", "limit", "notes", "changed"]
									}
								});

								var myColumnDefs = [
			{ key: "trm_label", label: "Term", sortable:true, resizeable:true},
			{ key: "limit", label: "Limit", sortable:true, className:'right', width:20,
				resizeable:true, formatter:'number',
				editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true}), 
				formatter: function(elLiner, oRecord, oColumn, oData) {
					var val = oRecord.getData('limit');
					elLiner.innerHTML = ((Number(val)<1)?'unlimited':val);
			}
			},
			{ key: "notes", label: "Notes", resizeable:true, sortable:false, editor: new YAHOO.widget.TextareaCellEditor()},
			{ key: null, label: "Delete", sortable:false, width:10, 
				formatter: function(elLiner, oRecord, oColumn, oData) {
elLiner.innerHTML = '<a href="#delete_term"><img src="../../common/images/cross.png" title="Delete this Term" /><\/a>';
						}
			}
								];


		var myConfigs = {};

		_myTermTable = new YAHOO.widget.DataTable('tabContainer2', myColumnDefs, _myTermSource, myConfigs);

		_myTermTable.subscribe("cellClickEvent", _myTermTable.onEventShowCellEditor);
		//click on action images
		_myTermTable.subscribe('linkClickEvent', function(oArgs){


				var dt = this;
				var elLink = oArgs.target;
				var oRecord = dt.getRecord(elLink);

				if(elLink.hash === "#delete_term"){ 

					YAHOO.util.Event.stopEvent(oArgs.event);
						
						var cnt = _currentPairRecord.getData('count');
						var swarn = (cnt==1)
									? "Do you really want to delete constraint pair at all?"
									: "Do you really want to delete term?"
					
						var value = confirm(swarn); // '"+oRecord.getData('fullname')+"'?");
						if(value) {

							function _updateAfterDelete(context) {

								if(Hul.isnull(context) || !context){
									alert("Unknown error on server side");
								}else if(Hul.isnull(context.error)){
									
									if(cnt==1){
										_myDataTable.deleteRow(_currentPairRecord.getId(),-1);
										_currentPairRecord = null;
										_currentPair = null;
										Dom.get('termsList').style.display = 'none';
									}else{
										
										if(oRecord.getData("trm_label")=="any"){
											Dom.get('btnAddAny').style.visibility = "visible";
											_currentPairRecord.setData('hasAny', false);
										}

										_currentPairRecord.setData('count',cnt--);
										dt.deleteRow(oRecord.getId(), -1);
									}
									top.HEURIST.rectypes.constraints = context.constraints;
									//alert("Constraint was deleted");
								}
							}

							var baseurl = top.HEURIST.baseURL + "admin/structure/saveStructure.php";
							var callback = _updateAfterDelete;
							var params = "method=deleteRTC&db="+db + _currentPair +
																	"&trmID=" + oRecord.getData("trm_id");
							top.HEURIST.util.getJsonData(baseurl, callback, params);

						}
				}

		});

		// Subscribe to events for row selection
		_myTermTable.subscribe("rowMouseoverEvent", _myTermTable.onEventHighlightRow);
		_myTermTable.subscribe("rowMouseoutEvent", _myTermTable.onEventUnhighlightRow);

		_initTermTable(arr);

	}//end of initialization =====================


	/**
	* Show table with list of relation types
	*/
	function _editConstraint(oRecord){

		Dom.get('termsList').style.display = 'inline-block';
		Dom.get('currPairTitle').innerHTML = oRecord.getData("src_name") + ' to ' + oRecord.getData("trg_name");

		_currentPairRecord = oRecord;
		_currentPair = "&srcID=" + oRecord.getData("src_id") +	"&trgID=" + oRecord.getData("trg_id");

		var _currentTerms = oRecord.getData("terms");

		var hasAny = oRecord.getData("hasAny");
		Dom.get('btnAddAny').style.visibility = hasAny?"hidden":"visible";
		
		//init terms table
		_initTermTable(_currentTerms);
	}

	/**
	*
	*/
	function _saveConstraint(src_id, trg_id){

		var isFirst = (src_id!=null || trg_id!=null); 
		
							function _updateAfterSave(context) {

								if(Hul.isnull(context) || !context){
									alert("Unknown error on server side");
								}else if(Hul.isnull(context.error)){

									if(isFirst)
									{
										
									var i;
									for( i=0; i<context.result.length; i++){
										var res = context.result[i];

										var sname = 'any',
										    rec_ID = 0,
										    tname = 'any',
										    target_rec_ID = 0;

										if(isNaN(Number(res[0]))){
											rec_ID = 0;
											sname = 'any';
										}else{
											rec_ID = Number(res[0]);
											sname = top.HEURIST.rectypes.names[rec_ID];
										}
										if(isNaN(Number(res[1]))){
											target_rec_ID = 0;
											tname = 'any';
										}else{
											target_rec_ID = Number(res[1]);
											tname = top.HEURIST.rectypes.names[target_rec_ID];
										}
										
										res =  {src_id:Number(rec_ID),
												src_name:sname,
												trg_id:Number(target_rec_ID),
												trg_name:tname,
												count:1,
												terms:[['null',
												'any',
												0,
												'',
												false]],
												hasAny:true}; //terms

	            						_myDataTable.addRow(res);
										}
									}

									top.HEURIST.rectypes.constraints = context.constraints;
								}
							}

			//1. creates object to be sent to server
			var values = [],
				currPair;
			if(!isFirst){
				
				currPair = _currentPair;
				
				var i;
				var records = _myTermTable.getRecordSet();  
				var len = records._records.length-1;  
				for(i=0;i<=len;i++){  
					var rec = records._records[i];
					values.push([rec.getData("trm_id"),
					rec.getData("trm_label"),
					rec.getData("limit"),
					rec.getData("notes"), false]);
				}
				
				_currentPairRecord.setData("terms", values);
 			}else{
 				//check that this pair is unique
 				var srcrec = top.HEURIST.rectypes.constraints[(src_id==''?'any':src_id)];
 				if(Hul.isnull(srcrec) || Hul.isnull(srcrec.byTarget[(trg_id==''?'any':trg_id)])){
					
 					currPair = "&srcID=" + src_id +	"&trgID=" + trg_id;
					values.push(['null','null','null','']);
 				}else{
					alert('There is such pair already');
					return;
 				}
			}
			var str = YAHOO.lang.JSON.stringify(values);


			// 2. sends data to server
			var baseurl = top.HEURIST.baseURL + "admin/structure/saveStructure.php";
			var callback = _updateAfterSave;
			var params = "method=saveRTC&db="+db + currPair + "&data=" + encodeURIComponent(str);
			Hul.getJsonData(baseurl, callback, params);

	}

	/**
	*
	*/
	function createRectypeSelector(selname, isall)
	{
		var rectypes = top.HEURIST.rectypes;
		var rectypeValSelect = document.getElementById(selname);
		rectypeValSelect.innerHTML = '<option value="" selected>any</option>';
		// rectypes displayed in Groups by group display order then by display order within group
		for (var index in rectypes.groups){
			if (index == 'groupIDToIndex' || rectypes.groups[index].showTypes.length < 1){
				continue;
			}
			var grp = document.createElement("optgroup");
			var firstInGroup = true,
				i=0;
			grp.label = rectypes.groups[index].name;
			for (; i < rectypes.groups[index].showTypes.length; i++) {
				var recTypeID = rectypes.groups[index].showTypes[i];
				if (recTypeID && (isall || rectypes.usageCount[recTypeID]>0)) {
					if (firstInGroup){
						rectypeValSelect.appendChild(grp);
						firstInGroup = false;
					}
					Hul.addoption(rectypeValSelect, recTypeID, rectypes.names[recTypeID]);
				}
			}
		}
	}

	/**
	* find index in term array for current pair
	*/
	function _findTermIndex(termID){
		if(_currentPairRecord){
			var curterms = _currentPairRecord.getData("terms");
			var idx;
			for( idx in curterms){
				if(!Hul.isnull(idx)){
					if(termID == curterms[idx][0]){
						return idx;
					}
				}
			}
		}
		return -1;
	}

	/**
	* 
	*/
	function _addAny(){
		var curterms = _currentPairRecord.getData("terms");
		curterms.push(['null',
						'any',
						0,
						'',
						false]);
		//update table
		_currentPairRecord.setData("terms", curterms);
		_currentPairRecord.setData("hasAny", true);
		Dom.get('btnAddAny').style.visibility = "hidden";
		
		_initTermTable(curterms);		
	}
	
	/**
	* onSelectTerms
	*
	* listener of "Add Term" button
	* Shows a popup window where user can select terms
	*/
	function _onSelectTerms(){

		var curterms = _currentPairRecord.getData("terms");
		var allTerms = "{";
		var idx, termID;
		for( idx in curterms){
			if(!Hul.isnull(idx)){
				termID = curterms[idx][0];
				if(!Hul.isnull(termID)){
					allTerms = allTerms + '"'+termID+'":{},'
				}
			}
		}
		if(allTerms!='') {
			allTerms = allTerms+ '}';
		}

		Hul.popupURL(top, top.HEURIST.basePath +
			"admin/structure/selectTerms.html?datatype=relationtype&all="+allTerms+"&db="+db,
			{
			"close-on-blur": false,
			"no-resize": true,
			height: 500,
			width: 750,
			callback: function(editedTermTree, editedDisabledTerms) {
				if(editedTermTree || editedDisabledTerms) {

					var existingTree = Hul.expandJsonStructure(editedTermTree);
					var disabledTerms = Hul.expandJsonStructure(editedDisabledTerms);
					var selterms = [];

					function __getFlatArray(termSubTree){
						//get flat list
						for( termID in termSubTree){
							if(!Hul.isnull(termID)){
								if(disabledTerms.indexOf(termID)<0){
									selterms.push(termID);
									if(typeof termSubTree[termID] === "object") {
										__getFlatArray(termSubTree[termID]);
									}
								}
							}
						}
					}

					__getFlatArray(existingTree);

					//update terms in current pair record and reload table
					idx = 0;
					while( idx<curterms.length){
							if(curterms[idx][1]!='any')
							{
								termID = curterms[idx][0];
								if(selterms.indexOf(termID)<0){
									//remove
									curterms.splice(idx,1);
									continue;
								}
							}
							idx++;
					}
					var fi_trm_label = top.HEURIST.terms.fieldNamesToIndex.trm_Label;
					for( idx in selterms){
						if(!Hul.isnull(idx)){
							termID = selterms[idx];
							if(_findTermIndex(termID)<0){
								//add to array
								curterms.push([termID,
												top.HEURIST.terms.termsByDomainLookup.relation[termID][fi_trm_label],
												0,
												'',
												false]);

							}
						}
					}

					//update table
					_currentPairRecord.setData("terms", curterms);
					_initTermTable(curterms);


				}
			}
		});

	}


	//
	//public members
	//
	var that = {

				/**
				* @param user - userID or email
				*/
				addConstraint: function(user){
					_saveConstraint(Dom.get('selSrcRectypes').value, Dom.get('selTrgRectypes').value);
				},

				toggleEdit:function(isactive){
					Dom.get('pnlSelectPair').style.display = (isactive)?'block':'none';
					Dom.get('pnlAddConstraint').style.display = (!isactive)?'block':'none';

				},

				toggleEdit2:function(isactive){
					Dom.get('pnlSelectTerm').style.display = (isactive)?'block':'none';
					Dom.get('pnlAddTerm').style.display = (!isactive)?'block':'none';

				},

				addTerms:function(){
					_onSelectTerms();
				},

				addAny:function(){
					_addAny();
				},
				
				saveTerms:function(){
					_saveConstraint(null,null);
					Dom.get('termsList').style.display = 'none';
				},

				getClass: function () {
					return _className;
				},

				isA: function (strClass) {
					return (strClass === _className);
				}

	};

	_init();  // initialize before returning
	return that;
}