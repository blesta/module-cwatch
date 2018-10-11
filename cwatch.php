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
class Cwatch extends Module
{

    /**
     * Initialize the Module.
     */
    public function __construct()
    {
        // Load the cWatch API
        Loader::load(dirname(__FILE__) . DS . 'api' . DS . 'cwatch_api.php');

        // Load components required by this module
        Loader::loadComponents($this, ['Record', 'Input']);

        // Load the language required by this module
        Language::loadLang('cwatch', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load product configuration required by this module
        Configure::load('cwatch', dirname(__FILE__) . DS . 'config' . DS);
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
        Loader::loadHelpers($view, ['Form', 'Html', 'Widget']);

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
        Loader::loadHelpers($view, ['Form', 'Html', 'Widget']);

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
        Loader::loadHelpers($view, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        }

        $view->set('vars', (object) $vars);
        return $view->fetch();
    }

    /**
     * Load the view
     *
     * @param string $view The name of the view to load
     * @return \View
     */
    protected function getView($view)
    {
        $view_obj = new View($view, 'default');
        $view_obj->base_uri = $this->base_uri;
        $view_obj->setDefaultView('components' . DS . 'modules' . DS . 'cwatch' . DS);

        return $view_obj;
    }

    /**
     * Returns an array of available service delegation order methods. The module
     * will determine how each method is defined. For example, the method 'first'
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value pairs where the key is
     *  the type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function selectModuleRow($module_group_id)
    {
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        $group = $this->ModuleManager->getGroup($module_group_id);
        if ($group) {
            switch ($group->add_order) {
                default:
                case 'first':
                    foreach ($group->rows as $row) {
                        return $row->id;
                    }
                    break;
            }
        }

        return 0;
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
            'service' => ['cwatch_email', 'cwatch_firstname', 'cwatch_lastname', 'cwatch_country']
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
    public function addService(
        $package,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $status = 'pending'
    ) {
        Loader::loadModels($this, ['Clients', 'Services']);

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Get cWatch API
        $api = $this->getApi();
        $errors = [];
        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            $response = null;
            try {

                // Add a customer account in cWatch
                $response = $api->addUser(
                    $vars['cwatch_email'],
                    $vars['cwatch_firstname'],
                    $vars['cwatch_lastname'],
                    $vars['cwatch_country']
                );

                // Log request data
                $this->log('adduser', serialize($api->lastRequest()), 'input', true);
                $this->log('adduser', $response->raw(), 'output', $response->status() == 200);

                if ($response->status() != 200) {
                    $errors = ['api' => ['internal' => $response->errors()]];
                } else {
                    $license_types = Configure::get('cwatch.products');
                    // Get a count of licenses to provide for each type
                    foreach ($license_types as $license_type => $value) {
                        $license_types[$license_type] = isset($vars['configoptions'][$license_type])
                            ? $vars['configoptions'][$license_type]
                            : 0;
                    }

                    // Determine what term to add these licenses for
                    $license_term = 'MONTH_1';
                    if (($pricing = $this->Services->getPackagePricing($vars['pricing_id']))) {
                        $license_term = strtoupper($pricing->period) . '_' . $pricing->term;
                    }

                    // Add licenses to the customer account according to the config options provided
                    foreach ($license_types as $license_type => $quantity) {
                        for ($i = 0; $i < $quantity; $i++) {
                            $license_response = $api->addLicense(
                                $license_type,
                                $license_type == 'BASIC_DETECTION' ? 'UNLIMITED' : $license_term,
                                $vars['cwatch_email'],
                                $vars['cwatch_firstname'],
                                $vars['cwatch_lastname'],
                                $vars['cwatch_country']
                            );

                            $this->log(
                                'addlicense',
                                $license_response->raw(),
                                'output',
                                $license_response->status() == 200
                            );

                            // Break on error
                            if ($license_response->status() != 200) {
                                $errors = ['api' => ['internal' => $license_response->errors()]];

                                break 2;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $errors = ['api' => ['internal' => $e->getMessage()]];
            }
        }

        if (!empty($errors)) {
                // Delete the user if something went wrong
            if (isset($vars['cwatch_email'])) {
                $this->deleteUser($vars['cwatch_email']);
            }

            // Set errors and return
            $this->Input->setErrors($errors);
            return;
        }

        return [
            [
                'key' => 'cwatch_email',
                'value' => isset($vars['cwatch_email']) ? $vars['cwatch_email'] : '',
                'encrypted' => 0
            ],
            [
                'key' => 'cwatch_firstname',
                'value' => isset($vars['cwatch_firstname']) ? $vars['cwatch_firstname'] : '',
                'encrypted' => 0
            ],
            [
                'key' => 'cwatch_lastname',
                'value' => isset($vars['cwatch_lastname']) ? $vars['cwatch_lastname'] : '',
                'encrypted' => 0
            ],
            [
                'key' => 'cwatch_country',
                'value' => isset($vars['cwatch_country']) ? $vars['cwatch_country'] : '',
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
        Loader::loadModels($this, ['Clients', 'Services']);

        $this->validateServiceEdit($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        $fields = [];
        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            $fields = $this->updateService($service, $vars);
        }

        if ($this->Input->errors()) {
            return;
        }

        return $fields;
    }

    /**
     * Edits the service on the remote server. Sets Input errors on failure,
     * preventing the service from being edited.
     *
     * @param stdClass $service A stdClass object representing the current service
     * @param array $vars An array of user supplied info to satisfy the request
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    private function updateService($service, array $vars = [])
    {
        // Get cWatch API
        $api = $this->getApi();
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $license_keys = [];

        $firstname = isset($vars['cwatch_firstname'])
                ? $vars['cwatch_firstname']
                : $service_fields->cwatch_firstname;
        $lastname = isset($vars['cwatch_lastname'])
                ? $vars['cwatch_lastname']
                : $service_fields->cwatch_lastname;
        $country = isset($vars['cwatch_country'])
                ? $vars['cwatch_country']
                : $service_fields->cwatch_country;

        try {
            // Update a customer account in cWatch
            $response = $api->addUser($service_fields->cwatch_email, $firstname, $lastname, $country);

            // Log request data
            $this->log('edituser', serialize($api->lastRequest()), 'input', true);
            $this->log('edituser', $response->raw(), 'output', $response->status() == 200);

            if ($response->status() != 200) {
                $this->Input->setErrors(['api' => ['internal' => $response->errors()]]);
            } else {
                $license_types = Configure::get('cwatch.products');
                // Get a count of licenses to provide for each type
                foreach ($license_types as $license_type => $value) {
                    $license_types[$license_type] = isset($vars['configoptions'][$license_type])
                        ? $vars['configoptions'][$license_type]
                        : 0;
                }

                // Get cWatch licenses
                $licenses_response = $api->getLicenses($service_fields->cwatch_email);
                $license_errors = $licenses_response->errors();
                $licenses = empty($license_errors) ? $licenses_response->response() : [];

                // Count how many licenses to add
                foreach ($licenses as $license) {
                    if (strtolower($license->status) == 'valid') {
                        $license_types[$license->pricingTerm]--;
                    }

                    if ($license_types[$license->pricingTerm] < 0) {
                        // Give an error if the config option has a value lower than the current number of licenses
                        // for this type
                        $this->Input->setErrors(
                            ['licenses' => ['limit_exceeded' => Language::_('CWatch.!error.limit_exceeded', true)]]
                        );
                    }
                }

                // Determine what term to add these licenses for
                $license_term = 'MONTH_1';
                if (($pricing = $this->Services->getPackagePricing($vars['pricing_id']))) {
                    $license_term = strtoupper($pricing->period) . '_' . $pricing->term;
                }

                // Add licenses to the customer account according to the config options provided
                foreach ($license_types as $license_type => $quantity) {
                    for ($i = 0; $i < $quantity; $i++) {
                        $license_response = $api->addLicense(
                            $license_type,
                            $license_type == 'BASIC_DETECTION' ? 'UNLIMITED' : $license_term,
                            $service_fields->cwatch_email,
                            $firstname,
                            $lastname,
                            $country
                        );

                        $this->log(
                            'addlicense',
                            $license_response->raw(),
                            'output',
                            $license_response->status() == 200
                        );

                        // Break on error
                        if ($license_response->status() != 200) {
                            $this->Input->setErrors(['api' => ['internal' => $license_response->errors()]]);

                            break 2;
                        }

                        $license = $license_response->response();
                        $license_keys[] = $license->distributionResult[0]->licenseKeys[0];
                    }
                }
            }
        } catch (exception $e) {
            $this->Input->setErrors(['api' => ['internal' => $e->getMessage()]]);
        }

        if ($this->Input->errors()) {
            // Deactivate any licenses that were added
            foreach ($license_keys as $license_key) {
                $license_response = $api->deactivateLicense($license_key);
            }
        }

        return [
            ['key' => 'cwatch_email', 'value' => $service_fields->cwatch_email, 'encrypted' => 0],
            ['key' => 'cwatch_firstname', 'value' => $firstname, 'encrypted' => 0],
            ['key' => 'cwatch_lastname', 'value' => $lastname, 'encrypted' => 0],
            ['key' => 'cwatch_country', 'value' => $country, 'encrypted' => 0]
        ];
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
        // Delete user
        $this->deleteUser($service_fields->cwatch_email);
    }

    /**
     * Deletes or suspends the given user
     *
     * @param string $email The email of the customer to delete or suspend
     */
    private function deleteUser($email)
    {
        // Get cWatch API
        $api = $this->getApi();

        $errors = ['api' => []];
        try {
            // Fetch all licenses for the user
            $list_response = $api->getLicenses($email);
            $licenses = $list_response->response();

            // Deactivate all licenses for the user
            foreach ($licenses as $license) {
                $license_response = $api->deactivateLicense($license->licenseKey);

                if ($license_response->status() != 200) {
                    $errors['api'][$license->licenseKey] = $license_response->errors();
                }
            }

            // Remove user
            $response = $api->deleteUser($email);

            $this->log('deleteuser', serialize($api->lastRequest()), 'input', true);
            $this->log('deleteuser', $response->raw(), 'output', $response->status() == 200);
        } catch (Exception $e) {
            $errors['api']['internal'] = $e->getMessage();
        }

        // Set Errors
        if (!empty($errors['api'])) {
            $this->Input->setErrors($errors);
        }
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html', 'Form']);
        Loader::loadModels($this, ['Countries']);

        $fields = new ModuleFields();

        // Create email label
        $email = $fields->label(Language::_('Cwatch.service_field.email', true), 'cwatch_email');
        // Create email field and attach to email label
        $email->attach(
            $fields->fieldText('cwatch_email', $this->Html->ifSet($vars->cwatch_email), ['id' => 'cwatch_email'])
        );
        // Set the label as a field
        $fields->setField($email);

        // Create firstname label
        $firstname = $fields->label(Language::_('Cwatch.service_field.firstname', true), 'cwatch_firstname');
        // Create firstname field and attach to firstname label
        $firstname->attach(
            $fields->fieldText(
                'cwatch_firstname',
                $this->Html->ifSet($vars->cwatch_firstname),
                ['id' => 'cwatch_firstname']
            )
        );
        // Set the label as a field
        $fields->setField($firstname);

        // Create lastname label
        $lastname = $fields->label(Language::_('Cwatch.service_field.lastname', true), 'cwatch_lastname');
        // Create lastname field and attach to lastname label
        $lastname->attach(
            $fields->fieldText(
                'cwatch_lastname',
                $this->Html->ifSet($vars->cwatch_lastname),
                ['id' => 'cwatch_lastname']
            )
        );
        // Set the label as a field
        $fields->setField($lastname);

        // Create country label
        $country = $fields->label(Language::_('Cwatch.service_field.country', true), 'cwatch_country');
        // Create country field and attach to country label
        $country->attach(
            $fields->fieldSelect(
                'cwatch_country',
                $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - '),
                $this->Html->ifSet($vars->cwatch_country),
                ['id' => 'cwatch_country']
            )
        );
        // Set the label as a field
        $fields->setField($country);

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        return $this->getAdminAddFields($package, $vars);
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
            'tabClientLicenses' => Language::_('CWatch.tab_licenses.licenses', true),
            'tabClientMalware' => Language::_('CWatch.tab_malware.malware', true)
        ];
    }

