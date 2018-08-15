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

	// Load product configuration required by this module
        Configure::load('cwatch', dirname(__FILE__).DS.'config'.DS);
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
    
    public function selectModuleRow($module_group_id) 
    {
	if (!isset($this->ModuleManager))
	    Loader::loadModels($this, array("ModuleManager"));
	
	$group = $this->ModuleManager->getGroup($module_group_id);
	
	if ($group) {
	    switch ($group->add_order) {
		default:
		case "first":
		    foreach ($group->rows as $row) {
			return $row->id;
		    }
		break;
	    }
	}
	
        return 0;
    }
    
    private function getModuleRowByApi($module_row, $module_group = "") 
    {
        $row = null;
	    
	if ($module_group == "") {
	    if ($module_row > 0) {
	        $row = $this->getModuleRow($module_row);
	    } else {
		$rows = $this->getModuleRows();
   
		if (isset($rows[0]))
	            $row = $rows[0];
		    unset($rows);
	    }
	} else {
		$rows = $this->getModuleRows($module_group);

		if (isset($rows[0]))
                    $row = $rows[0];
		    unset($rows);
	}
		
	return $row;
    }
        
    public function getPackageFields($vars=null)
    {
        Loader::loadHelpers($this, array("Form", "Html"));
        
        $module = $this->getModuleRowByApi((isset($vars->module_row) ? $vars->module_row : 0), (isset($vars->module_group) ? $vars->module_group : ""));
        
        $fields = new ModuleFields();
        
        if ($module)
	{
    	    $products = Configure::get('cwatch.products');
            $type = $fields->label(Language::_('CWatch.add_product.license_type', true), "license_type");
            $type->attach($fields->fieldSelect("meta[cwatch_license_type]", $products, $this->Html->ifSet($vars->meta['cwatch_license_type']), array("id" => "license_type")));
            $fields->setField($type);
            unset($type);
        }
            
        return $fields;
    }
    
    public function getEmailTags()
    {
        return array(
            'module' => array(),
            'package' => array(),
            'service' => array("cwatch_license")
        );
    }
}
?>
