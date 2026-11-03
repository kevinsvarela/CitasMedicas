<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\Medico;

$app->get('/', function (Request $request, Response $response, array $args) {

    $response->getBody()->write("Hola Slim");
    return $response;
});

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

$app->group('/api', function(RouteCollectorProxy $api) {
    $api->group('/auth', function(RouteCollectorProxy $endpoint) {
        $endpoint->post('/credentials', Auth::class . ':credentials');
        $endpoint->put('/tkref/{id}', Auth::class . ':updateTokenRef');
    });
    $api->group('/medicos', function (RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Medico::class . ':read');
        $endpoint->get('/filter/{offset}/{limit}', Medico::class . ':filter');
        $endpoint->post('', Medico::class . ':create');
        $endpoint->put('/{id}', Medico::class . ':update');
        $endpoint->delete('/{id}', Medico::class . ':delete');
    });
    $api->group('/pacientes', function (RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Paciente::class . ':read');
        $endpoint->get('/filter/{offset}/{limit}', Paciente::class . ':filter');
        $endpoint->post('', Paciente::class . ':create');
        $endpoint->put('/{id}', Paciente::class . ':update');
        $endpoint->delete('/{id}', Paciente::class . ':delete');
    });
    $api->group('/citas', function(RouteCollectorProxy $endpoint) {
        $endpoint->get('/paciente/{id}', Citas::class . ':readPaciente');
        $endpoint->get('/medico/{id}', Citas::class . ':readMedico');
        $endpoint->post('', Citas::class . ':create');
        $endpoint->put('/{id}', Citas::class . ':update');
    });
});