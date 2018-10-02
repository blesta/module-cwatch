<?php

class CwatchResponse
{
    private $status;
    private $raw;
    private $response;
    private $errors;
    private $headers;

    /**
     * CwatchResponse constructor.
     * @param array $api_response
     */
    public function __construct($api_response)
    {
        $this->raw = $api_response['content'];
        $this->headers = $api_response['headers'];
        $response = json_decode($api_response['content']);
        if (!isset($response->error)) {
            if (empty($response->validationErrors)) {
                $this->status = 200;
                $this->response = $response;
            } else {
                $this->status = 500;
                $this->errors = $response->validationErrors;
            }
        } else {
            $this->status = $response->status;
            $this->errors = $response->message;
            $this->response = $response;
        }
    }

    /**
     * Get the status of this response
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Get the raw data from this response
     */
    public function raw()
    {
        return $this->raw;
    }

    /**
     * Get the data response from this response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Get any errors from this response
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Get the headers returned with this response
     */
    public function headers()
    {
        return $this->headers;
    }
}
