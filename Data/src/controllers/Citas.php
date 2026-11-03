<?php

declare(strict_types=1);

namespace App\controllers;

use Illuminate\Database\QueryException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

    class Citas
    {
        
        
        public function __construct(private ContainerInterface $container){
        }

        public function create(Request $request, Response $response, array $args): Response{
            $eloquent = $this->container->get('eloquent');
            $data = $request->getParsedBody();
            $data = is_array($data) ? $data : [];

            $patientId = filter_var($data['paciente_id'] ?? null, FILTER_VALIDATE_INT);
            $doctorId = filter_var($data['medico_id'] ?? null, FILTER_VALIDATE_INT);
            $dateTime = trim((string) ($data['fecha_hora'] ?? ''));
            $reason = isset($data['motivo']) ? trim((string) $data['motivo']) : null;

            $errors = [];
            if ($patientId === false || $patientId < 1) {
                $errors['paciente_id'] = 'Debe ser un entero positivo.';
            }
            if ($doctorId === false || $doctorId < 1) {
                $errors['medico_id'] = 'Debe ser un entero positivo.';
            }
            $parsedDateTime = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $dateTime);
            $dateErrors = \DateTimeImmutable::getLastErrors();
            if (
                $parsedDateTime === false
                || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
                || $parsedDateTime->format('Y-m-d H:i:s') !== $dateTime
            ) {
                $errors['fecha_hora'] = 'Debe tener el formato Y-m-d H:i:s.';
            }
            if ($reason !== null && $reason === '') {
                $reason = null;
            }

            if ($errors !== []) {
                return $this->json($response, ['errors' => $errors], 422);
            }

            try {
                $eloquent->connection()->statement( //Llamado para hacer un procedimiento almacenado en la base de datos
                    'CALL sp_crear_cita(?, ?, ?, ?, @resultado)',
                    [$patientId, $doctorId, $dateTime, $reason]
                );
                $result = $eloquent->connection()->selectOne('SELECT @resultado AS resultado');
                $message = (string) ($result->resultado ?? '');
            } catch (QueryException $exception) {
                return $this->json($response, [
                    'error' => 'No fue posible agendar la cita. Verifique que el paciente y el médico existan.',
                ], 422);
            }

            if (str_starts_with($message, 'Error:')) {
                return $this->json($response, ['error' => $message], 409);
            }

            return $this->json($response, ['message' => $message], 201);
        }

        public function readPaciente(Request $request, Response $response, array $args): Response
        {
            $patientId = filter_var($args['id'] ?? $request->getQueryParams()['paciente_id'] ?? null, FILTER_VALIDATE_INT);
            if ($patientId === false || $patientId < 1) {
                return $this->json($response, ['error' => 'paciente_id debe ser un entero positivo.'], 422);
            }

            $eloquent = $this->container->get('eloquent');
            $citas = $eloquent->connection()->select(
                'CALL sp_obtener_citas_paciente(?)',
                [$patientId]
            );
            return $this->json($response, ['data' => $citas], 200);
        }

        public function readMedico(Request $request, Response $response, array $args): Response{
        $doctorId = filter_var($args['id'] ?? $request->getQueryParams()['medico_id'] ?? null, FILTER_VALIDATE_INT);
        if ($doctorId === false || $doctorId < 1) {
            return $this->json($response, ['error' => 'medico_id debe ser un entero positivo.'], 422);
        }

        $eloquent = $this->container->get('eloquent');
        $citas = $eloquent->connection()->select(
            'CALL sp_obtener_citas_medico(?)',
            [$doctorId]
        );
        return $this->json($response, ['data' => $citas], 200);
        }

        private function json(Response $response, array $payload, int $status): Response
        {
            $response->getBody()->write(json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ));

            return $response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withStatus($status);
        }
    }