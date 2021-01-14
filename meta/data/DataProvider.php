<?php

namespace dokuwiki\plugin\bibliography\meta\data;

abstract class DataProvider{

  public function __construct($source_id, $authentication_data, $last_modified=null, $last_updated=null){
  }

  public static $provider_name;

  public function lookupKey($refKey) { 
  }
}
?>