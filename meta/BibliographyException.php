<?php

namespace dokuwiki\plugin\bibliography\meta;

/**
 * Class BibliographyException
 *
 * A translatable exception
 *
 * @package dokuwiki\plugin\bibliography\meta
 */
class BibliographyException extends \RuntimeException
{

    protected $trans_prefix = 'Exception ';

    /**
     * BibliographyException constructor.
     *
     * @param string $message
     * @param ...string $vars
     */
    public function __construct($message)
    {
        /** @var \helper_plugin_bibliography $plugin */
        $plugin = plugin_load('helper', 'bibliography_config');

        $trans = $plugin->getLang($this->trans_prefix . $message);
        if (!$trans) $trans = $message;

        $args = func_get_args();
        array_shift($args);

        $trans = vsprintf($trans, $args);

        parent::__construct($trans, -1, null);
    }
}