    /**
     * Returns all tabs to display to an admin when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getAdminTabs($package)
    {
        return [
            'tabLicenses' => Language::_('CWatch.tab_licenses.licenses', true),
            'tabMalware' => Language::_('CWatch.tab_malware.malware', true)
        ];
    }

    /**
     * Manage malware scanners
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientMalware($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->getMalwareTab('tab_client_malware', $service, $post);
    }

    /**
     * Manage malware scanners
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabMalware($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->getMalwareTab('tab_malware', $service, $post);
    }

    /**
     * Manage malware scanners
     *
     * @param string $template The name of the template to use
     * @param stdClass $service A stdClass object representing the current service
     * @param array $post Any POST parameters
     * @return string The string representing the contents of this tab
     */
    private function getMalwareTab($template, $service, $post)
    {
        // Load view
        $this->view = $this->getView($template);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get cWatch API
        $api = $this->getApi();
        $service_fields = $this->serviceFieldsToObject($service->fields);

        if (!empty($post)) {
            $scanner = $api->addScanner(
                $service_fields->cwatch_email,
                [
                    'domain' => $post['domainname'],
                    'password' => $post['password'],
                    'login' => $post['username'],
                    'host' => $post['host'],
                    'port' => $post['port'],
                    'path' => $post['path'],
                    'protocol' => $post['port'] == '22' ? 'FTPS' : 'FTP'
                ]
            );

            // Filter logging content
            $scanner_errors = $scanner->errors();
            $last_request = $api->lastRequest();
            if (isset($last_request['content']['password'])) {
                $last_request['content']['password'] = '***';
            }

            $scanner_raw = json_decode($scanner->raw());
            if (isset($scanner_raw->password)) {
                $scanner_raw->password = '***';
            }

            $this->log('addmalwarescanner', serialize($last_request), 'input', true);
            $this->log('addmalwarescanner', json_encode($scanner_raw), 'output', empty($scanner_errors));

            if (!empty($scanner_errors)) {
                $this->Input->setErrors(['api' => ['internal' => $scanner_errors]]);
            }
        }

        $sites_response = $api->getSites($service_fields->cwatch_email);
        $sites_errors = $sites_response->errors();
        $sites = ['' => Language::_('AppController.select.please', true)];
        $domains_ftp = [];
        if (empty($sites_errors)) {
            foreach ($sites_response->response() as $site) {
                $scanner_response = $api->getScanner($service_fields->cwatch_email, $site->domain);
                if (empty($sites_errors)) {
                    $scanner = $scanner_response->response();
                    $domains_ftp[$site->domain] = $scanner->ftp;
                }

                $sites[$site->domain] = $site->domain;
            }
        }

        $this->view->set('domains_ftp', $domains_ftp);
        $this->view->set('sites', $sites);
        $this->view->set('service', $service);
        return $this->view->fetch();
    }

