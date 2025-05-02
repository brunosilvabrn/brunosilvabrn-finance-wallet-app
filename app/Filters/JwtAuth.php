<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\JWT as JWTConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // $config = config(JWTConfig::class);
        // $authHeader = $request->getHeaderLine('Authorization');

        // if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        //     return service('response')->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
        //                              ->setJSON(['error' => 'Token não fornecido']);
        // }

        // $token = $matches[1];

        $config     = config(JWTConfig::class);
        $token      = service('request')->getCookie('jwt_token');

        if (!$token) {
            // não autenticado: redireciona para login
            return redirect('login');
        }

        try {
            $decoded = JWT::decode($token, new Key($config->secret, 'HS256'));
            // Armazena dados do usuário no request para uso posterior
            $request->user = $decoded->data;
        } catch (\Exception $e) {
            return service('response')->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                                     ->setJSON(['error' => 'Token inválido ou expirado']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // não modifica resposta
    }
}
