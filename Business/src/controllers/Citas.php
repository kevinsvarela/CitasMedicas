<?php
namespace App\controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\Services\DataService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class Citas {

    public function __construct(private ContainerInterface $container) {}

    public function readPaciente(Request $request, Response $response, array $args): Response {
        
        try {
            $dataService = $this->container->get(DataService::class);
            $path = isset($args['id']) ? 'citas/paciente/' . rawurlencode($args['id']) : 'citas/paciente';

            $upstream = $dataService->get($path);
            $response->getBody()->write((string) $upstream->getBody());
            return $response
                ->withHeader(
                    'Content-Type', $upstream->getHeaderLine('Content-Type') ?: 'application/json; charset=utf-8')
                ->withStatus($upstream->getStatusCode());
        } catch (ConnectException){
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        }
        catch (RequestException){
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    public function readMedico(Request $request, Response $response, array $args): Response {
        
        try {
            $dataService = $this->container->get(DataService::class);
            $path = isset($args['id']) ? 'citas/medico/' . rawurlencode($args['id']) : 'citas/medico';

            $upstream = $dataService->get($path);
            $response->getBody()->write((string) $upstream->getBody());
            return $response
                ->withHeader(
                    'Content-Type', $upstream->getHeaderLine('Content-Type') ?: 'application/json; charset=utf-8')
                ->withStatus($upstream->getStatusCode());
        } catch (ConnectException){
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        }
        catch (RequestException){
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    public function create(Request $request, Response $response, array $args): Response {
        try {
            $request->getBody()->rewind();
            $body = (string) $request->getBody();

            $data = json_decode($body, true);

            if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
                return $this->json($response, ['errors' => ['body' => 'Debe enviar un objeto JSON válido']]);
            }
  
            $dataService = $this->container->get(DataService::class);
            
            $upstream = $dataService->post('citas', json_encode($data, JSON_THROW_ON_ERROR));
            return $this->json($response, json_decode((string) $upstream->getBody(), true),
                $upstream->getStatusCode() );

        } catch (ConnectException){
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        }
        catch (RequestException){
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }    
    private function json(Response $response, array $payload, int $status): Response {
        $response->getBody()->write(json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}