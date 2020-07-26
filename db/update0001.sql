-- Bibliography Database based on CSL data.
-- Version 1

CREATE TABLE datasources (id INTEGER PRIMARY KEY AUTOINCREMENT,
                          source_name TEXT UNIQUE,
                          dataprovider_type TEXT,
                          enabled BOOLEAN DEFAULT(TRUE),
                          access_data TEXT,
                          last_modified TEXT,
                          last_updated TEXT);

CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, 
                    refKey TEXT UNIQUE,
                    datasource_id TEXT,
                    datasource_item_id TEXT,
                    csl TEXT,
                    last_modified TEXT,
                    UNIQUE(datasource_id, datasource_item_id)
                    );