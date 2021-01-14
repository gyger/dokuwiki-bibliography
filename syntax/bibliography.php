<?php
/**
 * DokuWiki Plugin bibliography (Syntax Component)
 * 
 * Provides the bibliography block for the component.
 *
 * @license MIT
 * @author  Samuel Gyger <samuel@gyger.tech>
 */

use dokuwiki\plugin\bibliography\meta as Plugin;


class syntax_plugin_bibliography_bibliography extends \DokuWiki_Syntax_Plugin
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
        return 'block';
    }

    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort()
    {
        return 150;
    }

    /**
     * Connect lookup pattern to lexer. This plugin supports \bibliography{} syntax.
     * FIXME: Up to know it can only show references, that have been already in the text, not sure how to fix this.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode)
    {
      $this->Lexer->addSpecialPattern('\\\bibliography\b{*?}', $mode, 'plugin_bibliography_bibliography');
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
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {   
        $render_data = array();

        if (null == $this->bibliography) {
            try {
                $this->bibliography = Plugin\Bibliography::getInstance($this->getConf('citation-style'));
            } catch(Plugin\BibliographyException $e) {
                msg(hsc($e->getMessage()), -1);
            }
        }
        $render_data['css'] = $this->bibliography->css_styles;
        $render_data['bibliography'] = $this->bibliography->getBibliography();

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
        if ($mode !== 'xhtml') {
            return false;
        }

        $csshtml = '<style type="text/css">'.DOKU_LF.'<!-- ';
        $csshtml .= $data['css'];
        $csshtml .= ' -->'.DOKU_LF.'</style>'.DOKU_LF;

        $renderer->doc .= $csshtml . $data['bibliography'];
        return true;
    }
}

