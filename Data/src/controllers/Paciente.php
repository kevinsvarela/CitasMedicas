<?php

namespace App\Controllers;

use App\Models\PacienteModel;
use App\Models\User;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;


class Paciente {
    private ContainerInterface $container;

    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }

    public function read(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');
        if(isset($args['id'])){
            $pacientes = PacienteModel::query()
                ->select(['pacientes.id', 'cedula', 'nombre_completo', 'fecha_nacimiento', 'telefono'])
                ->find($args['id']); #find busca un elemento

            return $this->json($response, ['data' => $pacientes], 200);
        }

        $pacientes = PacienteModel::query()
                ->select(['id', 'cedula', 'nombre_completo', 'fecha_nacimiento', 'telefono'])
                ->get()
                ->toArray();
            return $this->json($response, ['data' => $pacientes], 200);
    }

    public function filter(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');
        
        $offset = filter_var($args['offset'] ?? 0, FILTER_VALIDATE_INT);
        $limit = filter_var($args['limit'] ?? 20, FILTER_VALIDATE_INT);

        if($offset === false || $offset < 0 || $limit === false || $limit < 1 || $limit >100){
            return $this->json($response, 
            ['error' => 'El offset debe ser mayor o igual 0 y el límite debe estar entre 1 y 100.'], 422);
        }

        $params = $request->getQueryParams();
        $name = trim((string) ($params['nombre'] ?? $params['nombre_completo']));
        $identity_card = trim((string) ($params['cedula'] ?? ''));

        $query = PacienteModel::query()
            ->select([
                'pacientes.id',
                'pacientes.nombre_completo',
                'pacientes.cedula',
                'pacientes.fecha_nacimiento',
                'pacientes.telefono'
            ]);

            if($name !== '') {
                $query->where('pacientes.nombre_completo', 'like', "%$name%");
            }

            if($identity_card !== '') {
                $query->where('pacientes.cedula', 'like', "%$identity_card%");
            }

            $total = (clone $query)->count('pacientes.id');

            $pacientes = $query
                ->orderBy('pacientes.nombre_completo')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->toArray();

            return $this->json($response, [
                'data' => $pacientes, 
                'pagination' => [
                        'offset' => $offset, 
                        'limit' => $limit, 
                        'total' => $total
                    ]
                ] , 200);

    }

    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody(); #convierte los datos en arreglo

        $data = is_array($data) ? $data : []; #si es null, asigna un arreglo vacío

        #var_dump($data); #muestra el contenido de $data
        #die(); 

        $errors = [];
        $fullName = trim((string) ($data['nombre_completo'] ?? ''));
        $identitycard = trim((string) ($data['cedula'] ?? ''));
        $dateOfBirth = trim((string) ($data['fecha_nacimiento'] ?? ''));
        $phone = isset($data['telefono']) ? trim((string) $data['telefono']) : null; #el isset verifica si la variable existe, si no existe, asigna null
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');


        if ($fullName === '' || mb_strlen($fullName) > 150) { #mb_strlen() cuenta los caracteres de una cadena, considerando caracteres multibyte
            $errors['nombre_completo'] = 'Es obligatorio y debe tener máximo 150 caracteres.';
        }
        if ($identitycard === '' || mb_strlen($identitycard) > 12) {
            $errors['cedula'] = 'Es obligatoria y debe tener máximo 12 caracteres.';
        }
        if ($dateOfBirth === '' || mb_strlen($dateOfBirth) > 10) {
            $errors['fecha_nacimiento'] = 'Es obligatoria y debe tener máximo 10 caracteres.';
        }
        if ($username === '' || mb_strlen($username) > 50) {
            $errors['username'] = 'Es obligatorio y debe tener máximo 50 caracteres.';
        }
        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Debe tener al menos 8 caracteres.';
        }
        if ($phone !== null && $phone === '') {
            $phone = null;
        } elseif ($phone !== null && mb_strlen($phone) > 20) {
            $errors['telefono'] = 'Debe tener máximo 20 caracteres.';
        }

        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        try {
            $eloquent = $this->container->get('eloquent');
            $pacientes = $eloquent->connection()->transaction(
                static function () use ($fullName, $identitycard, $dateOfBirth, $phone, $username, $password): PacienteModel {
                    $roleId = User::query()
                        ->from('roles')
                        ->where('nombre', 'Paciente')
                        ->value('id');

                    if ($roleId === null) {
                        throw new \RuntimeException('El rol Paciente no está configurado.');
                    }

                    $usuario = User::create([
                        'username' => $username,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'rol_id' => $roleId,
                        'activo' => true,
                    ]);

                    return PacienteModel::create([
                        'usuario_id' => $usuario->id,
                        'cedula' => $identitycard,
                        'nombre_completo' => $fullName,
                        'fecha_nacimiento' => $dateOfBirth,
                        'telefono' => $phone,
                    ]);
                }
            );
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { #1062 es el código de error de MySQL por duplicate entry
                $message = (string) ($exception->errorInfo[2] ?? '');

                if (str_contains($message, 'username')) { #verifica si el error corresponde al nombre de usuario duplicado
                    return $this->json($response, ['error' => 'El nombre de usuario ya está registrado.'], 409);
                }

                return $this->json($response, ['error' => 'La cedula ya está registrada.'], 409);
            }

            throw $exception;
        }

        return $this->json($response, ['data' => $pacientes->toArray()], 201); #201 significa que el recurso fue creado exitosamente
    }

    public function update(Request $request, Response $response, array $args): Response {
        // Implementation for updating a Paciente record would go here.
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $paciente = PacienteModel::find($args['id']); #ORM, busca el paciente

            if ($paciente === null) {
                return $this->json($response, ['error' => 'Paciente no encontrado.'], 404);
            }

            $data = $request->getParsedBody();
            $data = is_array($data) ? $data : [];

            // Validate and update fields as necessary
            // For example:
            if (isset($data['nombre_completo'])) {
                $fullName = trim((string) $data['nombre_completo']);
                if ($fullName === '' || mb_strlen($fullName) > 150) {
                    return $this->json($response, ['error' => 'Nombre completo inválido.'], 422);
                }
                $paciente->nombre_completo = $fullName;
            }

            if (isset($data['telefono'])) {
                $phone = trim((string) $data['telefono']);

            if($phone !== null && mb_strlen($phone) > 20) {
                return $this->json($response, ['error' => 'Teléfono inválido.'], 422);
            }

                $paciente->telefono = $phone;
            }

            $paciente->save();

            return $this->json($response, ['data' => $paciente->toArray()], 200);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        // Implementation for deleting a Paciente record would go here.
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $paciente = PacienteModel::find($args['id']); #ORM, busca el paciente

            if ($paciente === null) {
                return $this->json($response, ['error' => 'Paciente no encontrado.'], 404);
            }

            #Verificar si el paciente a eliminar tenga citas asignadas
            $citasCount = $eloquent->table('citas')
                ->where('paciente_id', $paciente->id)
                ->count();

            if ($citasCount > 0){
                return $this->json($response, ['error' => 'No se puede eliminar el paciente porque tiene citas asignadas.'], 409);
            }

            $eloquent->connection()->transaction(function() use ($paciente) : void{
                //Obtener el id del usuarioId asociado al paciente antes de eliminarlo
                $usuarioId = $paciente->usuario_id;
                //Eliminar paciente
                $paciente->delete();

                //Eliminar el usuario asociado al paciente
                if ($usuarioId !== null) {
                    $usuario = User::find($usuarioId);
                    if ($usuario !== null) {
                        $usuario->delete();
                    }
                }
            });

            return $this->json($response, ['message' => 'Paciente eliminado correctamente.'], 200);
        }
    }


    private function json(Response $response, array $payload, int $status): Response{
    $response->getBody()->write(json_encode($payload,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withStatus($status);
    }
    
}