    /**
     * Manage customer licenses
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientLicenses($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $template = isset($get['action']) && $get['action'] == 'add_site' ? 'client_add_site' : 'tab_client_licenses';
        return $this->getLicensesTab($template, $service, $post, $get);
    }

    /**
     * Manage customer licenses
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabLicenses($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $template = isset($get['action']) && $get['action'] == 'add_site' ? 'admin_add_site' : 'tab_licenses';
        return $this->getLicensesTab($template, $service, $post, $get);
    }

    /**
     * Manage customer licenses
     *
     * @param string $template The name of the template to use
     * @param stdClass $service A stdClass object representing the current service
     * @param array $post Any POST parameters
     * @param array $get Any GET parameters
     * @return string The string representing the contents of this tab
     */
    private function getLicensesTab($template, $service, $post, $get)
    {
        // Load view
        $this->view = $this->getView($template);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        Loader::loadModels($this, ['Clients']);

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get cWatch API
        $api = $this->getApi();

        if (!empty($post)) {
            $remove_site = isset($post['action']) && $post['action'] == 'remove_domain';
            if ($remove_site) {
                $site = $api->removeSite($service_fields->cwatch_email, $post['domain']);
            } else {
                $site = $api->addSite(
                    [
                        'email' => $service_fields->cwatch_email,
                        'domain' => $post['domain'],
                        'licenseKey' => $post['licenseKey'],
                        'initiateDns' => isset($post['initiateDns']) && $post['initiateDns'] == 1 ? true : false,
                        'autoSsl' => isset($post['autoSsl']) && $post['autoSsl'] == 1 ? true : false
                    ]
                );
            }

            $site_errors = $site->errors();
            $this->log($remove_site ? 'removesite' : 'addsite', serialize($api->lastRequest()), 'input', true);
            $this->log($remove_site ? 'removesite' : 'addsite', $site->raw(), 'output', empty($site_errors));
            if (!empty($site_errors)) {
                $this->Input->setErrors(['api' => ['internal' => $site_errors]]);
            }
        }

        // Get cWatch site provisions
        $site_provisions = [];
        $provisions_response = $api->getSiteProvisions($service_fields->cwatch_email);
        $provisions_errors = $provisions_response->errors();
        if (empty($provisions_errors)) {
            $site_provisions = $provisions_response->response();
        }

        // Sort provisions by license
        $provisions_by_license = [];
        foreach ($site_provisions as $site_provision) {
            if (strtolower($site_provision->status) != 'add_site_fail') {
                $provisions_by_license[$site_provision->licenseKey] = $site_provision;
            }
        }

        // Get cWatch sites
        $sites = [];
        $sites_response = $api->getSites($service_fields->cwatch_email);
        $sites_errors = $sites_response->errors();
        if (empty($sites_errors)) {
            $sites = $sites_response->response();
        }

        // Sort sites by license
        $sites_by_license = [];
        foreach ($sites as $site) {
            // Get the malware scanner for this site
            $scanner = $api->getScanner($service_fields->cwatch_email, $site->domain);
            $scanner_errors = $scanner->errors();
            if (empty($scanner_errors)) {
                $site->scanner = $scanner->response();
            }

            $sites_by_license[$site->licenseKey] = $site;
        }

        // Get cWatch licenses
        $licenses_response = $api->getLicenses($service_fields->cwatch_email);
        $licenses_errors = $licenses_response->errors();
        $licenses = [];
        $available_licenses = [];
        if (empty($licenses_errors)) {
            foreach ($licenses_response->response() as $license) {
                if (isset($sites_by_license[$license->licenseKey])) {
                    // Use the associated site for domain info
                    $license->site = $sites_by_license[$license->licenseKey];
                } elseif (isset($provisions_by_license[$license->licenseKey])) {
                    // Use the associated provision for site info
                    $license->site = $provisions_by_license[$license->licenseKey];
                } else {
                    // Mark this license available for attaching a new domain
                    $available_licenses[$license->licenseKey] = $license->productTitle;
                }

                $licenses[] = $license;
            }
        }

        $this->view->set('site_statuses', $this->getSiteStatuses());
        $this->view->set('licenses', $licenses);
        $this->view->set('available_licenses', $available_licenses);
        $this->view->set('service', $service);
        $this->view->set('license_key', isset($get['key']) ? $get['key'] : '');

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
        return $this->getServiceInfo('client_service_info', $service);
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
        return $this->getServiceInfo('admin_service_info', $service);
    }

