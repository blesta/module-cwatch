<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cwatch_response.php';

class CwatchApi
{
    // API endpoint URL
    private $API_URL;

    // API Token for request authentication
    private $token = '';

    /**
     * CwatchApi constructor.
     *
     * @param string $username The username of the API user
     * @param string $password The password of the API user
     * @param bool $sandbox True to use the sandbox API endpoint, false otherwise
     */
    public function __construct($username, $password, $sandbox)
    {
        if ($sandbox) {
            $this->API_URL = 'http://cwatchpartnerportalstaging-env.us-east-1.elasticbeanstalk.com';
        } else {
            $this->API_URL = 'https://partner.cwatch.comodo.com';
        }

        $params = json_encode(['username' => $username, 'password' => $password]);
        $response = $this->apiRequest('login', $params, 'POST');
        if ($response) {
            $auth = $response['headers'];
            $this->token = $auth;
        }
    }

    /**
     * Create a customer account in cWatch
     *
     * @param string $email The email by which to identify the customer and use for login
     * @param string $firstName The customer's first name
     * @param string $lastName The customer's last name
     * @param string $country The 3-character country code of the customer
     * @return CwatchResponse
     */
    public function addUser($email, $firstName, $lastName, $country)
    {
        $params = json_encode(['email' => $email, 'name' => $firstName, 'surname' => $lastName, 'country' => $country]);
        $response = $this->apiRequest('customer/add', $params, 'POST');

        return new CwatchResponse($response['content']);
    }

    /**
     * Delete the given user
     *
     * @param string $email The email of the customer to delete
     * @return CwatchResponse
     */
    public function deleteUser($email)
    {
        $response = $this->apiRequest('customer/deleteCustomer?email' . $email, '', 'GET');

        return new CwatchResponse($response['content']);
    }

    /**
     * Fetch customer info from cWatch
     *
     * @param string $email The email by which to identify the customer and use for login
     * @return CwatchResponse
     */
    public function getUser($email)
    {
        $params = json_encode(['customers' => [$email]]);
        $response = $this->apiRequest('customer/add', $params, 'POST');

        return new CwatchResponse($response['content']);
    }

    /**
     * Create a license of the given type for the given user
     *
     * @param string $licenseType The type of license to create ("BASIC_DETECTION", "PRO", "PRO_FREE",
     *  "PRO_FREE_60D", "PREMIUM", "PREMIUM_FREE", "PREMIUM_FREE_60D")
     * @param string $term The term for which the license will remain valid before renewal ("MONTH_1", "MONTH_12",
     *  "MONTH_24", "MONTH_36", "MONTH_2", "UNLIMITED")
     * @param string $email The email by which to identify the customer
     * @param string $firstName The customer's first name
     * @param string $lastName The customer's last name
     * @param string $country The 3-character country code of the customer
     * @return CwatchResponse
     */
    public function addLicense($licenseType, $term, $email, $firstName, $lastName, $country)
    {
        $array = [
            'term' => $term,
            'product' => $licenseType,
            'customers' => [['email' => $email, 'name' => $firstName, 'surname' => $lastName, 'country' => $country]],
            'autoLicenseUpgrade' => false,
            'renewAutomatically' => false
        ];

        $params = json_encode($array);
        $response = $this->apiRequest('customer/distributeLicenseForCustomers', $params, 'POST');

        return new CwatchResponse($response['content']);
    }

    /**
     * Deactivates the given license
     *
     * @param string $licenseKey The key of the license to disable
     * @return CwatchResponse
     */
    public function deactivateLicense($licenseKey)
    {
        $params = json_encode(['licenses' => [$licenseKey]]);
        $response = $this->apiRequest('customer/deactivatelicense', $params, 'PUT');

        return new CwatchResponse($response['content']);
    }

    /**
     * Provision a site for a license in Cwatch
     *
     * @param array $params
     *     - email The email of the customer to add this site for
     *     - domain The domain to be provisioned
     *     - licenseKey The key for the license to associate with this site
     *     - initiateDns Whether to start scaning DNS records
     *     - autoSsl Whether to install an ssl certificate
     * @return CwatchResponse
     */
    public function addSite($params)
    {
        $response = $this->apiRequest('siteprovision/add', json_encode([$params]), 'POST');

        return new CwatchResponse($response['content']);
    }

    /**
     * Get sites by customer email
     *
     * @param string $email The customer's email
     * @return CwatchResponse
     */
    public function getSites($email)
    {
        $response = $this->apiRequest('siteprovision/item/getByCustomer?customerEmail=' . $email, '', 'GET');

        return new CwatchResponse($response['content']);
    }

    /**
     * Fetch license info for a given license key
     *
     * @param string $licenseKey The key of the license
     * @return CwatchResponse
     */
    public function getLicense($licenseKey)
    {
        $response = $this->apiRequest('customer/showLicenceByKey?licenseKey=' . $licenseKey, '', 'GET');

        return new CwatchResponse($response['content']);
    }

    /**
     * Fetch licenses for the given customer
     *
     * @param string $email The customer email to fetch licenses for
     * @return CwatchResponse
     */
    public function getLicenses($email)
    {
        $response = $this->apiRequest('customer/listlicencebyemail?activeLicenseOnly=true&email=' . $email, '', 'GET');

        return new CwatchResponse($response['content']);
    }

    /**
     * Check the malware scanner status for a given damin
     *
     * @param string $site The domain to check
     * @return CwatchResponse
     */
    public function getScanner($site)
    {
        $response = $this->apiRequest('/malware/getScannerStatus?site=' . $site, '', 'GET');

        return new CwatchResponse($response['content']);
    }

    /**
     * Check a malware scanner for a given damin
     *
     * @param array $params
     *     - domain The domain to scan
     *     - username The username for FTP access
     *     - password The password for FTP access
     *     - host The host to use for FTP access
     *     - port The port to use for FTP access
     *     - path The path to the web directory for this site
     * @return CwatchResponse
     */
    public function addScanner($params)
    {
        $response = $this->apiRequest('/malware/enableScanner', json_encode([$params]), 'POST');

        return new CwatchResponse($response['content']);
    }

    /**
     * Send an API request to cWatch server
     *
     * @param string $route The path to the API method
     * @param string $body The data to be sent
     * @param string $method Data transfer method (POST, GET, PUT)
     * @return array
     */
    private function apiRequest($route, $body, $method)
    {
        $url = $this->API_URL . '/' . $route;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_POST, 1);
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

        if ($method == 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        $headers = [];
        $headers[] = 'Authorization: ' . $this->token;
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Curl Error: ' . curl_error($ch));
        }
        curl_close($ch);

        $authorization = '';
        $data = explode("\n", $result);
        foreach ($data as $part) {
            $split_part = explode(':', $part);
            if ($split_part[0] == 'Authorization' && isset($split_part[1])) {
                $authorization = $split_part[1];
                break;
            }
        }

        // Return request response
        return [
            'content' => $data[count($data) - 1],
            'headers' => $authorization
        ];
    }
}
