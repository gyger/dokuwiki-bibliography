<?php

namespace dokuwiki\plugin\bibliography\meta\data\zotero;

use dokuwiki\plugin\bibliography\meta\data\DataProvider as DataProvider;
use dokuwiki\plugin\bibliography\meta\data\Library as Library;

/** 
 * ZoteroDataProvider keeps the SQLite database provided through Bibliography in sync with the Zotero Web API.
 * 
 * The reference keys are BetterBibTex keys and need to be pinned to be usable. Perhaps this can be relaxed in a future
 * version.
 */
class ZoteroDataProvider extends DataProvider{

  public static $provider_name = "Zotero";

  /**
   * @var int
   */  
  protected $source_id;

  /**
   * The data version of the local data.
   * @var int
   */
  protected $version;

  /**
   * @var ZoteroApi
   */
  private $zotero_api;

  /**
   * A user or group id defining the library that is accessed here.
   *
   * @var int
   */
  private $library_id;

  private $is_group = FALSE;

  /**
   * If set, only items within the collection are added to the local database.
   *
   * @var int
   */
  private $collection_id = NULL;

  /**
   * 
   */
  public function __construct($source_id, $authentication_data, $last_modified=NULL, $last_updated=NULL){ 
    $this->source_id = $source_id;
    $authentication_data_array = json_decode($authentication_data, TRUE);

    if(!$authentication_data_array) {
      msg("Zotero: The authentication data in the database cannot be parsed. ".$authentication_data, -1);
      return;
    }

    $this->zotero_api = new ZoteroApi($authentication_data_array['api_key']);
    $this->library_id = $authentication_data_array['library_id'];
    
    $this->is_group = (!empty($authentication_data_array['library_is_group'])) ? $authentication_data_array['library_is_group'] : FALSE;
    
    $this->version = $last_modified ? $last_modified : 0;

    $this->collection_id = (!empty($authentication_data_array['collection_id'])) ? $authentication_data_array['collection_id'] : NULL;
  }

  /**
   * Zotero does not expose Bibtex keys, so it has to update the whole database if the key is not found.
   * This update is incremental and if not a lot has changed in the database fast.
   * 
   * If there was an update, another chance is given to Bibliography to find the key (without creating another update request.)
   * 
   * Stores the data in the local database and returns it.
   * @param $refKey The refKey for the entry in the database.
   * @return associative array with the resulting set. Returns false if no result was found.
   */
  public function lookupKey($refKey){
    if (!$this->zotero_api) return false;

    return ($this->updateDatasource()) ? Library::getInstance()->getEntry($refKey, False) : false;
  }

  /**
   * Update the datasource to the state of the web
   * 
   * Stores the data in the local database and returns it.
   * @param $refKey The refKey for the entry in the database.
   * @param $store_local TRUE stores the fetched data in the database. If not, this should be run on a super small Zotero Library.
   * @return associative array with the resulting set. Returns false if no result was found.
   */
  public function updateDatasource() {
    $changed_keys = $this->getModifiedItems();
    if (!$changed_keys) { return FALSE; }

    $database_version = $this->updateItems($changed_keys);
    
    $changed = $database_version > $this->version;
    
    $source_data['last_modified'] = $database_version;
    $source_data['last_updated'] = date('c');

    Library::getInstance()->sqlite_updateEntry('datasources', $source_data, "id == $this->source_id");
    $this->version = $database_version;

    return $changed;
  }

  /**
   * Updates or inserts the items given as keys through the Zotero Web API.
   * 
   * @param $keys The refKey for the entry in the database.
   */
  public function updateItems($keys) {
    $current_database_version = $this->version;

    foreach (array_chunk($keys, 50) as $chunk) {
      $call = ($this->is_group) ? $this->zotero_api->group($this->library_id) : $this->zotero_api->user($this->library_id);
      $response = $call->items($chunk)->setFormat('json')->include('csljson')->send();

      if(!$response->response) {msg("Error asking for items. ".$response->response->error); return false;}

      $items = $response->getBody();

      $current_database_version = $response->getHeaders()['last-modified-version'];

      foreach ($items as $item) {
        $data_item = array();
        $matchingKey = array();
        //Extra field should contain: Citation Key: [your citekey]
        preg_match('/\X*(?i)Citation key:(?-i)(\V+)/', $item['csljson']['note'], $matchingKey);

        //We use the pinned BetterBibLatex, otherwise we only store the version, not the whole dataset, as it can not be referenced.
        $refKey = trim($matchingKey[1]);
        if(!empty($refKey)){
          $item['csljson']['id'] = $refKey;
          $data_item['refKey'] = $refKey;
          $data_item['csl'] = json_encode($item['csljson']);
        }

        $data_item['datasource_id'] = $this->source_id;
        $data_item['datasource_item_id'] = $item['key'];  
        $data_item['last_modified'] = $item['version'];

        Library::getInstance()->sqlite_storeEntry('items', $data_item); # Replaces updated items.
      }
    }

    return $current_database_version;
  }

  /**
   * Gets items that need to be updated for the current database version.
   * 
   * Stores the data in the local database and returns it.
   * @param $refKey The refKey for the entry in the database.
   * @param $store_local TRUE stores the fetched data in the database. If not, this should be run on a super small Zotero Library.
   * @return associative array with the resulting set. Returns NULL if no result was found.
   */
  private function getModifiedItems() {
    if ($this->is_group) {
      $call = $this->zotero_api->group($this->library_id);
    } else {
      $call = $this->zotero_api->user($this->library_id);
    }
    
    if ($this->collection_id) {
      $call = $call->collections($this->collection_id);
    }
    
    $response = $call->items()->top()->versions()->since($this->version)->send();
    if(!$response->response) {
      msg("Error getting updated items. Status Code: ".$response->response->status." Error ".$response->response->error." Request URL: ".$response->getPath(), -1); 
      return FALSE;
    }

    $updated_items = $response->getBody();

    $db = Library::getInstance()->sqlite;
    $result = $db->query("SELECT last_modified, datasource_item_id ".
                         "FROM items WHERE datasource_id == ? AND datasource_item_id IN(".
                         $db->quote_and_join(array_keys($updated_items), $sep=',').
                         ")",
                         $this->source_id);

    while ($row = $db->res2row($result)) {
      if ($updated_items[$row['datasource_item_id']] == $row['last_modified']) {
        unset($updated_items[$row['datasource_item_id']]); # Remove up-to-date version
      }
    }

    $db->res_close($result);
    return array_keys($updated_items);
  }
}
?>