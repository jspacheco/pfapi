<?php
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
class BaseApiController extends ResourceController
{
    protected $jwtUser;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);      
        // $this->jwtUser = service('request')->jwtUser;
        $this->jwtUser = json_decode($_SERVER['JWT_USER'] ?? '{}');        
    }
}