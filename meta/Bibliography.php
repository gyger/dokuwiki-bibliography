<?php

namespace dokuwiki\plugin\bibliography\meta;

require_once __DIR__ . '/../vendor/autoload.php';

use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

class Bibliography{
  
  public $library;

  public $citeproc;
  public $css_styles;
  public $cited_keys = array();
  public $cited_csl = array();

  private static $instance = array();

  /**
   * Get current instance of Bibliography
   * @param $page if set returns Bibliography for this key.
   */
  public static function getInstance($stylesheet="din-1505-2", $page='default') {
   if(!isset(self::$instance[$page])) {
     self::$instance[$page] = new Bibliography($stylesheet);
   }
   return self::$instance[$page];
  }

  public function __construct($stylesheet="din-1505-2"){
    $this->library = data\Library::getInstance();

    $style = StyleSheet::loadStyleSheet($stylesheet);
    $this->citeproc = new CiteProc($style, "en-US", $this->markup_format());
    $this->css_styles = $this->citeproc->renderCssStyles();
  }

  private function markup_format() {
    //Fixme the links should work for different citation styles.
    $additionalMarkup = [
      "bibliography" => [
          "csl-entry" => function($cslItem, $renderedText) {
              return '<a id="' . $cslItem->id .'" href="#' . $cslItem->id .'"></a>' . $renderedText;
          }
      ],
      "citation" => [
          "citation-number" => function($cslItem, $renderedText) {
              return '<a href="#' . $cslItem->id .'">'.$renderedText.'</a>';
          }
      ]
    ];
    return $additionalMarkup;
  }


  public function getCitation($refKey, $list=TRUE) {
    if (!$list || !in_array($refKey, $this->cited_keys)){
        $entry = $this->library->getEntry($refKey);
        if (!$entry) {
          return false;
        }

        $current_entry = json_decode($entry['csl']);
        if($list) {
          $this->cited_csl[] = $current_entry;
          $this->cited_keys[] = $refKey;
          $bibliography_array = $this->cited_csl;
        } else {
          $bibliography_array = array($current_entry);
        }
    }
    return $this->citeproc->render($bibliography_array, "citation", 
                                   json_decode("[{\"id\": \"$refKey\"}]"));
  }

  public function getBibliography() {
    return $this->citeproc->render($this->cited_csl, "bibliography");
  }
}

?>