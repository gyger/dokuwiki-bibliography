bibliography Plugin for DokuWiki
================================
Cite your informations in your dokuwiki based on different citation providers.

Supported Citation providers are:
 - Zotero Web API

The project is still in the development phase, so better do not use it know.

MUST: What to implement before a useful release:
 [ ] Configuration interface im Admin bereich for Datasource settings.
 [ ] Treat errors from API.
 [ ] CLI tool for loading or forced updating.
 [ ] Use backoff time setting.

TODO: What to implement in the future.
 [ ] Make it possible to get a mouse over with the information on the citation.
 [ ] Some CSL styles show the note field. Perhaps remove Betterbibtex line.
 [ ] Make the bibliography know all the fields in the text, even if it is late.
 [ ] If import would be larger than 100 items, suggest to do it in the admin panel. (Only to logged in users of course).

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
I looked into this plugins to get inspiration and lern how to write plugins.

- `plugin:zotero <https://www.dokuwiki.org/plugin:zotero>`_
- `plugin:refnotes <https://www.dokuwiki.org/plugin:refnotes>`_

----

Copyright (C) Samuel Gyger <samuel@gyger.tech>
