<?php

namespace dokuwiki\plugin\bibliography\meta\data;

use dokuwiki\plugin\bibliography\meta\data\zotero\ZoteroDataProvider as ZoteroDataProvider;
use \DateInterval;
use \DateTime;

/**
 * Uses enabled DataProvider for Bibliography entries.
 */
class DataLoader{

  protected $provider = array();

  public function __construct($backoff_time='1 second'){
    $db = Library::getInstance()->sqlite;
    $result = $db->query("SELECT id, source_name, dataprovider_type, access_data, last_modified, last_updated ".
                         "FROM datasources WHERE enabled == TRUE"
                        );
                        
    $backoff_time = DateInterval::createFromDateString($backoff_time);

    while ($row = $db->res2row($result)) {
      $last_updated = new DateTime(($row['last_updated']) ? $row['last_updated'] : "@0");
      if ($last_updated->add($backoff_time) > new DateTime()) {continue;}

      switch ($row['dataprovider_type']) {
        case 'zotero':  $provider = new ZoteroDataProvider($row['id'], $row['access_data'], 
                                                           $row['last_modified'], $row['last_updated']);
                        break;
        default:        break; // Provider not supported.
      }
      if ($provider){
        $this->provider[$row['source_name']] = $provider;
      }
    }
  }

  /**
   * Looks up the reference Key in all the enabled @see DataProvider.
   * Stores the data in the local database and returns it.
   * @param $refKey The refKey for the entry in the database.
   * @return associative array with the resulting set. Returns NULL if no result was found.
   */
  public function lookupKey($refKey, $store_local=TRUE){
    foreach($this->provider as $provider_name => $provider) {
      $result = $provider->lookupKey($refKey);
      if($result) {
        return $result;
      }
    }
    return NULL;
  }
}
?>