    /**
     * Fetches the HTML content to display when viewing the service info.
     *
     * @param string $template The name of the template to use
     * @param stdClass $service A stdClass object representing the current service
     * @return string The string representing the contents of this tab
     */
    private function getServiceInfo($template, $service)
    {
        // Load view
        $this->view = $this->getView($template);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get cWatch API
        $api = $this->getApi();
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get cWatch licenses
        $licenses_response = $api->getLicenses($service_fields->cwatch_email);
        $licenses_errors = $licenses_response->errors();
        $licenses = [];
        if (empty($licenses_errors)) {
            foreach ($licenses_response->response() as $license) {
                if (strtolower($license->status) == 'valid') {
                    $licenses[] = $license;
                }
            }
        }

        $this->view->set('licenses', $licenses);
        $this->log('viewinfo', serialize($licenses), 'output', true);

        $login_url = 'https://partner.cwatch.comodo.com';
        $user_response = $api->getUser($service_fields->cwatch_email);
        $users = $user_response->response();
        $user_errors = $user_response->errors();
        if (empty($user_errors) && isset($users[0])) {
            $login_response = $api->getLogin($users[0]->camId, $service_fields->cwatch_email);
            $login_errors = $login_response->errors();
            if (empty($login_errors)) {
                $login = $login_response->response();
                $login_url = $login->loginUrl;
            }
        }
        $this->view->set('login_url', $login_url);

        return $this->view->fetch();
    }

