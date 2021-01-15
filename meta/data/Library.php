<?php
namespace dokuwiki\plugin\bibliography\meta\data;

use dokuwiki\plugin\bibliography\meta as Plugin;

class Library {

  /**
   * @var SQlite helper from Dokuwiki plugin.
   */
  public $sqlite;

  /**
   * @var DataLoader
   */
  protected $data_loader = NULL;

  /**
   * @var Library
   */
  private static $instance = NULL;

  /**
   * Get current instance of the Library
   */
  public static function getInstance() {
    if(NULL == self::$instance) {
      self::$instance = new Library();
    }
    return self::$instance;
   }

  public function __construct(){
    $this->sqlite = plugin_load('helper', 'sqlite');

    if(!$this->sqlite) {
      throw new Plugin\BibliographyException('no sqlite');
    }
    
    if(!$this->sqlite->init('bibliography', DOKU_PLUGIN.'bibliography/db/')){
      return;
    }
  }
  
  /**
   * Returns the first entry found for the $refKey.
   * 
   * @param $refKey The refKey for the entry in the database.
   * @return associative array with the resulting set.  Only returns the first item found. Returns false if not found.
   */
  public function getEntry($refKey, $automatic_load=TRUE) {
    //FIXME Join and directly load last-modified
    $res = $this->sqlite->query("SELECT id, refKey, datasource_id, csl FROM items WHERE refkey == ?", $refKey);

    $entry = $this->sqlite->res2row($res);
    $this->sqlite->res_close($res);

    if (!$entry && $automatic_load) {
      if(NULL == $this->data_loader) {
        $this->data_loader = new DataLoader();
      }
      $entry = $this->data_loader->lookupKey($refKey);
    }
    return $entry;
  }

  public function sqlite_storeEntry($table, $entry) {
    $keys = join(',', array_keys($entry));
    $vals = join(',', array_fill(0,count($entry),'?'));

    $sql = "INSERT OR REPLACE INTO $table ($keys) VALUES ($vals)";
    return $this->sqlite->query($sql, array_values($entry));
  }

  public function sqlite_updateEntry($table, $entry, $where) {
    foreach ($entry as $key => $value) {
      $vals[] = "$key = ? \n";
    }
    $sql = "UPDATE $table SET " . join(',', $vals). " WHERE $where ";

    return $this->sqlite->query($sql, array_values($entry));
  }
}