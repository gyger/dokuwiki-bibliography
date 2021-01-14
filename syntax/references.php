<?php
/**
 * DokuWiki Plugin bibliography (Syntax Component)
 *
 * @license MIT
 * @author  Samuel Gyger <samuel@gyger.tech>
 */

 use dokuwiki\plugin\bibliography\meta as Plugin;

class syntax_plugin_bibliography_references extends \DokuWiki_Syntax_Plugin
{
    /**
     * @var Plugin\Bibliography
     */
    private $bibliography = null;

    /**
     * @return string Syntax mode type
     */
    public function getType()
    {
        return 'substition';
    }

    /**
     * @return string Paragraph type
     */
    public function getPType()
    {
        return 'normal';
    }

    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort()
    {
        return 50;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('\[\(.*?\)\]', $mode, 'plugin_bibliography_references');
        $this->Lexer->addSpecialPattern('\\\cite.*?\}', $mode, 'plugin_bibliography_references');
    }

    protected function _addLinksorDOI($text) 
    {
        $text = preg_replace( '!((http(s)?://)[-a-zA-Z?-??-?()0-9@:%_+.~#?&;//=]+)!i', '<br> <a href="$1">$1</a>', $text );
        return $text;
    }

    /**
     * Handle matches of the bibliography syntax
     *
     * @param string       $match   The match of the syntax
     * @param int          $state   The state of the handler
     * @param int          $pos     The position in the document
     * @param Doku_Handler $handler The handler
     *
     * @return array Data for the renderer
     * 
     * @todo Needs to support citation of multiple keys.
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $render_data = array('error' => False);
        $data = array('reference_key' => null);

        $matches = array();
        if (preg_match("/\[\(([a-zA-Z0-9\-\.:]*?)(>>([a-zA-Z0-9 \.,\-:]*))?\)\]/", $match, $matches))
        {
            $data['reference_key'] = $matches[1];
            $data['notes']  = $matches[2];
        } elseif (preg_match("/\\\\cite(\[([a-zA-Z0-9 \.,\-:]*)\])?\\{([a-zA-Z0-9\\-:\.]*?)\\}/", $match, $matches))
        {
            $data['reference_key'] = $matches[3];
            $data['notes'] = $matches[2];
        }
        else
        {
            $render_data['error'] = True;
            $render_data['message'] = "invalid citation: " . $match;
            return $render_data;
        }
        if (null == $this->bibliography) {
            $this->bibliography = Plugin\Bibliography::getInstance($this->getConf('citation-style'));
        }
        list($citation, $bibliography) = $this->bibliography->getCitation($data['reference_key'], TRUE, TRUE);
        if(!$citation) {
          $render_data['error'] = True;
          $render_data['message'] = "unknown cite key: " . $citeKey;
          return $render_data;
        }
        
        $render_data['error'] = False;
        $render_data['message'] = $citeKey . " " . $notes;
        $render_data['citation'] = $citation;
        
        #FIXME Goal would be to link https:// urls.
        $render_data['inlineBibliography'] = $this->_addLinksorDOI(strip_tags($bibliography));

        return $render_data;
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string        $mode     Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer $renderer The renderer
     * @param array         $data     The data from the handler() function
     *
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($data === false) return false;
        if ($data['error']) {
            $renderer->doc .= $data['message'];
        }
        
        $renderer->doc .= $data['citation'];
        $renderer->doc .= '<span class="inlineBibliography">'.$data['inlineBibliography'].'</span>';
        return true;
    }
}

