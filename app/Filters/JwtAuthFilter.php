<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if (empty($header))
        {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                'status' => 'error',
                'message' => 'Missing token'
            ]);
        }

        $token = str_replace('Bearer ', '', $header);
        try
        {
            $jwtSecret = hex2bin(env('JWT_SECRET_KEY'));

            $decoded = JWT::decode($token, 
                        new Key($jwtSecret,'HS256')
            );
            $_SERVER['JWT_USER'] = json_encode($decoded);
        }
        catch (Exception $e)
        {
            return service('response')
                    ->setStatusCode(401)
                    ->setJSON(['status' => 'error',
                            'message' => 'Invalid token'
                    ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}