    /**
     * Gets a list of cWatch site provision statuses and their languages
     *
     * @return array A list of cWatch site provision statuses and their languages
     */
    private function getSiteStatuses()
    {
        return [
            'WAITING' => Language::_('CWatch.getsitestatuses.waiting', true),
            'ADD_SITE_INPROGRESS' => Language::_('CWatch.getsitestatuses.site_inprogress', true),
            'ADD_SITE_RETRY' => Language::_('CWatch.getsitestatuses.site_retry', true),
            'ADD_SITE_COMPLETED' => Language::_('CWatch.getsitestatuses.site_completed', true),
            'ADD_SITE_FAIL' => Language::_('CWatch.getsitestatuses.site_failed', true),
            'INITIATE_DNS_INPROGRESS' => Language::_('CWatch.getsitestatuses.dns_inprogress', true),
            'INITIATE_DNS_RETRY' => Language::_('CWatch.getsitestatuses.dns_retry', true),
            'INITIATE_DNS_COMPLETED' => Language::_('CWatch.getsitestatuses.dns_completed', true),
            'INITIATE_DNS_FAIL' => Language::_('CWatch.getsitestatuses.dns_failed', true),
            'AUTO_SSL_INPROGRESS' => Language::_('CWatch.getsitestatuses.ssl_inprogress', true),
            'AUTO_SSL_RETRY' => Language::_('CWatch.getsitestatuses.ssl_retry', true),
            'AUTO_SSL_COMPLETED' => Language::_('CWatch.getsitestatuses.ssl_completed', true),
            'AUTO_SSL_FAIL' => Language::_('CWatch.getsitestatuses.ssl_fail', true)
        ];
    }

