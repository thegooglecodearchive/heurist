/*
 *  Heurist Navigator framework
 *  Kim Jackson, October 2008
 */

 function HistoryManager(em) {

/* private */ var _query = null;
/* private */ var _records = null;
/* private */ var _displayModules = {};
/* private */ var _module = null;

/* private */ function _loadQuery(query) {  //SAW this belongs in the RM

	if (query === _query) return;

	_query = query;
	_records = null;

	// TODO: optimisation - if single id, check cache first

	// FIXME: provide the option to load all, or page through records

	var records = [];
	var bulkLoader = new HLoader (
		function(s, r, c) {	// onload
			[].push.apply(records, r);
			if (records.length < c) {	// we haven't got all the results yet
				HeuristScholarDB.loadRecords (
					new HSearch(s.getQuery() + " offset:"+records.length),
					bulkLoader
				);
			}
			else {
				// we've loaded all the records
				_records = records;
				if (_module) {
					_displayModules[_module].recordsLoaded(_query, _records);
				}
			}
		},
		function(s,e) {	// onerror
			alert("error loading records: " + e);
		}
	);
	HeuristScholarDB.loadRecords (
		new HSearch(query),
		bulkLoader
	);
}

/* private */ function _showModule(module) {  //SAW  Does this belong in the UIM
	if (module !== _module) {
		_module = module;
		_displayModules[_module].show(_query, _records);
	}
}

var that = {

	init: function(defaultQuery, defaultModule, modules) {
		var initQuery, initModule;

		_displayModules = {};			//SAW  ?? UIM
		for (var module in modules) {
			if (! modules[module].recordsLoaded) {
				throw "HNavigator.init: invalid module " + module + ": no 'recordsLoaded' method";
			}
			if (! modules[module].show) {
				throw "HNavigator.init: invalid module " + module + ": no 'show' method";
			}
			_displayModules[module] = modules[module]
		}

		initQuery = YAHOO.util.History.getBookmarkedState("q");		// SAW this is also a module since it affects visible items though at a different dimension than YUI docs
		initModule = YAHOO.util.History.getBookmarkedState("m");  //SAW  module is a YUI generic term for both q and m, perhaps we should change m to v for view
		if (! initQuery) {
			initQuery = defaultQuery;
		}
		if (! initModule) {
			if (! _displayModules[defaultModule]) {
				throw "HNavigator.init: defaultModule " + defaultModule + " does not exist";
			}
			initModule = defaultModule;
		}

		YAHOO.util.History.register("q", initQuery, function(state) {
			_loadQuery(state);  // SAW Would it be best to send a message or do we need to hook this up directly to the RM
		});
		YAHOO.util.History.register("m", initModule, function(state) {
			_showModule(state);  // SAW same here for the UIM  and perhaps the same for the filter manager (FM)
		});


		YAHOO.util.History.onReady(function() {
			_loadQuery(YAHOO.util.History.getCurrentState("q"));
			_showModule(YAHOO.util.History.getCurrentState("m"));
		});

		try {
			YAHOO.util.History.initialize("yui-history-field", "yui-history-iframe");
		} catch (e) {
			_loadQuery(initQuery);
			_showModule(initModule);
		}
	},

	load: function(query, module) {
		var states = {};
		if (query  &&  query !== _query) {
			states["q"] = query;
		}
		if (module  &&  module !== _module) {
			if (! _displayModules[module]) {
				throw "HNavigator.load: module " + module + " does not exist";
			}
			states["m"] = module;
		}
		if (states["q"]  ||  states["m"]) {
			YAHOO.util.History.multiNavigate(states);
		}
	},

	show: function(module) {
		this.load(null, module);
	}

};

return that;

};

