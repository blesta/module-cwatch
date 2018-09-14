<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'CwatchResponse.php';

class ApiController
{

    // URL where API reside, please do not change
    private $API_URL = 'https://partner.cwatch.comodo.com';

    const API_ERROR = '{"error": "Server Error", "message": "Server Error", "status": 500}';

    // Class variable to hold auth token
    private $token = '';

    /**
     * APIController constructor.
     * @param string $login
     * @param string $pass
     */
    public function __construct(string $login, string $pass, string $mode)
    {
        if ($mode == 'true') {
            $this->API_URL = 'http://cwatchpartnerportalstaging-env.us-east-1.elasticbeanstalk.com';
        } else {
            $this->API_URL = 'https://partner.cwatch.comodo.com';
        }
        $params = json_encode(['username' => $login, 'password' => $pass]);
        $response = $this->api_call('login', $params, 'POST');
        if ($response) {
            $auth = $response['headers'];
            $this->token = $auth;
        }
    }

    /** function will create user and assign license to user
     * @param string $email
     * @param type $name
     * @param type $surname
     * @param type $country
     * @param type $product
     * @param type $term
     * @return json
     */
    public function createlicence(string $email, string $name, string $surname, string $country, string $product, string $term)
    {
        $apiResponse = $this->createuser($email, $name, $surname, $country);
        if (200 === $apiResponse->code) {
            $apiResponse = $this->distributelicense($product, $term, $email, $name, $surname, $country);
        }

        return $apiResponse;
    }

    /** Function will create user
     * @param string $email
     * @param string $name
     * @param string $surname
     * @param string $country
     * @return APIResponse
     */
    private function createuser(string $email, string $name, string $surname, string $country)
    {
        //$params = "{\"email\": \"{$email}\",  \"name\": \"{$name}\",  \"surname\": \"{$surname}\",  \"country\": \"{$country}\"}";
        $params = json_encode(['email' => $email, 'name' => $name, 'surname' => $surname, 'country' => $country]);
        $response = $this->api_call('customer/add', $params, 'POST');
        if (!$response) {
            $responseBody = self::API_ERROR;
        } else {
            $responseBody = $response['content'];
        }

        return new ApiResponse($responseBody);
    }

    /**
     * Function will delete user
     * @param string $email
     * @return json string
     */
    public function deleteuser(string $email)
    {
        $params = json_encode(['email' => $email]);
        $response = $this->api_call('customer/deleteCustomer', $params, 'POST');
        if (!$response) {
            $responseBody = self::API_ERROR;
        } else {
            $responseBody = $response['content'];
        }

        return new ApiResponse($responseBody);
    }

    /**
     * Function will cancel license
     * @param string $lkey
     * @return json string
     */
    public function deactivatelicense(string $lkey)
    {
        $params = json_encode(['licenses' => [$lkey]]);
        $response = $this->api_call('customer/deactivatelicense', $params, 'PUT');
        if (!$response) {
            $responseBody = self::API_ERROR;
        } else {
            $responseBody = $response['content'];
        }

        return new ApiResponse($responseBody);
    }

    /**
     * It will fetch the license info for particular license key
     * @param string $lkey
     * @return json string
     */
    public function getlicenseinfo(string $lkey)
    {
        $params = "";
        $response = $this->api_call('customer/showLicenceByKey?licenseKey=' . $lkey, $params, 'GET');
        if (!$response) {
            $responseBody = self::API_ERROR;
        } else {
            $responseBody = $response['content'];
        }

        return new ApiResponse($responseBody);
    }

    /**
     * Get site by user email
     * @param string $email
     * @return json string
     */
    public function getsites(string $email)
    {
        $params = "";
        $response = $this->api_call('siteprovision/item/getByCustomer?customerEmail=' . $email, $params, 'GET');
        if (!$response) {
            $responseBody = self::API_ERROR;
        } else {
            $responseBody = $response['content'];
        }

        return new ApiResponse($responseBody);
    }

    /**
     * function will provision site with for license  in Cwatch
     * @param array $params
     * @return json string
     */
    public function addsite($params)
    {
        $response = $this->api_call('siteprovision/add', json_encode([$params]), 'POST');
        if (!$response) {
            $responseBody = self::API_ERROR;
        } else {
            $responseBody = $response['content'];
        }

        return new ApiResponse($responseBody);
    }
    /**
     * function will allow to add  site for malware scanner
     * @param array $params
     * @return json string
     */
    public function addScanner($params)
    {
        $response = $this->api_call('/malware/enableScanner', json_encode([$params]), 'POST');
        if (!$response) {
            $responseBody = self::API_ERROR;
        } else {
            $responseBody = $response['content'];
        }

        return new ApiResponse($responseBody);
    }
    /**
     * function will allow to check malware  scan status
     * @param array $params
     * @return json string
     */
    public function getScanner($site)
    {
        $response = $this->api_call('/malware/getScannerStatus?site='.$site, '', 'GET');
        if (!$response) {
            $responseBody = self::API_ERROR;
        } else {
            $responseBody = $response['content'];
        }

        return new ApiResponse($responseBody);
    }
    /**
     * assign license to user
     * @param string $product
     * @param string $term
     * @param string $email
     * @param string $name
     * @param string $surname
     * @param string $country
     * @return APIResponse
     */
    private function distributelicense(string $product, string $term, string $email, string $name, string $surname, string $country)
    {
        $array = ['term' => $term, 'product' => $product, 'customers' => [['surname' => $surname, 'email' => $email, 'country' => $country,'name' => $name]],'autoLicenseUpgrade' => false,'renewAutomatically' => false];
        $params = json_encode($array);
        $response = $this->api_call('customer/distributeLicenseForCustomers', $params, 'POST');
        if (!$response) {
            $responseBody = self::API_ERROR;
        } else {
            $responseBody = $response['content'];
        }

        return new ApiResponse($responseBody);
    }

    /**
     * send request to cwatch server
     * @param string $route
     * @param string $body
     * @param string $method  (POST,GET,PUT)
     * @return array
     */
    private function api_call(string $route, string $body, $method)
    {
        $url = $this->API_URL . "/{$route}";
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

        $headers = array();
        $headers[] = 'Authorization: ' . $this->token;
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $headers = [];
        $data = explode("\n", $result);
        $headers['status'] = $data[0];
        array_shift($data);

        foreach ($data as $part) {
            $middle = explode(":", $part);
            $headers[trim($middle[0])] = trim($middle[1]);
        }
        // Return request response
        $response = array(
            'content' => $data[count($data) - 1],
            'headers' => $headers['Authorization']
        );
        return $response;
    }

}