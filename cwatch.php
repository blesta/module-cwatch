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
class cwatch extends Module 
{

    /**
     * Initialize the Module.
     */
    public function __construct() 
    {
        // Load components required by this module
        Loader::loadComponents($this, array('Record', 'Input'));

        // Load the language required by this module
        Language::loadLang('cwatch', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load product configuration required by this module
        Configure::load('cwatch', dirname(__FILE__) . DS . 'config' . DS);
    }

    /**
     * Initialize the cWatch API
     */
    private function loadApi($user, $pass, $mode) 
    {
        Loader::load(dirname(__FILE__) . DS . 'api' . DS . 'CwatchApi.php');
        $this->api = new APIController($user, $pass, $mode);
        $this->params = array();
    }

    /**
     * Returns the rendered view of the manage module page
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manager module
     *  page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars) 
    {
        $view = $this->getView('manage');
        Loader::loadHelpers($view, array('Form', 'Html', 'Widget'));

        $view->set('module', $module);
        $view->set('vars', (object) $vars);

        return $view->fetch();
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function manageAddRow(array &$vars) 
    {
        $view = $this->getView('add_row');
        Loader::loadHelpers($view, array('Form', 'Html', 'Widget'));

        $view->set('vars', (object) $vars);
        return $view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the edit
     *  module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars) 
    {
        $view = $this->getView('edit_row');
        Loader::loadHelpers($view, array('Form', 'Html', 'Widget'));

        if (empty($vars)){
            $vars = $module_row->meta;
        }

        $view->set('vars', (object) $vars);
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

    /**
     * Returns an array of available service deligation order methods. The module
     * will determine how each method is defined. For example, the method 'first'
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value paris where the key is
     *  the type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function selectModuleRow($module_group_id) 
    {
        if (!isset($this->ModuleManager))
            Loader::loadModels($this, array('ModuleManager'));

        $group = $this->ModuleManager->getGroup($module_group_id);
        if ($group){
            switch ($group->add_order) {
                default:
                case 'first':
                    foreach ($group->rows as $row){
                        return $row->id;
                    }
                    break;
            }
        }

        return 0;
    }

    private function getModuleRowByApi($module_row, $module_group = '') 
    {
        $row = null;
        if ($module_group == ''){
            if ($module_row > 0){
                $row = $this->getModuleRow($module_row);
            }else{
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

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to
     *  render as well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null) 
    {
        Loader::loadHelpers($this, array('Form', 'Html'));

        $module = $this->getModuleRowByApi((isset($vars->module_row) ? $vars->module_row : 0), (isset($vars->module_group) ? $vars->module_group : ''));

        $fields = new ModuleFields();

        if ($module) {
            $products = Configure::get('cwatch.products');
            $type = $fields->label(Language::_('CWatch.add_product.license_type', true), 'license_type');
            $type->attach($fields->fieldSelect('meta[cwatch_license_type]', $products, $this->Html->ifSet($vars->meta['cwatch_license_type']), array('id' => 'license_type')));
            $fields->setField($type);
            unset($type);
            $term = Configure::get('cwatch.terms');
            $terms = $fields->label(Language::_('CWatch.add_product.license_term', true), 'license_term');
            $terms->attach($fields->fieldSelect('meta[cwatch_license_term]', $term, $this->Html->ifSet($vars->meta['cwatch_license_term']), array('id' => 'license_term')));
            $fields->setField($terms);
            unset($terms);

            $sandbox_label = $fields->label('Sandbox', 'sandbox');
            $field_sandbox = $fields->label('Enable Sandbox', 'sandbox');
            $sandbox_label->attach($fields->fieldCheckbox('meta[cwatch_sandbox]', 'true', (isset($vars->meta['cwatch_sandbox']) && $vars->meta['cwatch_sandbox'] == 'true'), array('id' => 'cwatch_sandbox'), $sandbox_label));
            $fields->setField($sandbox_label);

            return $fields;
        }

        return $fields;
    }

    /**
     * Returns an array of key values for fields stored for a module, package,
     * and service under this module, used to substitute those keys with their
     * actual module, package, or service meta values in related emails.
     *
     * @return array A multi-dimensional array of key/value pairs where each key is
     *  one of 'module', 'package', or 'service' and each value is a numerically
     *  indexed array of key values that match meta fields under that category.
     * @see Modules::addModuleRow()
     * @see Modules::editModuleRow()
     * @see Modules::addPackage()
     * @see Modules::editPackage()
     * @see Modules::addService()
     * @see Modules::editService()
     */
    public function getEmailTags() 
    {
        return 
        [
            'module' => [],
            'package' => ['type', 'package', 'acl'],
            'service' => ['licensekey', 'cwatch_license_type', 'cwatch_license_term']
        ];
    }

    /**
     * Adds the service to the remote server. Sets Input errors on failure,
     * preventing the service from being added.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being added (if the current service is an addon service
     *  service and parent service has already been provisioned)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addService($package, array $vars = null, $parent_package = null, $parent_service = null, $status = 'pending') 
    {
        $row = $this->getModuleRow();
        if (isset($row->meta->username)) {
            $user = $row->meta->username;
        }
        if (isset($row->meta->password)) {
            $pass = $row->meta->password;
        }
        if (isset($package->meta->cwatch_license_type)) {
            $product = $package->meta->cwatch_license_type;
        }
        if (isset($package->meta->cwatch_license_term)){
            $term = $package->meta->cwatch_license_term;
        }
        if (isset($package->meta->cwatch_sandbox)){
            $sandbox = $package->meta->cwatch_sandbox;
        }
        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            Loader::loadModels($this, ['Clients']);
            $client = $this->Clients->get($vars['client_id'], false);
            $firstname = $client->first_name;
            $lastname = $client->last_name;
            $email = $client->email;
            $country = $client->country;
            $this->loadApi($user, $pass, $sandbox);
            try{
                $response = $this->api->createlicence($email, $firstname, $lastname, $country, $product, $term);
                $json = json_decode($response->resp);
                if ($response->code != 200) {
                    $this->Input->setErrors(['api' => ['internal' => $response->errorMsg]]);
                }
                $licensekey = $json->distributionResult[0]->licenseKeys[0];
            } catch (exception $e){
                $this->Input->setErrors(['api' => ['internal' => $e]]);
            }
            $this->log('createcommand', serialize($response), 'output', true);
            // Return on error
            if ($this->Input->errors())
                return;
        }
        return 
        [
            [
                'key' => 'licensekey',
                'value' => $licensekey,
                'encrypted' => 0
            ],
            [
                'key' => 'number_sites',
                'value' => $vars['configoptions']['number_of_sites'],
                'encrypted' => 0
            ]
        ];
    }

    /**
     * Edits the service on the remote server. Sets Input errors on failure,
     * preventing the service from being edited.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being edited (if the current service is an addon service)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editService($package, $service, array $vars = [], $parent_package = null, $parent_service = null)
    {
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $licensekey = $service_fields->licensekey;        
        return 
        [
            [
                'key' => 'licensekey',
                'value' => $licensekey,
                'encrypted' => 0
            ],
            [
                'key' => 'number_sites',
                'value' => $vars['configoptions']['number_of_sites'],
                'encrypted' => 0
            ]
        ];
    }
    /**
     * Suspends the service on the remote server. Sets Input errors on failure,
     * preventing the service from being suspended.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being suspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function suspendService($package, $service, $parent_package = null, $parent_service = null) 
    {
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $licensekey = $service_fields->licensekey;
        $row = $this->getModuleRow();
        if (isset($row->meta->username)) {
            $user = $row->meta->username;
        }
        if (isset($row->meta->password)) {
            $pass = $row->meta->password;
        }
        if (isset($package->meta->cwatch_sandbox)){
            $sandbox = $package->meta->cwatch_sandbox;
        }
        $this->loadApi($user, $pass, $sandbox);
        try {
            if (empty($licensekey)){
                $this->Input->setErrors(['api' => ['internal' => 'License Key not found.']]);
            } else {
                $response = $this->api->deactivatelicense($licensekey);
                $json = json_decode($response->resp);
                if ($response->success != 1){
                    $this->Input->setErrors(['api' => ['internal' => $json[0]->message]]);
                }
            }
            $this->log('suspendService', serialize($response), 'output', true);
        } 
        catch (exception $e){
            $this->Input->setErrors(['api' => ['internal' => $e->getMessage()]]);
        }
        // Return on error
        if ($this->Input->errors())
            return;

        return null;
    }

    /**
     * Cancels the service on the remote server. Sets Input errors on failure,
     * preventing the service from being canceled.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being canceled (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancelService($package, $service, $parent_package = null, $parent_service = null) 
    {
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $licensekey = $service_fields->licensekey;
        $row = $this->getModuleRow();
        if (isset($row->meta->username)) {
            $user = $row->meta->username;
        }
        if (isset($row->meta->password)) {
            $pass = $row->meta->password;
        }
        if (isset($package->meta->cwatch_sandbox)) {
            $sandbox = $package->meta->cwatch_sandbox;
        }
        $this->loadApi($user, $pass, $sandbox);
        try {
            $response = $this->api->deactivatelicense($licensekey);
            $json = json_decode($response->resp);
            //print_r($json); exit();
            if ($response->success != 1){
                $this->Input->setErrors(['api' => ['internal' => $json[0]->message]]);
            }
            $this->log('cancelService', serialize($response), 'output', true);
        }catch (exception $e){
            $this->Input->setErrors(['api' => ['internal' => $e]]);
        }
        // Return on error
        if ($this->Input->errors())
            return;

        return null;
    }

    /**
     * Client tab (add client/add malware scanner and view status)
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function getClientTabs($package) 
    {
        return [
            'tabClientActions' => Language::_('Cwatch.tab_client_actions', true),
            'tabClientMalWare' => Language::_('Cwatch.site.malware', true)
        ];
    }
    /**
     * Client Actions (Add site for scanner)
     *
     * @return string The string representing the contents of this tab
     */
    function tabClientMalWare($package, $service, array $get = null, array $post = null, array $files = null) 
    {
        $this->view = new View('tab_malware', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, array('Form', 'Html'));
        $row = $this->getModuleRow();
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $licensekey = $service_fields->licensekey;
        $user = $row->meta->username;
        $pass = $row->meta->password;
        $sandbox = $package->meta->cwatch_sandbox;
        $product = $package->meta->cwatch_license_type;
        $term = $package->meta->cwatch_license_term;
        $this->loadApi($user, $pass, $sandbox);
        if (!empty($post)){
            if($post['actionname'] == 'checkstatus'){
                $sites = $this->api->getScanner($post['domainname']);
            }else{
                $sites = $this->api->addScanner(['domain' => $post['domainname'], 'password' => $post['password'], 'username' => $post['username'], 'host' => $post['host'], 'port' => $post['port'],'path'=>$post['path']]);
            }
            if (!empty($sites->errorMsg)) {
                $this->Input->setErrors(['api' => ['internal' => $sites->errorMsg]]);
            }
        }
        $this->view->set('service', $service);
        $this->view->set('service_id', $service->id);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cwatch' . DS);
        return $this->view->fetch();
    }
    /**
     * Client Actions (Manage Site)
     *
     * @return string The string representing the contents of this tab
     */
    public function tabClientActions($package, $service, array $get = null, array $post = null, array $files = null) 
    {
        $this->view = new View('tab_site', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, array('Form', 'Html'));
        $row = $this->getModuleRow();
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $licensekey = $service_fields->licensekey;
        if (isset($row->meta->username)) {
            $user = $row->meta->username;
        }
        if (isset($row->meta->password)) {
            $pass = $row->meta->password;
        }
        if (isset($package->meta->cwatch_sandbox)) {
            $sandbox = $package->meta->cwatch_sandbox;
        }
        if (isset($package->meta->cwatch_license_type)) {
            $product = $package->meta->cwatch_license_type;
        }
        if (isset($package->meta->cwatch_license_term)){
            $term = $package->meta->cwatch_license_term;
        }
        Loader::loadModels($this, ['Clients']);
        $client = $this->Clients->get($service->client_id, false);
        $email = $client->email;
        $this->loadApi($user, $pass, $sandbox);
        $sitesdata = $this->api->getsites($email);
        $usedsites=json_decode($sitesdata->resp,true);
        if (!empty($post)){
            $sitecount=0;
            foreach($usedsites as $site){
                if($site->licenseKey == $licensekey){
                    $sitecount +=1;
                }
            }
            if($sitecount<$service_fields->number_sites || $service_fields->number_sites == 0){
                $initiateDns = $post['initiateDns'] == 1 ? true : false;
                $autoSsl = $post['autoSsl'] == 1 ? true : false;
                $sites = $this->api->addsite(['email' => $email, 'domain' => $post['domainname'], 'licenseKey' => $licensekey, 'initiateDns' => $initiateDns, 'autoSsl' => $autossl]);
                if (!empty($sites->errorMsg)) {
                    $this->Input->setErrors(['api' => ['internal' => $sites->errorMsg]]);
                }
            }else{
                $this->Input->setErrors(['api' => ['internal' => Language::_('Cwatch.site.notallow', true)]]);
            }
        }
        $this->view->set('sites_data', json_decode($sitesdata->resp));
        $this->view->set('service', $service);
        $this->view->set('service_id', $service->id);
        $this->view->set('licenseid', $licensekey);
        $this->view->set('addsite', $get[2]);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cwatch' . DS);
        return $this->view->fetch();
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * client interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getClientServiceInfo($service, $package) 
    {
        $row = $this->getModuleRow();
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $licensekey = $service_fields->licensekey;
        if (isset($row->meta->username)) {
            $user = $row->meta->username;
        }
        if (isset($row->meta->password)) {
            $pass = $row->meta->password;
        }
        if (isset($package->meta->cwatch_sandbox)){
            $sandbox = $package->meta->cwatch_sandbox;
        }
        $this->loadApi($user, $pass, $sandbox);
        $response = $this->api->getlicenseinfo($licensekey);
        $json = json_decode($response->resp);
        if ($response->code != 200) {
            $this->Input->setErrors(['api' => ['internal' => $response->errorMsg]]);
        }
        // Load the view (admin_service_info.pdt) into this object, so helpers can be automatically added to the view
        $this->view = new View('client_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cwatch' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, array('Form', 'Html'));

        $this->view->set('module_row', $row);
        $this->view->set('package', $json);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));
        $this->log('viewinfo', serialize($response), 'output', true);
        return $this->view->fetch();
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * admin interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getAdminServiceInfo($service, $package) 
    {
        $row = $this->getModuleRow();
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $licensekey = $service_fields->licensekey;
        if (isset($row->meta->username)){
            $user = $row->meta->username;
        }
        if (isset($row->meta->password)) {
            $pass = $row->meta->password;
        }
        if (isset($package->meta->cwatch_sandbox)){
            $sandbox = $package->meta->cwatch_sandbox;
        }
        $this->loadApi($user, $pass, $sandbox);
        $response = $this->api->getlicenseinfo($licensekey);
        $json = json_decode($response->resp);
        if ($response->code != 200){
            $this->Input->setErrors(['api' => ['internal' => $response->errorMsg]]);
        }
        // Load the view (admin_service_info.pdt) into this object, so helpers can be automatically added to the view
        $this->view = new View('admin_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cwatch' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, array('Form', 'Html'));

        $this->view->set('module_row', $row);
        $this->view->set('package', $json);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));
        $this->log('viewinfo', serialize($response), 'output', true);
        return $this->view->fetch();
    }

}