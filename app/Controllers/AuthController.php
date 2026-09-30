<?php

namespace App\Controllers;

use App\Models\ApiUserModel;
use Firebase\JWT\JWT;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    public function auth_login()
    {
/*        
        $secretHeader = $this->request->getHeaderLine('X-API-SECRET');

        if (!hash_equals(env('API_MASTER_SECRET_HEX'), $secretHeader)) {
            return $this->respond(['status' => 'error',
                                    'message' => 'Invalid secret'
                                ], 401);
        }
*/
        $data = $this->request->getJSON(true);
        $username = $data['username'] ?? '';
        $apikey = $data['apikey'] ?? '';
        $model = new ApiUserModel();

        $user = $model
                ->where('username', $username)
                ->where('api_key', $apikey)
                ->where('active', true)
                ->first();

        if (!$user) {
            return $this->respond([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                    ], 401);
        }

        $jwtSecret = hex2bin(env('JWT_SECRET_KEY'));
        $payload = [
            'iss' => 'pfapi.pfamosup.org',
            'sub' => $user['id'],
            'username' => $username,
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
            'jti' => bin2hex(random_bytes(16))
        ];

        $token = JWT::encode(
            $payload,
            $jwtSecret,
            'HS256'
        );

        return $this->respond([
            'status' => 'success',
            'jwt' => $token
        ]);
    }
}