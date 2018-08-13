<?php
/**
 * cWatch Module.
 *
 * @package blesta
 * @subpackage blesta.components.modules.cwatch
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://blesta.com/license/ The Blesta License Agreement
 * @link http://blesta.com/ Blesta
 */

class cwatch extends Module {        
    /**
     * Initialize the Module.
     */
    public function __construct() 
    {
	// Load components required by this module
        Loader::loadComponents($this, array("Record", "Input"));

	// Load the language required by this module
        Language::loadLang("cwatch", null, dirname(__FILE__) . DS . "language" . DS);

	// Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . "config.json");
    }

    /**
     * Initialize the cWatch API
     */
    private function loadApi($user, $pass) 
    {
        $this->api = new APIController($user, $pass);
        $this->params = array();
    }  
    
    public function install() 
    {
	// No Logic
    }
    
    public function uninstall($module_id, $last_instance) 
    {
	// No Logic
    }      

    public function upgrade($current_version) 
    {
	// No Logic
    }

    public function manageModule($module, array &$vars)
    {
        $view = $this->getView('manage');
        Loader::loadHelpers($view, array('Form', 'Html', 'Widget'));

        $view->set('module', $module);
        $view->set('vars', (object)$vars);

        return $view->fetch();
    }

    public function manageAddRow(array &$vars)
    {
        $view = $this->getView('add_row');
        Loader::loadHelpers($view, array('Form', 'Html', 'Widget'));

        $view->set('vars', (object)$vars);
        return $view->fetch();
    }

    public function manageEditRow($module_row, array &$vars)
    {
        $view = $this->getView('edit_row');
        Loader::loadHelpers($view, array('Form', 'Html', 'Widget'));

        if (empty($vars)) {
            $vars = $module_row->meta;
        }

        $view->set('vars', (object)$vars);
        return $view->fetch();
    }

   /**
     * Load the view
     *
     * @param string $view
     * @return \View
     */
    protected function getView($view)
    {
        $viewObj = new View($view, 'default');
        $viewObj->base_uri = $this->base_uri;
        $viewObj->setDefaultView(
            'components' . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'cwatch' . DIRECTORY_SEPARATOR
        );

        return $viewObj;
    }

}
?>
