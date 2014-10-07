/** Gets the selected IDs from top.HEURIST.search */
function getSelectedIDs() {
    var selectedIDs = [];
    var recIDs = top.HEURIST.search.getSelectedRecIDs(); 
    if(recIDs) {
        for(var key in recIDs) {
            if(!isNaN(key)) {
                selectedIDs.push(recIDs[key]);       
            }
        }
    }
    console.log("SELECTED IDs");
    console.log(selectedIDs);
    return selectedIDs;
}

/** Parses a recSet into usable data */
function parseRecSet() {
    var selectedIDs = getSelectedIDs();
    var results = top.HEURIST.search.results;
    var nodes = {};
    var links = [];
            
    // Building nodes
    for(var id in results.recSet) {
        // Get details
        var record = results.recSet[id].record;
        var depth = results.recSet[id].depth;
        var name = record["5"];  
        var group = record["4"];
        var image = top.HEURIST.iconBaseURL + group + ".png";
        var selected = selectedIDs.indexOf(id.toString()) > -1;

        // Construct node
        var node = {id: parseInt(id), name: name, image: image, count: 1, depth: depth, selected: selected};
        nodes[id] = node;    
        console.log("Node #" + id);    
    }
    
    
    /**
    * Finds links in a revPtrLinks or revRelLinks object
    */
    function findLinks(source, object, type) {
        var recIDs = object.byRecIDs;
        for(var recID in recIDs) {
            //console.log("ID " +id+ " points to recID: " + recID);
            var target = nodes[recID];
            if(target !== undefined) {
                var ids = recIDs[recID];
                //console.log("RELATION ID's");
                //console.log(ids);
                if(ids !== undefined && ids.length > 0) {
                    for(var i = 0; i < ids.length; i++) {
                        // Define relation    
                        console.log("Relation #" + i + " ID: " + ids[i]);        
                        var relation = nodes[ids[i]];
                        if(relation === undefined) {
                            relation = {id: ids[i], name: type, image: "unknown.png", count: 1, pointer: type.indexOf("ointer")>0};
                        }
                        
                        // Construct a link
                        var link = {source: source, relation: relation, target: target, targetcount: target.count};
                        //console.log("LINK");
                        //console.log(link);   
                        links.push(link);  
                    } 
                }  
            }
        }
    }

    // Go through all records
    for(var id in results.recSet) {
        //console.log("RecSet["+id+"]:");
        //console.log(results.recSet[id]);
        var source = nodes[id];
        
        // Determine links
        if(source !== undefined) {
            var map = {"ptr": "Pointer", "revPtr": "Reverse Pointer", "revPtrLinks": "Reverse pointer link",
                       "rel": "Relationship", "relLinks": "Relationship marker", "revRelLinks": "Reverse relationship marker"};
                       
            for(var key in map) {
                var object = results.recSet[id][key];
                console.log(key + " for recSet["+id+"]");
                console.log(object);
                if(object !== undefined) {
                    findLinks(source, object, map[key]);
                }
            }
        }
    }
    
    // Construct data object
    var data = {nodes: nodes, links: links};
    console.log("DATA");
    console.log(data);
    return data;
}

/** Calls the visualisation plugin */
function visualize(data) {
    // Custom data parsing
    function getData(data) {
        console.log("Custom getData() call");
        return data;
    }
    
    // Calculates the line length
    function getLineLength(record) {
        var length = getSetting(setting_linelength);
        if(record !== undefined && record.hasOwnProperty("depth")) {
            length = length / (record.depth+1);
        }
        return length;
    }
    
    // Call plugin
    console.log("Calling plugin!");
    putSetting(setting_entityradius, 1);
    putSetting(setting_linewidth, 1);
    $("#visualisation").visualize({
        data: data,
        getData: function(data) { return getData(data); },
        getLineLength: function(record) { return getLineLength(record); },
        
        showEntitySettings: false,
        showLineWidth: false,
        showFormula: false
    });  
}

