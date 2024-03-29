/**
* H4 connections between records
* 
* @package     Heurist academic knowledge management system
* @link        http://HeuristNetwork.org
* @copyright   (C) 2005-2014 University of Sydney
* @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
* @author      Jan Jaap de Groot    <jjedegroot@gmail.com>
* @license     http://www.gnu.org/licenses/gpl-3.0.txt GNU License 3.0
* @version     4.0
*/

/*
* Licensed under the GNU License, Version 3.0 (the "License"); you may not use this file except in compliance
* with the License. You may obtain a copy of the License at http://www.gnu.org/licenses/gpl-3.0.txt
* Unless required by applicable law or agreed to in writing, software distributed under the License is
* distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied
* See the License for the specific language governing permissions and limitations under the License.
*/


$.widget( "heurist.connections", {

    // default options
    options: {
        title: '',
        recordset: null,
        selection: null //list of record ids
    },

    _events: null,
    recordset_changed: true,

    // the constructor
    _create: function() {

        var that = this;
        
        this.framecontent = $('<div>')
                   .css({
                        position:'absolute', top:'2.5em', bottom:0, left:0, right:0,
                        'background':'url('+top.HAPI4.basePath+'assets/loading-animation-white.gif) no-repeat center center'})
                   .appendTo( this.element );
                   
        this.dosframe = $( "<iframe>" ).css({overflow: 'none !important', width:'100% !important'}).appendTo( this.framecontent );


        //-----------------------     listener of global events
        this._events = top.HAPI4.Event.LOGIN+' '+top.HAPI4.Event.LOGOUT + ' ' 
            + top.HAPI4.Event.ON_REC_SEARCH_FINISH + ' ' + top.HAPI4.Event.ON_REC_SEARCHSTART + ' ' + top.HAPI4.Event.ON_REC_SELECT;

        $(this.document).on(this._events, function(e, data) {
            // Login
            if(e.type == top.HAPI4.Event.LOGIN){

                that._refresh();

            // Logout
            }else  if(e.type == top.HAPI4.Event.LOGOUT) { 
                
                that.recordset_changed = true;
                that.option("recordset", null);
                that._refresh();

            // Search results
            }else if(e.type == top.HAPI4.Event.ON_REC_SEARCH_FINISH){

                //find all relation within given result set
                that.recordset_changed = true;
                that._getRelations( data );
                
                //that.option("recordset", data); //hRecordSet
                //that.loadanimation(false);

            // Search start
            }else if(e.type == top.HAPI4.Event.ON_REC_SEARCHSTART){

                that.option("recordset", null);
                that.option("selection", null);
                that.loadanimation(true);
                //???? that._refresh();
              
            // Record selection  
            }else if(e.type == top.HAPI4.Event.ON_REC_SELECT){
                
                if(data && data.source!=that.element.attr('id')) { //selection happened somewhere else
                  
                    that._doVisualizeSelection( top.HAPI4.getSelection(data.selection, true) );
                }            
            }
        });

        
        // Refreshing
        this.element.on("myOnShowEvent", function(event){
            if( event.target.id == that.element.attr('id')){
                that._refresh();
            }
        });
        
        this.dosframe.on('load', function(){
                that._refresh();
        });
        
        
    }, //end _create


    /*
    _setOptions: function() {
        // _super and _superApply handle keeping the right this-context
        this._superApply( arguments );
        this._refresh();
    },
    */  

    /* private function */
    _refresh: function(){

        /* change title
        if(this.options.title!=''){
            var id = this.element.attr('id');
            $(".header"+id).html(this.options.title);
            $('a[href="#'+id+'"]').html(this.options.title);
        }*/
        
        //refesh if element is visible only - otherwise it costs much resources        
        if( this.element.is(':visible') && this.recordset_changed) {
        
            if(this.dosframe.attr('src')!==this.options.url){
                
                this.options.url = top.HAPI4.basePath + '/page/springDiagram.php?db=' + top.HAPI4.database;
                this.dosframe.attr('src', this.options.url);
              
            // Content loaded already    
            }else{
                // SPRING DIAGRAM CODE
                // console.log("CONTENT LOADED ALREADY");  
                // console.log(this.options);
                
                if(this.options.recordset !== null) {
                    //console.log("Showing recordset connections");
                    
                    if(this.options.relations == null){ //relation not yet loaded
                        
                        this._getRelations(this.options.recordset);
                        
                    }else{
                    
                        var records = this.options.recordset.getRecords();
                        var relations = this.options.relations;
                        
                        // Parse response to spring diagram format
                        var data = this._parseData(records, relations);
                        this._doVisualize(data);
                    
                    }
                }
                
            }
        
        }
    },

    // events bound via _on are removed automatically
    // revert other modifications here
    _destroy: function() {

        this.element.off("myOnShowEvent");
        $(this.document).off(this._events);

        var that = this;

        // remove generated elements
        this.dosframe.remove();
        this.framecontent.remove();
    },
    
    loadanimation: function(show){
        if(show){
            //this.dosframe.hide();
            this.framecontent.css('background','url('+top.HAPI4.basePath+'assets/loading-animation-white.gif) no-repeat center center');
        }else{
            this.framecontent.css('background','none');
            //this.dosframe.show();
        }
    },
    
    /**
    * private - send request to server side to find all relation withing given recordset
    * @param recordset
    */
    _getRelations: function( recordset ){
        //console.log("getRelations CALLED");
        //console.log(recordset);
        
        if(top.HEURIST4.util.isnull(recordset)) return;

        this.option("relations", null);
        
        if(!this.element.is(':visible')){
                this.option("recordset", recordset);
                return;
        }
        
        var that = this; 
        //get first 2000 records and send their IDS to server to get related record IDS
        var records_ids = recordset.getIds(2000);
        if(records_ids.length>0){
            
            var callback = function(response)
            {
                var resdata = null;
                if(response.status == top.HAPI4.ResponseStatus.OK){
                    // Store relationships
                    console.log("Successfully retrieved relationship data!", response.data);
                    that.option("relations", response.data);
                    
                    // Parse response to spring diagram format
                    var data = that._parseData(recordset.getRecords(), response.data);
                    that._doVisualize(data);
                }else{
                    top.HEURIST4.util.showMsgErr(response.message);
                }
                
                that.option("recordset", recordset); //hRecordSet
                that.loadanimation(false);
                
            }

            top.HAPI4.RecordMgr.search_related({ids:records_ids.join(',')}, callback);
        }
    }
    

    //@todo - move inside widget


    /**
    * Parses record data and relationship data into usable D3 format
    * 
    * @param records    Object containing all record
    * @param relations  Object containing direct & reverse links
    * 
    * @returns {Object}
    */
    , _parseData: function (records, relations) {
        var data = {}; 
        var nodes = {};
        var links = [];

        if(records !== undefined && relations !== undefined) {
            // Construct nodes for each record
            for(var id in records) {
                var node = {id: parseInt(id),
                            name: records[id][5],
                            image: top.HAPI4.iconBaseURL+records[id][4],
                            count: 0,
                            depth: 1
                           };
                nodes[id] = node;
            }
            
            
            /**
            * Determines links between nodes
            * 
            * @param nodes      All nodes
            * @param relations  Array of relations
            */
            function __getLinks(nodes, relations) {
                var links = [];
                
                // Go through all relations
                for(var i = 0; i < relations.length; i++) { 
                    // Null check
                    var source = relations[i].recID;
                    var target = relations[i].targetID;
                    var dtID = relations[i].dtID;
                    var type = "Floating relationship";
                    if(dtID > 0) {
                        //type = top.HEURIST4.detailtypes.typedefs[dtID].commonFields[1];
                        type = top.HEURIST4.detailtypes.names[dtID];
                    }

                    // Link check
                    if(source !== undefined && nodes[source] !== undefined && target !== undefined && nodes[target] !== undefined) { 
                        // Construct link
                        var link = {source: nodes[source],
                                    target: nodes[target],
                                    targetcount: 1,
                                    relation: {name: type}  //top.HEURIST4.detailtypes.typedefs[id].commonfields[1]
                                   };
                        links.push(link); 
                    }      
                }   
                
                return links;
            }
                    
            
            // Links
            links = links.concat( __getLinks(nodes, relations.direct)  ); // Direct links
            links = links.concat( __getLinks(nodes, relations.reverse) ); // Reverse links
        }

        // Construct data object with nodes as array
        var array = [];
        for(var id in nodes) {
            array.push(nodes[id]);
        }
        return {nodes: array, links: links};
    }

    /** Calls the visualisation plugin */
    , _doVisualize: function (data) {
        //console.log("Visualize called in connections.js");
        
        if( !top.HEURIST4.util.isnull(this.dosframe) && this.dosframe.length > 0 ){
            var that = this;
            this.dosframe[0].contentWindow.showData(data, this.options.selection, 
                    function(selected){
                        $(that.document).trigger(top.HAPI4.Event.ON_REC_SELECT, 
                        { selection:selected, source:that.element.attr('id') } );
                    }            
            
            );
            this.recordset_changed = false;
        }
        /* Call showData method of the springDiagram iFrame
        var iframe = $("iframe[src*=springDiagram]");
        if(iframe != null && iframe !== undefined && iframe.length >= 1) {
            iframe[0].contentWindow.showData(data);
        }*/
    }    

    , _doVisualizeSelection: function (selection) {

            if(top.HEURIST4.util.isnull(this.options.recordset)) return;

            this.option("selection", selection);
            
            if(!this.element.is(':visible')
                || top.HEURIST4.util.isnull(this.dosframe) || this.dosframe.length < 1){
                    return;
            }
            
            this.dosframe[0].contentWindow.showSelection(this.options.selection);
    }    

});