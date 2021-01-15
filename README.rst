bibliography Plugin for DokuWiki
================================
Cite your informations in your dokuwiki based on different citation providers.

Supported Citation providers are:
 - Zotero Web API

The project is still in the development phase, **so better do not use it now**.

**MUST:** What to implement before a useful release:

 - Configuration interface im Admin area for Datasource settings.  
   Not sure what to make editable. It makes no sense to change the collection, for this one should add a new source.  
 - Treat errors from API.  
 - CLI tool for loading or forced updating.  

**TODO:** What to implement in the future.  

 - Use backoff time setting in the CLI or Admin tool.  
 - Make it possible to get a mouse over with the information on the citation.  
 - Some CSL styles show the note field. Perhaps remove Betterbibtex line.  
 - Make the bibliography know all the fields in the text, even if it is late.  
 - If import would be larger than 100 items, suggest to do it in the admin panel. (Only to logged in users of course).

How to use it
=============
The software supports two commands.
::
  \cite{} and \bibliography{}
The Zotero Citation Provider works together with the BetterBibTex Plugin. One can only cite pinned Citekeys in your wiki.

Because there is no working config backend yet, one needs to add the connection manually to the sqlite database. You need to create a new API Key, best to make it read-only so we can not mess up your Zotero Database. Then through the SQLite Interface in dokuwiki you can add the connection.
::
  INSERT INTO datasources (source_name, dataprovider_type, access_data) values ('zotero_link', 'zotero', '{
      "api_key" : "key",
      "library_id" : 000000,
      "library_is_group" : true
  }')

::
  INSERT INTO datasources (source_name, dataprovider_type, access_data) values ('zotero_link', 'zotero', '{
      "api_key" : "key",
      "library_id" : 00000000,
      "library_is_group" : false,
      "collection_id": "collectionid"
  }')

All documentation for this plugin can be found at
https://samuel.gyger.at/projects/bibliography (in the future)

If you install this plugin manually, make sure it is installed in
lib/plugins/bibliography/ - if the folder is called different it
will not work!

Please refer to http://www.dokuwiki.org/plugins for additional info
on how to install plugins in DokuWiki.

Libraries
=========

- `citation-style-language <https://github.com/citation-style-language>`_
- `citeproc-php <https://github.com/seboettg/citeproc-php>`_
- `hedii/zotero-api <https://github.com/hedii/zotero-api/>`_

Available Alternatives
======================

- `plugin:zotero <https://www.dokuwiki.org/plugin:zotero>`_
- `plugin:refnotes <https://www.dokuwiki.org/plugin:refnotes>`_

I looked into this plugins to get inspiration and lern how to write plugins. Without the struct plugin I would have been lost.
