## Heurist version 3 ##

Development of Heurist 3 started in late 2009, with a narrower set of staff. The main programming contributors are Steve White and Artem Osmakov, with smaller design contributions from Irek Golka, Steven Hayes and Andrew Wilson.

The development of Heurist 3 had a number of very specific aims:

  * **Revise directory structure and naming conventions:** clean up the code to create logical grouping of functions and systematic naming (Johnson, White). At the same time, Golka updated the visual design, and the working version of what was essentially still Version 2, was baptised H2.5

  * **Cleanup the underlying MySQL database structure:** standardisation of table and field naming, tightening up the definitions of fields, documenting the function of each field in the field comments, addition of fields for planned new functions, addition of referential integrity (Johnson, assisted by White).

  * **Update version 2 databases to version 3 format:** A huge SQL script (Johnson) to convert databases was, in hindsight, the wrong approach, because H2 data were too messy to be worth munging to a new format, due in particular to lack of referential integrity. Osmakov has since written a direct conversion with field matching which allows transfer of data between H2 or H3 databases to a new cleanly structured H3 database.

  * **Update code to work with new database structure:** this includes functional changes such as storing users and structural definitions in each database, new database creation on the fly, central registration of databases, import of definitions between databases, and a new module for end-user database structure management  (‘Designer View’). Most of the underlying conversion work was carried out by Steve White, who also added new features to the search and edit pages. Database structure management was written by Artem Osmakov. Database creation, cloning, properties and registration was mostly written by Ian Johnson.  Irek Golka  did CSS work.  Juan Adriaanse, a programming intern, worked on structure download and mobile access, supervised and corrected by Steve White.

  * **New features in Heurist 3:** version 3 is essentially similar to version 2 apart from the improved structure and the ability of end-users to create and configure their own databases. The three panel search interface provides similar functionality to the old two panel interface, apart from a new selection model and directed filtering (White) which has some functional implications which are still being worked through. We added user configurable reports (Osmakov), direct file import (Osmakov) and blogging (White). The edit form is essentially unchanged apart from the new relationship marker type (White).

  * **Ongoing development:** current developments (April 2013) focus on synchronisation of heurist databases with bibliographic data (notably Zotero) and other databases (FileMaker, LightRoom), on data recoding and descriptive analysis, and on improved mapping and timelines including GIS data connections.