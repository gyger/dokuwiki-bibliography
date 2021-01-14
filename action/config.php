<?php

/**
 * DokuWiki Plugin Bibliography (Action Component)
 * This is based on the struct plugin.
 * @license MIT
 * @author  Samuel Gyger
 */

 class action_plugin_struct_config extends DokuWiki_Action_Plugin
{

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handleAjax');
    }

    /**
     * Reconfigure configuration for given data source
     *
     * @param Doku_Event $event event object by reference
     * @param mixed $param [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     */
    public function handleAjax(Doku_Event $event, $param)
    {
        if ($event->data != 'plugin_bibliography_config') return;
        
        $event->preventDefault();
        $event->stopPropagation();
        global $INPUT;

        $conf = json_decode($INPUT->str('conf'), true);
        $typeclasses = Column::allTypes();
        $class = $typeclasses[$INPUT->str('type', 'Text')];
        /** @var AbstractBaseType $type */
        $type = new $class($conf);

        header('Content-Type: text/plain'); // we need the encoded string, not decoded by jQuery
        echo json_encode($type->getConfig());
    }
}