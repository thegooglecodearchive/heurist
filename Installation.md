# Installation #

Heurist is currently running on several servers on Ubuntu, Debian, RedHat and RHEL Linux platforms, including Virtual Servers at a number of universities, Amazon cloud, the NeCTAR Research Cloud, a French ISP (Ikoula), a US ISP (Frantech) and physical servers. We have also installed it on Windows under XAMPP.

## Current versions ##

We generally recommend using the latest alpha or beta version (if any) as it will have all the latest fixes and new features. Any bugs will generally be localised to a specific function and will be fixed rapidly (typically under a day) if reported.

  * **Heurist Version 4**

Most of our recent work is on Heurist version 4, which should be released as an alpha around the end of February 2015, and should replace version 3 at the end of March as our primary version. Version 4 is bi-directionaly compatible with Heurist version 3 databases, so version 3 will be kept available on our server for some time. You can download older versions simply by specifying their name - they are not generally deleted from our distribution server.

  * **h3.5.0** 13 Feb 2015
    * View linked record button next to pointer fields (in addition to edit)
    * Fix Zotero synchronisation bug, better warnings
    * Default multimedia import to search database directories, improved explanation
    * Fix bug in editing Advanced Properties
    * Fix bug in importing terms, including all terms where only Vocabulary is specified
    * Update FAIMS synch to reflect 2.0, better check of existence of SQLite
    * Hide page furniture when viewing annotated templates pages in Heurist
    * H4: Helpful guidance - next steps - popup on new databases
    * H4: Sync order of records in different views
    * H4: Print map, export KML and escape special characters, mapping toolbar
    * H4: Implement popups in timeline

  * **h3.4.0** 21 Jan 2015
    * Lots of small bug-fixes and cosmetics
    * Schema diagram
    * Network visualisation (alpha)
    * Improved installation

  * **h3.3.0** 27 November 201:
    * Data consistency check function updated
    * Corrected layout and menus now work on iPad Safari or Chrome
    * Workflow and layout improvements on advanced CSV file importer, import of geographic object from separate lat and long fields
    * Set relative rather than absolute paths for imported multimedia files when within database default directory
    * Corrected path settings in configuration file to resolve problematic default behaviour
    * Improvements to field insertion and default template in custom reports tab
    * Various improvements to error messages and general debugging

  * **h3.3.0-beta** 11th Nov 2014
    * schema visualisation as a spring graph with entity and link counts shown as diameter and line thickness
    * network visualisation diagram for selection result set
    * GEPHI GEFX file output from results of a query
    * support for tiered servers (Heurist on web server, database and files on separate servers behind firewall)
    * comprehensive database health check and fix functions for missing and incorrectly repeated values, broken pointers etc.
    * completely revised menu at top right bringing commonly used administrative functions to the fore and reorganising import and export aas separate menus
    * multi-file and large file upload to specified locations within the database directory
    * further bug fixing
    * development of HeuristNetwork.org website with video tutorials
    * revision of the online help file (ongoing) to bring it up-to-date with program changes

  * **h3.2.0-beta** 20 July 2014
    * _better menu structures, marked or removed deprecated functions
    * better laid out and documented popup dialogues for new records, saved searches and term definition
    * entirely new delimited text file normalising importer (matches existing records on multiple keys, inserts and updates records, extracts multiple entities from de-normalised spreadsheets, handles multi-value fields, unmatched quotes and new lines in text fields)
    * annotated template structure import (pulls structure via pages on the HeuristNetwork.org website)
    * XML structure export replacing deprecated pseudo-sql structure export
    * configuration of Zotero synchroniser to support multiple web libraries
    * FAIMS (http://fedarch.org) tablet app module creation, data import and repository sync (tDAR)
    * HuNI (http://huni.net.au) export to harvestable XML files
    * bug fixes including rendering of UTF8/accents in field labels_





---


## Heurist ##

If not logged in as root, add 'sudo' as shown - omit if logged in as root.

**Test pre-requisites**

> _`curl -l http://heurist.sydney.edu.au/HEURIST/DISTRIBUTION/verify_ubuntu_server.sh | bash -s`_

Heurist assumes that Linux, Apache, MySQL and PHP are installed (the so-called LAMP stack). This will test to see that the required PHP extensions are installed on an Ubuntu/Debian server, and will give instructions on how to install them if they are not. For Redhat/RHEL see list of prerequisites below pending an appropriate script. Please also see Prerequisities section below for changes required to Apache, MySQL and PHP INI files.

**Install:**

> _`curl -l http://heurist.sydney.edu.au/HEURIST/DISTRIBUTION/install_heurist.sh`
> > `| sudo bash -s  h3.5.0  /var/www/html  www-data`_

The second parameter is the installation directory (normally the web root at /var/www or /var/www/html) and the third is the user under which Apache runs (normally apache or www-data). This sets up and copies all necessary files to the HEURIST directory. You will need to add MySQL login information and contact email addresses to the Heurist initialisation file (heuristConfigIni.php) before running Heurist. The script will give you instructions at the end. The script ONLY creates directories, sets permissions and copies files, it does not make other changes to the server.

**Update:**


> _`curl -l http://heurist.sydney.edu.au/HEURIST/DISTRIBUTION/update_heurist.sh`
> > `| sudo bash -s  h3.5.0  /var/www/html/HEURIST`_

This creates a separate instance of Heurist in the HEURIST directory with the name of the update version, which can be used in parallel with the existing version. This can be replaced once the new version has been checked over.

The distribution file can be found at: http://heurist.sydney.edu.au/HEURIST/DISTRIBUTION/xxxxx.tar.bz2, where xxxxx is the version eg. h3.5.0

## Support files ##

Heurist requires _external_, external\_h4, _help_ and _exemplars_ directories, normally installed in /var/www/html/HEURIST/HEURIST\_SUPPORT and simlinked from the root folder of each Heurist codebase. They are located at http://heurist.sydney.edu.au/HEURIST/DISTRIBUTION/HEURIST_SUPPORT/external.tar.bz2, external\_h4.tar.bz2, help.tar.bz2, exemplars.tar.bz2. They are not included in the program's tar.bz2 files (above), but are downloaded and installed automatically by the installation and update scripts.

## Pre-requisites ##

Heurist runs under a standard Unix LAMP stack configuration (Apache, PHP, MySQL). We currently support PHP 5.0 - 5.3 and MySQL 5.1 - 5.6, although it should run on older versions, having started off in MySQL 4. You may be able to install all three at once with _lamp-stack_, or else install the three components separately with _sudo yum install_ ... or _sudo apt-get install_ ... Please search the web for instructions for your particular operating system. Be sure to run _/usr/bin/mysql\_secure\_installation_ to set the root password and remove default user and database.

Install the following extensions: _php5-curl php5-xsl php5-mysql php5-memcache memcached libapache2-mod-php5 (tested by verification script) and php5-gd pdo-mysql php-pdo php-mbstring_ (not tested, tbc). Some, such as mbstring, may already be installed by default. You may wish to change these to php5-xxxx extensions, but these should select the right version automatically.

In _/etc/my.cnf_ or _/etc/mysql/my.cnf_ add:
```
  [mysqld] 
  local-infile = 1
  [mysql] 
  local-infile = 1
```
Make the following changes to the php.ini file found in either _/etc/php.ini_ or _/etc/php5/apache2/php.ini_
```
  max_execution_time=300
  session.gc_maxlifetime=2600000 
  date.timezone="<your time zone>"
  short_open_tag = On
  upload_max_filesize=30M
  post_max_size=30M
```
Timeout of 5 minutes is desirable for some data import functions. Session timeout 30 days avoids having to login again every time you open a database unless it has been unused for a month. Upload and Post maximum sizes have a 2M default so we suggest increasing to 30M; the max size can be increased further, but it is better to use the multi-file uploader for very large files as it uploads in chunks and handles errors in transmission. _Short\_open\_tag_ allows the use of <?= tags in the code. Setting date and time zone explicitely avoids lots of messages in the error log if this is incorrectly set by the system.

Once the pre-requisites are loaded, simply run the Heurist install script and you're ready to roll.


## Updating versions (manually) ##

  * Download the new version from http://heurist.sydney.edu.au/HEURIST/DISTRIBUTION/xxxxx.tar.bz2 (where xxxxx is the version)
  * Make a new directory for the new version at the same level as the existing version(s)
  * Untar the distribution download into this new directory
  * Add simlinks to external, ext4, help and exemplars in the root of the codebase
  * We recommend using a heuristConfigIni.php file (prototype in the root of the Heurist distribution) placed in the Heurist parent directory (eg. /var/www/html/HEURIST) to override individual settings (see directory\_structure.txt file in Heurist root directory for full instructions). If you do not use this file you will need to edit configIni.php for each individual installed version (these can also be used to override settings for individual instances).
  * Run Heurist from the new directory
  * Delete old version and rename new version when satisfied that the new one is operating correctly

All minor version updates operate on a compatible database structure, so one can switch to and fro between versions freely without problems, accessing the same database in each case. Thus it is OK to operate on an alpha version and switch back to a beta or production version if a problem is encountered. Heurist version 4.0 is also forwards and backwards compatible with versions since 3.0

## Gotchas ##

Symptom: when you save a record, it writes out the record (the date and title change) but all the changes to data values disappear. This occurs when migrating from old (3.0.x) to newer versions due to replacement of platform-specific stored procedures with cross-platform procedures. To fix, simply run /admin/setup/dbcreate/addProceduresTriggers.sql