    /**
     * Loads the cWatch API based on current row data
     *
     * @return \CwatchApi
     */
    private function getApi()
    {
        $row = $this->getModuleRow();
        $username = isset($row->meta->username) ? $row->meta->username : '';
        $password = isset($row->meta->password) ? $row->meta->password : '';
        $sandbox = isset($row->meta->cwatch_sandbox) ? $row->meta->cwatch_sandbox : 'true';

        return new CwatchApi($username, $password, $sandbox == 'true');
    }

    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return bool True if the service validates, false otherwise. Sets Input errors when false.
     */
    public function validateService($package, array $vars = null)
    {
        $this->Input->setRules($this->getServiceRules($vars));
        return $this->Input->validates($vars);
    }

    /**
     * Attempts to validate an existing service against a set of service info updates. Sets Input errors on failure.
     *
     * @param stdClass $service A stdClass object representing the service to validate for editing
     * @param array $vars An array of user-supplied info to satisfy the request
     * @return bool True if the service update validates or false otherwise. Sets Input errors when false.
     */
    public function validateServiceEdit($service, array $vars = null)
    {
        $this->Input->setRules($this->getServiceRules($vars, true));
        return $this->Input->validates($vars);
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, $edit = false)
    {
        $rules = [
            'cwatch_email' => [
                'format' => [
                    'rule' => 'isEmail',
                    'message' => Language::_('CWatch.!error.cwatch_email.format', true)
                ],
                'unique' => [
                    'rule' => function ($email) {
                        // Fetch any user matching this email from cWatch
                        $api = $this->getApi();
                        $user_response = $api->getUser($email);
                        $user_errors = $user_response->errors();

                        if (empty($user_errors)) {
                            $user = $user_response->response();

                            if (!empty($user)) {
                                return false;
                            }
                        }

                        return true;
                    },
                    'message' => Language::_('CWatch.!error.cwatch_email.unique', true)
                ]
            ],
            'cwatch_firstname' => [
                'empty' => [
                    'if_set' => $edit,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('CWatch.!error.cwatch_firstname.empty', true)
                ]
            ],
            'cwatch_lastname' => [
                'empty' => [
                    'if_set' => $edit,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('CWatch.!error.cwatch_lastname.empty', true)
                ]
            ],
            'cwatch_country' => [
                'length' => [
                    'if_set' => $edit,
                    'rule' => ['maxLength', 3],
                    'message' => Language::_('CWatch.!error.cwatch_country.length', true)
                ]
            ]
        ];

        if ($edit) {
            unset($rules['cwatch_email']);
        }

        return $rules;
    }
}
