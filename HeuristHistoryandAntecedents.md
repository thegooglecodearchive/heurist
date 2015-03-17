## A brief personal history of Heurist ##

### Ian Johnson, Senior Research Fellow, Director Arts eResearch ###

**April 2013**

I started designing Heurist in 2005 in the context of the SHSSERI partnership (Sydney Humanities & Social Sciences e-Research Initiative) of which I was a founding member and key contributor. However its origins go far further back to work I did on the Minark archaeological DBMS (1980 - 1987) and TimeMap (1997 onwards).

### Context ###

Humanities data is characterised by high heterogeneity, small data volumes, qualitative and textual information, and the importance of the connections between entities, rather than the large volumes of repetitive, standardised, quantitative data characteristic of the Sciences. Humanists need complex databases but have extremely limited resources for building them.

My vision for Heurist - originally styled the SHSSERI Collaborative Knowledge System – was therefore to build a user-configurable online database which would handle a wide variety of Humanities data and allow researchers to manipulate and share these data in ways adapted to the needs of Humanists. My vision was perhaps overly ambitious - although we have largely made good on it - prioritising methods which address the needs of researchers rather than item inventories and business use (the primary drivers of consumer-oriented databases).

At the time I was critically aware, from over three decades developing Humanities computing applications since 1972 (Johnson 1976), that a major issue was the fragmentation of information into format-specific software silos (bibliography, text files, images, spreadsheets, GIS, bookmarking, notes, blogs etc.) and the lack of integrated tools to manage heterogeneous and often complex interlinked data. The key need I saw, therefore, was a way for Humanists to create and maintain complex collaborative databases without the need to either cobble together solutions from many disparate tools or become programmers and reinvent the wheel. Web applications were the obvious medium for this development, an area in which I had prior experience through work on TimeMap (http://timemap.net) and the ECAI Clearinghouse (see later).

### History ###

I established the Archaeological Computing Laboratory (ACL) now Arts eResearch (AeR) in 1992 through a joint Large Equipment grant with Roland Fletcher. Initially with a staff of 0.5 FTE (myself) it currently has 5.5 FTE, of whom two are programmers. Heurist is the culmination of the last two decades of work, drawing on prior applications mentioned above (and prior work back to the early 1980s).

I built the prototype of Heurist, known as TMBookmarker, in August 2005 using the T1000 templating system (written by Tom Murtagh) and a web-based system for creating and structuring a database and writing T1000 templates to manage it (written by myself). From this prototype I started developing Heurist through specification of additional functions and requirements, which were developed by the Archaeological Computing Laboratory (hereafter ACL) programming staff.

However, the origins of Heurist go back much further to Minark (Johnson 1984), a microcomputer database I designed and wrote between 1980 – 1987. Minark was quite widely used for state site registers and excavation databases in Australia, as well as overseas, in the 1980s and early 1990s, before the rise of FileMaker and Microsoft Access made its text-based interface obsolete.

Many of the core ideas of Heurist date back to this system:

  * database structure stored within the database itself;
  * database structure configurable by the user through the interface;
  * change of record structure on the fly without loss of data;
  * flexible record structure allowing missing and repeating fields;
  * dynamic construction of data entry forms from structure definitions;
  * variable length free text;
  * enumerated and lookup fields;
  * enumerated lists extended on the fly;
  * import and export of CSV and self-documenting database dumps;
  * simple built-in user-configurable report formatting to screen, printer or disk;
  * simple built-in mapping.

The emphasis in Minark was to empower the end-user to build their own databases, and many did. In contrast, the contemporary market leader, dBase II and later III, required extensive technical programming to build an equivalent system.  This philosophy of user-configured databases and user empowerment is central to the design of Heurist, and particularly to the restructuring of the database configuration and user tables which I undertook in moving Heurist to Version 3, starting in 2009.

Additional research which has contributed to Heurist include: TimeMap (developed in collaboration with Artem Osmakov and his team from 1997 - 2005); the Electronic Cultural Atlas Initiative (ECAI) Clearinghouse (developed in-house from 1998 - 2001); FieldHelper (initially prototyped in-house (Vsn 0.1 - 0.4), but eventually contracted out and developed collaboratively to a practical, although unfinished due to lack of funding, system by Artem Osmakov (Vsn 2.0)).