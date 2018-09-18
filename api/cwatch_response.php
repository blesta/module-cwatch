<?php

class CwatchResponse
{

    public $code;
    public $errorMsg;
    public $resp;

    /**
     * CwatchResponse constructor.
     * @param string $msg
     */
    public function __construct($msg)
    {
        $response = json_decode($msg);
        if (!isset($response->error)) {
            if (empty($response->validationErrors)) {
                $this->code = 200;
                $this->resp = $msg;
            } else {
                $this->code = 500;
                $this->errorMsg = $response->validationErrors;
            }
        } else {
            $this->code = $response->status;
            $this->errorMsg = $response->message;
            $this->resp = $msg;
        }
    }

}