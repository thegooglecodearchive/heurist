<?php

    /**
    * Retrieves map data for a certain database.
    * 
    * @package     Heurist academic knowledge management system
    * @link        http://HeuristNetwork.org
    * @copyright   (C) 2005-2014 University of Sydney
    * @author      Jan Jaap de Groot  <jjedegroot@gmail.com>
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

    require_once (dirname(__FILE__).'/../System.php');
    require_once (dirname(__FILE__).'/../common/db_files.php');
    
    $recordQuery = "SELECT * FROM Records r INNER JOIN defRecTypes d ON r.rec_RecTypeID=d.rty_ID";
    $detailQuery = "SELECT * FROM recDetails rd WHERE rd.dtl_RecID=";
    
    
    /**
    * Finds the url that belongs to the file with the given fileID in the given system
    * 
    * @param mixed $system System reference
    * @param mixed $fileID FileID
    * @return mixed Image URL
    */
    function getFileURL($system, $fileID) {
        $paths = fileGetPath_URL_Type($system, $fileID);
        //print_r($paths);

        if(isset($paths[0][0])) {  
            // Heurist URL
            return HEURIST_FILESTORE_URL . $paths[0][0];
        }else if(isset($paths[0][1])) {
            // External URL
            return $paths[0][1];
        }
        return "null";
    }
  
    /**
    * Retrieves a term by its ID
    * 
    * @param mixed $system System reference
    * @param mixed $id     Term ID                 
    */
    function getTermByID($system, $id) {
        $term = new stdClass();
    
        // Select term
        $query = "SELECT * FROM defTerms WHERE trm_ID=".$id;
        $res = $system->get_mysqli()->query($query);

        if ($res) {
            $row = $res->fetch_assoc();
            if($row) {
                $term->id = $row["trm_ID"];
                $term->label = $row["trm_Label"];
                $term->description = $row["trm_Description"];
            }
        } 
        
        return $term;    
    }
    
    /**
    * Finds a record in the database by ID
    * 
    * @param mixed $system System reference
    * @param mixed $id  Record ID
    */
    function getRecordByID($system, $id) {
        global $recordQuery;
        $record = new stdClass();
        
        // Select the record
        $query = $recordQuery." WHERE r.rec_ID=".$id;
        $res = $system->get_mysqli()->query($query);

        if ($res) {
            $row = $res->fetch_assoc();
            if($row) {
                // Data object containing the row values
                $record = getRecord($row);
            }
        }
        
        return $record;
    }
    
    /**
    * Constructs a record object from a SQL result row:
    * - ID: the id
    * - Title: record title
    * - RectypeID: record type id      
    * 
    * @param mixed $row SQL row
    * @return stdClass Record object
    */
    function getRecord($row) {
        $obj = new stdClass();
        $obj->id = intval($row["rec_ID"]);
        $obj->title = $row["rec_Title"];
        $obj->rectypeID = intval($row["rec_RecTypeID"]);
        $obj->rectypeName = $row["rty_Name"];
        return $obj;
    }
    
    /**
    * Retrieves a detailed record data out of the database
    * 
    * Allowed types according to field type 'Data source' (3-1083): 
    * - Map image file (tiled)        RT_TILED_IMAGE_LAYER
    * - KML                           RT_KML_LAYER
    * - Shapefile                     RT_SHP_LAYER
    * - Map image file (untiled)      RT_IMAGE_LAYER
    * - Mappable query                RT_QUERY_LAYER
    * 
    * @param mixed $system System reference
    * @param mixed $id Record ID
    */
    function getDetailedRecord($system, $id) {
        //echo "Get detailed record #".$id;
        $record = getRecordByID($system, $id);
        $record = getRecordDetails($system, $record);
        return $record; 
    }
    
    /**
    * Adds extended details to a record
    * Possible etail types:
        topLayer: Top Map Layer
        layers: array of Map Layers
        sourceURL: URL pointing somewhere
        description: Description of the record
        imageType: Image type
        long: Longitude
        lat: Latitude
        maxZoom: Maximum zoom
        minZoom: Minimum zoom
        Opacity: Desired opacity value
        Data source: Contains a record object
        minorSpan: The initial minor span on a map
        thumbnail: Record thumbnail
        query: Heurist query string
        tilingSchema: Image tiling schema
        kmlSnippet: KML snippet string
        kmlFile: A .kml file reference
        shapeFile: a SHP component
        dbfFile: a DBF component
        shxFile: a SHX component
        files: Array of files
        zipFile: A .zip file
    * 
    * @param mixed $system System reference
    * @param mixed $record Record reference
    */
    function getRecordDetails($system, $record) {
        global $detailQuery;
        //echo "Get record details of " . ($record->id);
        
        // Retrieve extended details
        $query = $detailQuery . $record->id;
        $details = $system->get_mysqli()->query($query);
        if($details) {
            // [dtl_ID]  [dtl_RecID]  [dtl_DetailTypeID]  [dtl_Value] [dtl_AddedByImport]  [dtl_UploadedFileID]   [dtl_Geo]  [dtl_ValShortened]  [dtl_Modified]
            while($detail = $details->fetch_assoc()) {  
                // Fields
                //print_r($detail);
                $type = $detail["dtl_DetailTypeID"];
                $value = $detail["dtl_Value"];
                $fileID = $detail["dtl_UploadedFileID"]; 

                /* GENERAL */
                if($type == DT_SHORT_SUMMARY) {
                    // Description
                    $record->description = $value; 
                    
                }else if($type == DT_CREATOR) {
                    // Creators
                    if(!property_exists($record, "creators")) {
                        $record->creators = array();   
                    }
                    array_push($record->creators, getRecordByID($system, $value));
                    
  
                /* SOURCE */
                }else if($type == DT_SERVICE_URL) {
                    // Source URL
                    $record->sourceURL = $value;
                    
                }else if($type == DT_DATA_SOURCE) {
                    // Data source
                    $record->dataSource = getDetailedRecord($system, $value);

                       
                /* MAP LAYERS */
                }else if($type == DT_TOP_MAP_LAYER) { // Recursive
                    // Top map layer 
                    $record->toplayer = getDetailedRecord($system, $value);

                }else if($type == DT_MAP_LAYER) {
                    // Map layer
                    if(!property_exists($record, "layers")) { // Recursive
                        $record->layers = array();   
                    }
                    array_push($record->layers, getDetailedRecord($system, $value));
 
                
                /* LOCATION */
                }else  if($type == DT_LONGITUDE_CENTREPOINT) {
                    // Longitude centrepoint
                    $record->long = floatval($value);
                    
                }else if($type == DT_LATITUDE_CENTREPOINT) {
                    // Latitude centrepoint
                    $record->lat = floatval($value);
                  
                  
                /* ZOOM */  
                }else if($type == DT_MAXIMUM_ZOOM) {
                    // Maximum zoom
                    $record->maxZoom = floatval($value);
                     
                }else if($type == DT_MINIMUM_ZOOM) {
                    // Minimum zoom
                    $record->minZoom = floatval($value); 

                    
                }else if($type == DT_OPACITY) {
                    // Opacity
                    $record->opacity = floatval($value);
   
                }else if($type == DT_MINOR_SPAN) {
                    // Initial minor span
                    $record->minorSpan = floatval($value);

                    
                /* IMAGE INFO */
                } else if($type == DT_THUMBNAIL) {
                    // Uploaded thumbnail 
                    $record->thumbnail = getFileURL($system, $fileID);
                    
                } else if($type == DT_MIME_TYPE) { 
                    // Mime type
                    $record->mimeType = getTermByID($system, $value);
                    
                } else if($type == DT_IMAGE_TYPE) {
                    // Tiled image type
                    $record->imageType = getTermByID($system, $value);

                }else if($type == DT_MAP_IMAGE_LAYER_SCHEMA) {
                    // Image tiling schema
                    $record->tilingSchema = getTermByID($system, $value);
                    
                    
                /* SNIPPET */
                }else if($type == DT_QUERY_STRING) {
                    // Heurist query string
                    $record->query = $value;
                    
                }else if($type == DT_KML) {
                    // KML snippet
                    $record->kmlSnippet = $value;

                    
                /* FILES */
                }else if($type == DT_FILE_RESOURCE) {
                    // File(s)
                    if(!property_exists($record, "files")) {
                        $record->files = array();   
                    }
                    array_push($record->files, getFileURL($system, $fileID));
                    
                }else if($type == DT_SHAPE_FILE) {
                    // Shape file (SHP component)
                    $record->shpFile = getFileURL($system, $fileID);
                    
                }else if($type == DT_DBF_FILE) {
                    // DBF file (DBF component)
                    $record->dbfFile = getFileUrl($system, $fileID);
                    
                }else if($type == DT_SHX_FILE) {
                    // SHX file (SHX component)
                    $record->shxFile = getFileURL($system, $fileID);
                    
                }else if($type == DT_ZIP_FILE) {
                    // Zip file
                    $record->zipFile = getFileURL($system, $fileID);
       
                }
            }    
        }
        return $record;
    }
    
    

    /**
    * Returns an array of all Map Documents for this database
    * 
    * Document object:
    * -----------------------------------------
    * - id: Record ID
    * - title: Record title
    * - rectypeID: Record type ID
    * -----------------------------------------
    * - topLayer: Layer object
    * - layers: Array of Layer objects
    * - long: Longitude
    * - lat: Latitude
    * - minZoom: Minimum zoom
    * - maxZoom: Maximum zoom 
    * - minorSpan: Initial minor span in degrees
    * - thumbnail: Thumbnail file   
    * ------------------------------------------
    * 
    * @param mixed $system System reference
    */
    function getMapDocuments($system) {
        //echo "getMapDocuments() called!";
        global $recordQuery;
        global $detailQuery;
        $documents = array();
        
        // Select all Map Document types
        $query = $recordQuery." WHERE d.rty_IDInOriginatingDB=".RT_MAP_DOCUMENT;
        $mysqli = $system->get_mysqli();
        $res = $mysqli->query($query);
        if ($res) {
            // Loop through all rows
            while($row = $res->fetch_assoc()) {
                // Document object containing the row values
                $document = getRecord($row);
                $document = getRecordDetails($system, $document);
                //print_r($document);
                array_push($documents, $document);
            }
        }
        
        //print_r($documents);
        return $documents;
    }
    
    

    // Initialize a System object that uses the requested database
    $system = new System();
    if( $system->init(@$_REQUEST['db']) ){                               
        // Get all Map Documents
        $documents = getMapDocuments($system);

        // Return the response object as JSON
        header('Content-type: application/json');
        print json_encode($documents);
    }else {
        // Show construction error
        echo $system->getError();   
    }
    
    exit();

?>