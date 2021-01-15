<?php
namespace dokuwiki\plugin\bibliography\meta\data;

use dokuwiki\Form\Form;


/**
 * Class SourceEditor
 *
 * Provides the editing interface for Bibliography Sources in the Backend.
 * 
 * This is obviously just copied from the Struct Plugin to big extend.
 *
 * @package dokuwiki\plugin\bibliography\meta
 */
class SourceEditor
{

    /** @var \DokuWiki_Plugin  */
    protected $db;

    /** @var \DokuWiki_Plugin  */
    protected $hlp;

    /**
     * SourceEditor constructor.
     */
    public function __construct()
    {
        $this->db = Library::getInstance()->sqlite;
        $this->hlp = plugin_load('helper', 'bibliography_config');
    }

    /**
     * Returns the Admin Form to edit the sources
     *
     * @return string the HTML for the editor form
     */
    public function getEditor()
    {
        $form = new Form(array('method' => 'POST', 'id' => 'plugin__bibliography_source_editor'));
        $form->setHiddenField('do', 'admin');
        $form->setHiddenField('page', 'bibliography_datasource');
        $form->addHTML('<table class="inline">');
        $form->addHTML("<tr>
            <th>{$this->hlp->getLang('source_type')}</th>
            <th>{$this->hlp->getLang('source_name')}</th>
            <th>{$this->hlp->getLang('source_conf')}</th>
            <th>{$this->hlp->getLang('source_enabled')}</th>
            <th>{$this->hlp->getLang('source_manage')}</th>
        </tr>");

        foreach (DataLoader::get_datasources() as $row) {
            $form->addHTML($this->adminSourceEntry($row['id'], 
                                                   $row['source_name'], 
                                                   $row['data_provider_type'], 
                                                   $row['access_data'],
                                                   $row['enabled'],
                                                   $row['last_modified'],
                                                   $row['last_updated']
                                                  ));
        }

        // FIXME new one needs to be added dynamically, this is just for testing
//        $form->addHTML($this->adminColumn('new1', new Column($this->schema->getMaxsort() + 10, new Text()), 'new'));

        $form->addHTML('</table>');

        $form->addButton('save', 'Save')->attr('type', 'submit');
        return $form->toHTML() . $this->initJSONEditor();
    }

    /**
     * Gives the code to attach the JSON editor to the config field
     *
     * We do not use the "normal" way, because this is rarely used code and there's no need to always load it.
     * @return string
     */
    protected function initJSONEditor()
    {
        $html = '';
        $html .= '<link href="' . DOKU_BASE . 'lib/plugins/bibliography/vendor/jsoneditor/jsoneditor.min.css" rel="stylesheet" type="text/css">';
        $html .= '<link href="' . DOKU_BASE . 'lib/plugins/bibliography/vendor/jsoneditor/setup.css" rel="stylesheet" type="text/css">';
        $html .= '<script src="' . DOKU_BASE . 'lib/plugins/bibliography/vendor/jsoneditor/jsoneditor-minimalist.min.js" defer="defer"></script>';
        $html .= '<script src="' . DOKU_BASE . 'lib/plugins/bibliography/vendor/jsoneditor/setup.js" defer="defer"></script>';
        return $html;
    }

    /**
     * Returns the HTML to edit a single data-source
     *
     * @param string $id
     * @param string $source_name
     * @param string $dataprovider_type
     * @param string $key The key to use in the form
     * @return string
     * @todo this should probably be reused for adding new columns via AJAX later?
     */
    protected function adminSourceEntry($id, $source_name, $dataprovider_type, $config, $enabled, $last_modified, $last_updated)
    {
        $base = 'source[' . $id . ']'; // base name for all fields

        $class = $enabled ? '' : 'disabled';

        $html = "<tr class=\"$class\">";


        $types = array_keys(DataLoader::$provider_types);
        $html .= '<td class="type">';
        $html .= '<select name="' . $base . '[dataprovider_type]">';
        foreach ($types as $type) {
            $selected = ($dataprovider_type == $type) ? 'selected="selected"' : '';
            $html .= '<option value="' . hsc($type) . '" ' . $selected . '>' . hsc($type) . '</option>';
        }
        $html .= '</select>';
        $html .= '</td>';

        $html .= '<td class="name">';
        $html .= '<input type="text" name="' . $base . '[source_name]" value="' . hsc($source_name) . '">';
        $html .= '</td>';

        $html .= '<td class="config">';
        #$config = json_encode($config, JSON_PRETTY_PRINT);
        $html .= '<textarea name="' . $base . '[config]" cols="45" rows="10" class="config">' . hsc($config) . '</textarea>';
        $html .= '</td>';

        $html .= '<td class="enable">';
        $checked = $enabled ? 'checked="checked"' : '';
        $html .= '<input type="checkbox" name="' . $base . '[enabled]" value="1" ' . $checked . '>';
        $html .= '</td>';

        $html .= '<td class="manage">';
        $html .= '</td>';

        $html .= '</tr>';

        return $html;
    }
}