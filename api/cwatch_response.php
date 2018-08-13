<?php

class APIResponse {
    public $code;
    public $errorMsg;

    /**
     * APIResponse constructor.
     * @param string $msg
     */
    public function __construct(string $msg)
    {
        $response = json_decode($msg);
        if (!isset($response->error)) {
            if(empty($response->validationErrors)) {
               $this->code = 200;
            } else {
                $this->code = 500;
                $this->errorMsg = $response->validationErrors;
            }
        } else {
            $this->code = $response->status;
            $this->errorMsg = $response->message;
        }
    }
}