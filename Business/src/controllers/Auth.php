<?php

declare(strict_types=1);

namespace App\controllers;

use Firebase\JWT\JWT;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\Services\DataService;

final class Auth {
    public function __construct(private ContainerInterface $container) {}

    public function login(Request $request, Response $response): Response {

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if($username === '' || $password === ''){
            return $this->json($response, [
                'error' => 'El username y la contraseña son obligatorios'
                ], 422);
        }

        try{
            $service = $this->container->get(DataService::class);
            $upstream = $service->post('/auth/credentials', json_encode([
                'username' => $username,
                'password' => $password
            ], JSON_THROW_ON_ERROR));
            $credentials = json_decode((string) $upstream->getBody(), true);

            if($upstream->getStatusCode() !== 200 || !is_array($credentials)){
                return $this->json($response, [
                    'error' => 'Credenciales inválidas'
                ], 401);
            }

            $now = time();
            $config = $this->container->get('config');
            $payload = [
                'iss' => 'citas-medicas-business',
                'iat' => $now,
                'exp' => $now + $config->jwt_ttl,
                'sub' => (string) $credentials['id'],
                'username' => $credentials['username'],
                'rol_id' => (int) $credentials['rol_id'],
            ];
            $payloadRef = [
                'iss' => 'citas-medicas-business',
                'iat' => $now,
                'sub' => (string) $credentials['id'],
            ];

            $token = JWT::encode($payload, $config->key, 'HS256');
            $tkRef = JWT::encode($payloadRef, $config->key, 'HS256');

            $upstream = $service->put('/auth/tkref/'.$credentials['id'], json_encode([
                'tkRef' => $tkRef
            ], JSON_THROW_ON_ERROR));

            return $this->json($response, [
                'token' => $token,
                'tkRef' => $tkRef,
            ],200);

        } catch (ConnectException){
            return $this->json($response,['error' => 'El servicio de datos no esta disponible.'], 502);
        } catch (RequestException){
            return $this->json($response,['error' => 'El servicio de datos no esta disponible.'], 502);
        }

    }

    public function loggout(Request $request, Response $response, $args): Response {
        try {
            $service = $this->container->get(DataService::class);
            $upstream = $service->put('auth/tkref/'.$args['id'], json_encode([
                'tkRef' => ""
            ], JSON_THROW_ON_ERROR));

            return $this->json($response, ['msj' => "Sesión cerrada"], 200);

        } catch (ConnectException) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible.']); 
        } catch (RequestException) {
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos.']);
        } 
    }


    private function json(Response $response, array $payload, int $status): Response {
        
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }

}