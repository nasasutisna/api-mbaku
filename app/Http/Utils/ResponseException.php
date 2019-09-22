<?php

namespace App\Http\Utils;

use Exception;

class ResponseException extends Exception
{
    private $response;
    public function __construct($response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
