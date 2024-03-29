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
* DBUpgrade_1.x.x_to_1.x.x.sql: SQL file to update Heurist database format between indicated versions
*
* @author      Ian Johnson   <ian.johnson@sydney.edu.au>
* @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
* @copyright   (C) 2005-2013 University of Sydney
* @link        http://Sydney.edu.au/Heurist
* @version     3.1.5
* @license     http://www.gnu.org/licenses/gpl-3.0.txt GNU License 3.0
* @package     Heurist academic knowledge management system
*/

-- additional upgrades to future versions can be added as new files but should not be processed
-- until the software is updated to expect them


-- Source version: 1.1.0
-- Target version: 1.2.0
-- Safety rating: SAFE
-- Description: Add Certainty rating and Annotation text to every key-value (detail) pair

-- Extend sys_SyncDefsWithDB from 63 --> 1000 to hold up to about 20 Zotero keys
 ALTER TABLE `sysIdentification`
    Change `sys_SyncDefsWithDB` Varchar( 1000 ) NULL DEFAULT ''
    COMMENT 'One or more Zotero library name,userID,groupID,key combinations separated by pipe symbols, for synchronisation of Zotero libraries';

-- Addition of certainty and annotation fields for alignment with NeCTAR/LIEF FAIMS project
-- This is also a potentially very useful function for broader use
    ALTER TABLE `recDetails`
    ADD `dtl_Certainty` DECIMAL( 3, 2 ) NOT NULL DEFAULT '1.0'
    COMMENT 'A certainty value for this observation in the range 0 to 1, where 1 indicates complete certainty';

    ALTER TABLE `recDetails` ADD `dtl_Annotation` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL
    COMMENT'A short note / annotation about this specific data value - may enlarge for example on the reasons for the certainty value';

-- Default behaviour is not to show these, for compatibility with DB version 1.1.0 and before
    ALTER TABLE `defRecStructure` ADD `rst_ShowDetailCertainty` TinyInt default 0
    COMMENT 'When editing the field, allow editng of the dtl_Certainty value (off by default)';

    ALTER TABLE `defRecStructure` ADD `rst_ShowDetailAnnotation` TinyInt default 0
    COMMENT 'When editing the field, allow editng of the dtl_Annotation value (off by default)';


-- Provision for an image or PDF or external URL to define or illustrate the term
    ALTER TABLE  `defTerms`
    ADD  `trm_SemanticReferenceURL` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL
    COMMENT 'The URL to a semantic definition or web page describing the term';

    ALTER TABLE  `defTerms`
    ADD  `trm_IllustrationURL` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL
    COMMENT 'The URL to a picture or other resource illustrating the term. If blank, look for trm_ID.jpg/gif/png in term_images directory';

    -- Provision for an external semantic URL to define the base field type
    ALTER TABLE  `defDetailTypes`
    ADD  `dty_SemanticReferenceURL` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL
    COMMENT 'The URL to a semantic definition or web page describing the base field type';


-- Store user profile information for use in H4 framework
    ALTER TABLE  `sysUGrps`
    ADD  `ugr_UsrPreferences` TEXT NULL
    COMMENT 'JSon array containing user profile available across machines. If blank, profile is specific to local session';

-- Unique composite key indexing (by record type), needed by FAIMS among others, on the way to replacing Tom's fuzzy matching methods
-- which were more bibliogeraphy-oruiented and never terribly successful, with a clear-cut unique key system by record type

    ALTER TABLE Records
    Add rec_KeyfieldsComposite VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL
    COMMENT 'Contains a composite field constructed from record details flagged for indexing by rst_UseInUniqueIndex';

    ALTER TABLE Records
    ADD UNIQUE KEY rec_UniqueRectypeKeyfields (rec_RecTypeID,rec_KeyfieldsComposite);

    ALTER TABLE defRecStructure
    ADD rst_UseInUniqueIndex TINYINT NOT NULL DEFAULT 0
    COMMENT 'Indicates whether field is to be used in the composite index which controls record uniqueness by record type';

-- It would of course be better to have a proper plugin architecture for calculated fields, but this provides a good stopgap
-- method for combining fields eg. to create a unique item identifier for hierarchichally organised entities such as excavated artefacts
    ALTER TABLE defRecStructure
    ADD rst_CalcFieldMask Varchar(250) NULL
    COMMENT 'A mask string along the lines of the title mask allowing a composite field to be generated from other fields in the record';
    -- Note: should be able to use the title mask routines to manage content of the CalcFieldmask, and both can be extended together
    -- eg. refomatting dates, truncating strings

    ALTER TABLE defRecStructure
    ADD rst_NumericLargestValueUsed INTEGER NULL
    COMMENT 'For numeric fields, Null = no auto increment, 0 or more indicates largest value used so far. Set to 0 to switch on incrementing';
    -- Note: need to set initial value if not 0 and protect numeric auto-increment fields against manual editing