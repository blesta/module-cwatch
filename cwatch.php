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
//ini_set('display_errors', true);
//error_reporting(E_ALL);

class cwatch extends Module {

    /**
     * Initialize the Module.
     */
    public function __construct() {
        // Load components required by this module
        Loader::loadComponents($this, array("Record", "Input"));

        // Load the language required by this module
        Language::loadLang("cwatch", null, dirname(__FILE__) . DS . "language" . DS);

        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . "config.json");

        // Load product configuration required by this module
        Configure::load('cwatch', dirname(__FILE__) . DS . 'config' . DS);
    }

    /**
     * Initialize the cWatch API
     */
    private function loadApi($user, $pass, $mode) {
        Loader::load(dirname(__FILE__) . DS . 'api' . DS . 'cwatch_api.php');
        $this->api = new APIController($user, $pass, $mode);
        $this->params = array();
    }

    public function install() {
        // No Logic
    }

    public function uninstall($module_id, $last_instance) {
        // No Logic
    }

    public function upgrade($current_version) {
        // No Logic
    }

    public function manageModule($module, array &$vars) {
        $view = $this->getView('manage');
        Loader::loadHelpers($view, array('Form', 'Html', 'Widget'));

        $view->set('module', $module);
        $view->set('vars', (object) $vars);

        return $view->fetch();
    }

    public function manageAddRow(array &$vars) {
        $view = $this->getView('add_row');
        Loader::loadHelpers($view, array('Form', 'Html', 'Widget'));

        $view->set('vars', (object) $vars);
        return $view->fetch();
    }

    public function manageEditRow($module_row, array &$vars) {
        $view = $this->getView('edit_row');
        Loader::loadHelpers($view, array('Form', 'Html', 'Widget'));

        if (empty($vars)) {
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
    protected function getView($view) {
        $viewObj = new View($view, 'default');
        $viewObj->base_uri = $this->base_uri;
        $viewObj->setDefaultView(
                'components' . DIRECTORY_SEPARATOR . 'modules'
                . DIRECTORY_SEPARATOR . 'cwatch' . DIRECTORY_SEPARATOR
        );

        return $viewObj;
    }

    public function selectModuleRow($module_group_id) {
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

    private function getModuleRowByApi($module_row, $module_group = "") {
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

    public function getPackageFields($vars = null) {
        Loader::loadHelpers($this, array("Form", "Html"));

        $module = $this->getModuleRowByApi((isset($vars->module_row) ? $vars->module_row : 0), (isset($vars->module_group) ? $vars->module_group : ""));

        $fields = new ModuleFields();

        if ($module) {
            $products = Configure::get('cwatch.products');
            $type = $fields->label(Language::_('CWatch.add_product.license_type', true), "license_type");
            $type->attach($fields->fieldSelect("meta[cwatch_license_type]", $products, $this->Html->ifSet($vars->meta['cwatch_license_type']), array("id" => "license_type")));
            $fields->setField($type);
            unset($type);
            $term = Configure::get('cwatch.terms');
            $terms = $fields->label(Language::_('CWatch.add_product.license_term', true), "license_term");
            $terms->attach($fields->fieldSelect("meta[cwatch_license_term]", $term, $this->Html->ifSet($vars->meta['cwatch_license_term']), array("id" => "license_term")));
            $fields->setField($terms);
            unset($terms);

            $sandbox_label = $fields->label("Sandbox", "sandbox");
            $field_sandbox = $fields->label("Enable Sandbox", "sandbox");
            $sandbox_label->attach($fields->fieldCheckbox("meta[cwatch_sandbox]", "true", (isset($vars->meta['cwatch_sandbox']) && $vars->meta['cwatch_sandbox'] == "true"), array('id' => "cwatch_sandbox"), $sandbox_label));
            $fields->setField($sandbox_label);

            return $fields;
        }

        return $fields;
    }

    public function getEmailTags() {
        return array(
            'module' => array(),
            'package' => array(),
            'service' => array("cwatch_license")
        );
    }

    public function addService($package, array $vars = null, $parent_package = null, $parent_service = null, $status = "pending") {
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
        if (isset($package->meta->cwatch_license_term)) {
            $term = $package->meta->cwatch_license_term;
        }
        if (isset($package->meta->cwatch_sandbox)) {
            $sandbox = $package->meta->cwatch_sandbox;
        }
        if (isset($vars['use_module']) && $vars['use_module'] == "true") {
            Loader::loadModels($this, ['Clients']);
            $client = $this->Clients->get($vars['client_id'], false);
            $firstname = $client->first_name;
            $lastname = $client->last_name;
            $email = $client->email;
            $country = $client->country;
            $this->loadApi($user, $pass, $sandbox);
            try {
                $response = $this->api->createLicence($email, $firstname, $lastname, $country, $product, $term);
                $json = json_decode($response->resp);
                if ($response->code != 200) {
                    $this->Input->setErrors(['api' => ['internal' => $response->errorMsg]]);
                }
                $licensekey = $json->distributionResult[0]->licenseKeys[0];
            } catch (exception $e) {
                $this->Input->setErrors(['api' => ['internal' => $e]]);
            }
            // $this->log("createcommand", serialize($client), "input", true);
            // Return on error
            if ($this->Input->errors())
                return;
        }
        return [
            [
                'key' => "licensekey",
                'value' => $licensekey,
                'encrypted' => 0
            ]
        ];
    }

//    public function unsuspendService($package, $service, $parent_package = null, $parent_service = null) {
//        return null;
//    }

    public function suspendService($package, $service, $parent_package = null, $parent_service = null) {
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
            if (empty($licensekey)) {
                $this->Input->setErrors(['api' => ['internal' => 'License Key not found.']]);
            } else {
                $response = $this->api->deactivateLicense($licensekey);
                $json = json_decode($response->resp);
                if ($response->success != 1) {
                    $this->Input->setErrors(['api' => ['internal' => $json[0]->message]]);
                }
            }
        } catch (exception $e) {
            $this->Input->setErrors(['api' => ['internal' => $e->getMessage()]]);
        }
        // Return on error
        if ($this->Input->errors())
            return;

        return null;
    }

    public function cancelService($package, $service, $parent_package = null, $parent_service = null) {
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
            $response = $this->api->deactivateLicense($licensekey);
            $json = json_decode($response->resp);
            //print_r($json); exit();
            if ($response->success != 1) {
                 $this->Input->setErrors(['api' => ['internal' => $json[0]->message]]);
            }
        } catch (exception $e) {
            $this->Input->setErrors(['api' => ['internal' => $e]]);
        }
        // Return on error
        if ($this->Input->errors())
            return;

        return null;
    }

    public function getClientTabs($package) {
        return [
            'tabClientActions' => Language::_('Cwatch.tab_client_actions', true)
        ];
    }

    public function getAdminTabs($package) {
//        return array(
//            'tabAdminManagementAction' => Language::_("Cwatch.tab_AdminManagementAction", true),
//        );
    }

    public function tabClientActions($package, $service, array $get = null, array $post = null, array $files = null) {
        $this->view = new View("tab_site", "default");
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, array("Form", "Html"));
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
        if (isset($package->meta->cwatch_license_term)) {
            $term = $package->meta->cwatch_license_term;
        }
        Loader::loadModels($this, ['Clients']);
        $client = $this->Clients->get($service->client_id, false);
        $email = $client->email;
        $this->loadApi($user, $pass, $sandbox);
        if (!empty($post)) {
            $sites = $this->api->addSite(['email' => $email, 'domain' => $post['domainname'], 'licenseKey' => $licensekey, 'initiateDns' => $post['initiateDns'] == 1 ? true : false, 'autoSsl' => $post['autoSsl'] == 1 ? true : false]);
            if (!empty($sites->errorMsg)) {
                $this->Input->setErrors(['api' => ['internal' => $sites->errorMsg]]);
            }
        }

        $sites = $this->api->getSites($email);
        $this->view->set("sites_data", json_decode($sites->resp));
        $this->view->set("service", $service);
        $this->view->set('service_id', $service->id);
        $this->view->set('licenseid', $licensekey);
        $this->view->set("addsite", $get[2]);

        $this->view->setDefaultView("components" . DS . "modules" . DS . "cwatch" . DS);
        return $this->view->fetch();
    }

    public function getClientServiceInfo($service, $package) {
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
        $this->loadApi($user, $pass, $sandbox);
        $response = $this->api->getLicenseInfo($licensekey);
        $json = json_decode($response->resp);
        if ($response->code != 200) {
            $this->Input->setErrors(['api' => ['internal' => $response->errorMsg]]);
        }
        // Load the view (admin_service_info.pdt) into this object, so helpers can be automatically added to the view
        $this->view = new View("client_service_info", "default");
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView("components" . DS . "modules" . DS . "cwatch" . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, array("Form", "Html"));

        $this->view->set("module_row", $row);
        $this->view->set("package", $json);
        $this->view->set("service", $service);
        $this->view->set("service_fields", $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }

    public function getAdminServiceInfo($service, $package) {
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
        $this->loadApi($user, $pass, $sandbox);
        $response = $this->api->getLicenseInfo($licensekey);
        $json = json_decode($response->resp);
        if ($response->code != 200) {
            $this->Input->setErrors(['api' => ['internal' => $response->errorMsg]]);
        }
        // Load the view (admin_service_info.pdt) into this object, so helpers can be automatically added to the view
        $this->view = new View("admin_service_info", "default");
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView("components" . DS . "modules" . DS . "cwatch" . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, array("Form", "Html"));

        $this->view->set("module_row", $row);
        $this->view->set("package", $json);
        $this->view->set("service", $service);
        $this->view->set("service_fields", $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }

}
