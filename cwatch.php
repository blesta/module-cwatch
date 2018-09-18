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

        // Get cWatch API
        $api = $this->getApi();
        $errors = [];
        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            $response = null;
            try {
                // Log request data
                $this->log('createcommand', serialize($vars), 'input', true);

                // Add a customer account in cWatch
                $response = $api->addUser(
                    $vars['cwatch_email'],
                    $vars['cwatch_firstname'],
                    $vars['cwatch_lastname'],
                    $vars['cwatch_country']
                );

                // Log response
                $this->log('adduser', serialize($response), 'output', $response->code == 200);

                if ($response->code != 200) {
                    $errors = ['api' => ['internal' => $response->errorMsg]];
                } else {
                    // Get a count of licenses to provide for each type
                    $license_types = [
                        'BASIC_DETECTION' => isset($vars['configoptions']['basic_licenses'])
                            ? $vars['configoptions']['basic_licenses']
                            : 0,
                        'PRO' => isset($vars['configoptions']['pro_licenses'])
                            ? $vars['configoptions']['pro_licenses']
                            : 0,
                        'PRO_FREE_60D' => isset($vars['configoptions']['pro_free_licenses'])
                            ? $vars['configoptions']['pro_free_licenses']
                            : 0,
                        'PREMIUM' => isset($vars['configoptions']['premium_licenses'])
                            ? $vars['configoptions']['premium_licenses']
                            : 0,
                        'PREMIUM_FREE_60D' => isset($vars['configoptions']['premium_free_licenses'])
                            ? $vars['configoptions']['premium_free_licenses']
                            : 0
                    ];

                    // Determine what term to add these licenses for
                    $license_term = 'MONTH_1';
                    $available_terms = Configure::get('cwatch.terms');
                    $pricing = $this->Services->getPackagePricing($vars['pricing_id']);

                    if ($pricing
                        && array_key_exists(strtoupper($pricing->period) . '_' . $pricing->term, $available_terms)
                    ) {
                        // Use the monthly term if it is in the supported list
                        $license_term = strtoupper($pricing->period) . '_' . $pricing->term;
                    } elseif ($pricing
                        && $pricing->period == 'year'
                        && array_key_exists('MONTH_' . (12 * $pricing->term), $available_terms)
                    ) {
                        // Convert the yearly tern to monthly and use it if it is in the supported list
                        $license_term = 'MONTH_' . (12 * $pricing->term);
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
                                serialize($license_response),
                                'output',
                                $license_response->code == 200
                            );

                            // Break on error
                            if ($license_response->code != 200) {
                                $errors = ['api' => ['internal' => $license_response->errorMsg]];

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
            $this->deleteUser(isset($vars['cwatch_email']) ? $vars['cwatch_email'] : '');

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
        $service_fields = $this->serviceFieldsToObject($service->fields);
        try {
            // Log request data
            $this->log('adduser', serialize($vars), 'input', true);

            $firstname = isset($vars['cwatch_firstname'])
                    ? $vars['cwatch_firstname']
                    : $service_fields->cwatch_firstname;
            $lastname = isset($vars['cwatch_lastname'])
                    ? $vars['cwatch_lastname']
                    : $service_fields->cwatch_lastname;
            $country = isset($vars['cwatch_country'])
                    ? $vars['cwatch_country']
                    : $service_fields->cwatch_country;

            // Update a customer account in cWatch
            $response = $api->addUser($service_fields->cwatch_email, $firstname, $lastname, $country);

            if ($response->code != 200) {
                $this->Input->setErrors(['api' => ['internal' => $response->errorMsg]]);
            }
        } catch (exception $e) {
            $this->Input->setErrors(['api' => ['internal' => $e->getMessage()]]);
        }

        return [
            ['key' => 'cwatch_email', 'value' => $service_fields->cwatch_email, 'encrypted' => 0],
            ['key' => 'cwatch_firstname', 'value' => $firstname, 'encrypted' => 0],
            ['key' => 'cwatch_lastname', 'value' => $lastname, 'encrypted' => 0],
            ['key' => 'cwatch_country', 'value' => $country, 'encrypted' => 0]
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
        // Suspend user
        $this->deleteUser($service_fields->cwatch_email, true);
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
     * @param bool $suspend False to delete the customer account, true otherwise
     */
    private function deleteUser($email, $suspend = false)
    {
        // Get cWatch API
        $api = $this->getApi();

        $errors = ['api' => []];
        try {
            // Fetch all licenses for the user
            $list_response = $api->getLicenses($email);
            $licenses = json_decode($list_response->resp);

            // Deactivate all licenses for the user
            foreach ($licenses as $license) {
                $license_response = $api->deactivateLicense($license->licenseKey);

                if ($license_response->code != 200) {
                    $errors['api'][$license->licenseKey] = $license_response->errorMsg;
                }
            }

            if (!$suspend) {
                // Remove user
                $response = $api->deleteUser($email);
                $this->log('deleteuser', serialize($response), 'output', $response->code == 200);
            }
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
            'tabClientSites' => Language::_('CWatch.tab_sites.sites', true),
            'tabClientMalWare' => Language::_('CWatch.tab_malware.malware', true)
        ];
    }
    /**
     * Client Actions (Add site for scanner)
     *
     * @return string The string representing the contents of this tab
     */
    public function tabClientMalWare($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Load view
        $this->view = $this->getView('tab_malware');

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get cWatch API
        $api = $this->getApi();

        if (!empty($post)) {
            if ($post['actionname'] == 'checkstatus') {
                $sites = $api->getScanner($post['domainname']);
            } else {
                $sites = $api->addScanner(
                    [
                        'domain' => $post['domainname'],
                        'password' => $post['password'],
                        'username' => $post['username'],
                        'host' => $post['host'],
                        'port' => $post['port'],
                        'path' => $post['path']
                    ]
                );
            }

            if (!empty($sites->errorMsg)) {
                $this->Input->setErrors(['api' => ['internal' => $sites->errorMsg]]);
            }
        }

        $this->view->set('service_id', $service->id);
        return $this->view->fetch();
    }
    /**
     * Manage customer sites
     *
     * @return string The string representing the contents of this tab
     */
    public function tabClientSites($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Load view
        $this->view = $this->getView('tab_sites');

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        Loader::loadModels($this, ['Clients']);

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get cWatch API
        $api = $this->getApi();

        if (!empty($post)) {
            $site = $api->addsite(
                [
                    'email' => $service_fields->cwatch_email,
                    'domain' => $post['domain'],
                    'licenseKey' => $post['licenseKey'],
                    'initiateDns' => isset($post['initiateDns']) && $post['initiateDns'] == 1 ? true : false,
                    'autoSsl' => isset($post['autoSsl']) && $post['autoSsl'] == 1 ? true : false
                ]
            );

            if (!empty($site->errorMsg)) {
                $this->Input->setErrors(['api' => ['internal' => $site->errorMsg]]);
            }
        }

        // Get cWatch sites
        $sites_response = $api->getSites($service_fields->cwatch_email);
        $sites = [];
        if (empty($sites_response->errorMsg)) {
            foreach (json_decode($sites_response->resp) as $site) {
                if (strtolower($site->status) != 'add_site_fail') {
                    $sites[] = $site;
                }
            }
        }

        // Get cWatch licenses
        $licenses_response = $api->getLicenses($service_fields->cwatch_email);
        $licenses = [];
        if (empty($licenses_response->errorMsg)) {
            foreach (json_decode($licenses_response->resp, true) as $license) {
                if (strtolower($license['status']) == 'valid' && $license['registeredDomainCount'] == 0) {
                    $licenses[$license['licenseKey']] = $license['friendlyName'];
                }
            }
        }

        $this->view->set('sites', $sites);
        $this->view->set('licenses', $licenses);
        $this->view->set('service', $service);
        $this->view->set('service_id', $service->id);
        $this->view->set('addsite', isset($get[2]) ? $get[2] : false);

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
        // Load view
        $this->view = $this->getView('client_service_info');

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get cWatch API
        $api = $this->getApi();
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get cWatch licenses
        $licenses_response = $api->getLicenses($service_fields->cwatch_email);
        $licenses = [];
        if (empty($licenses_response->errorMsg)) {
            foreach (json_decode($licenses_response->resp) as $license) {
                if (strtolower($license->status) == 'valid') {
                    $licenses[] = $license;
                }
            }
        }

        $this->view->set('licenses', $licenses);
        $this->log('viewinfo', serialize($licenses), 'output', true);

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
        // Load view
        $this->view = $this->getView('admin_service_info');

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get cWatch API
        $api = $this->getApi();
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get cWatch licenses
        $licenses_response = $api->getLicenses($service_fields->cwatch_email);
        $licenses = [];
        if (empty($licenses_response->errorMsg)) {
            foreach (json_decode($licenses_response->resp) as $license) {
                if (strtolower($license->status) == 'valid') {
                    $licenses[] = $license;
                }
            }
        }

        $this->view->set('licenses', $licenses);
        $this->log('viewinfo', serialize($licenses), 'output', true);

        return $this->view->fetch();
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
}
