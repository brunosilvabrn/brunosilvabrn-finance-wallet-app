<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\JWT as JWTConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends BaseController
{
    use ResponseTrait;

    protected $userModel;
    protected $jwtConfig;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->jwtConfig = config(JWTConfig::class);
    }

    public function register()
    {
        $data = $this->request->getJSON(true); 

        if (!$this->userModel->insert($data)) {
            return $this->failValidationErrors($this->userModel->errors());
        }

        return $this->respondCreated(['message' => 'Usuário registrado com sucesso']);
    }

    public function login()
    {
        $data = $this->request->getJSON(true); 

        if (empty($data)) {
            $data = (array) $this->request->getJSON(true);
        }

        $email    = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        $user = $this->userModel->where('email', $email)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Credenciais inválidas');
        }

        // Payload do token
        $now   = time();
        $exp   = $now + $this->jwtConfig->ttl;
        $payload = [
            'iss' => base_url(),      
            'aud' => base_url(),      
            'iat' => $now,            
            'nbf' => $now,           
            'exp' => $exp,           
            'data' => [
                'id'    => $user['id'],
                'email' => $user['email'],
                'name'  => $user['name'],
            ],
        ];

        // Gera o token
        $token = JWT::encode($payload, $this->jwtConfig->secret, 'HS256');

        $cookie = [
            'name'     => 'jwt_token',
            'value'    => $token,
            'expire'   => $this->jwtConfig->ttl,     
            'httpOnly' => true,                      
            'secure'   => false,                    
        ];

        response()->setCookie($cookie);

        return $this->respond([
            'message' => 'Login bem-sucedido',
            'token'   => $token,
            'expires_in' => $this->jwtConfig->ttl,
        ]);
    }

    public function logout()
    {
        setcookie(
            'jwt_token',    
            '',             
            [
                'expires'  => time() - 3600,  
                'path'     => '/', 
                'secure'   => false,        
                'httponly' => true,         
                'samesite' => 'Lax',
            ]
        );

        setcookie('jwt_expires', '', time() - 3600, '/');

        return $this->respond([
            'message' => 'Logout bem-sucedido',
        ]);
    }
}