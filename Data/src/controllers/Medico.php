<?php

namespace App\Controllers;

use App\Models\Medico as MedicoModel;
use App\Models\User;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;


class Medico {
    private ContainerInterface $container;

    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }

    public function read(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');
        if(isset($args['id'])){
            $medicos = MedicoModel::query()
                ->select(['medicos.id', 'especialidad_id','especialidades.nombre as nombre_especialidad', 'nombre_completo', 'licencia', 'telefono'])
                ->join('especialidades', 'medicos.especialidad_id','=', 'especialidades.id')
                ->find($args['id']); #find busca un elemento

            return $this->json($response, ['data' => $medicos], 200);
        }

        $medicos = MedicoModel::query()
                ->select(['id', 'especialidad_id', 'nombre_completo', 'licencia', 'telefono'])
                ->get()
                ->toArray();
            return $this->json($response, ['data' => $medicos], 200);
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
        $license = trim((string) ($params['licencia'] ?? $params['cedula']));
        $specialty = trim((string) ($params['especialidad'] ?? null));

        $query = MedicoModel::query()
            ->select([
                'medicos.id',
                'medicos.nombre_completo',
                'medicos.especialidad_id',
                'especialidades.nombre as especialidad_nombre',
                'medicos.licencia',
                'medicos.telefono'
            ])
            ->join('especialidades', 'medicos.especialidad_id', '=', 'especialidades.id');

            if($name !== '') {
                $query->where('medicos.nombre_completo', 'like', "%$name%");
            }

            if($license !== '') {
                $query->where('medicos.licencia', 'like', "%$license%");
            }

            if($specialty !== null && $specialty !== '') {
                $query->where('especialidades.nombre', 'like', "%$specialty%");
            }

            $total = (clone $query)->count('medicos.id');

            $medicos = $query
                ->orderBy('medicos.nombre_completo')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->toArray();

            return $this->json($response, [
                'data' => $medicos, 
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
        $specialtyId = filter_var($data['especialidad_id'] ?? null, FILTER_VALIDATE_INT);
        $fullName = trim((string) ($data['nombre_completo'] ?? ''));
        $license = trim((string) ($data['licencia'] ?? ''));
        $phone = isset($data['telefono']) ? trim((string) $data['telefono']) : null; #el isset verifica si la variable existe, si no existe, asigna null
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($specialtyId === false || $specialtyId === null || $specialtyId < 1) {
            $errors['especialidad_id'] = 'Debe ser un entero positivo.';
        }
        if ($fullName === '' || mb_strlen($fullName) > 150) { #mb_strlen() cuenta los caracteres de una cadena, considerando caracteres multibyte
            $errors['nombre_completo'] = 'Es obligatorio y debe tener máximo 150 caracteres.';
        }
        if ($license === '' || mb_strlen($license) > 50) {
            $errors['licencia'] = 'Es obligatoria y debe tener máximo 50 caracteres.';
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
            $medico = $eloquent->connection()->transaction(
                static function () use ($specialtyId, $fullName, $license, $phone, $username, $password): MedicoModel {
                    $roleId = User::query()
                        ->from('roles')
                        ->where('nombre', 'Médico')
                        ->value('id');

                    if ($roleId === null) {
                        throw new \RuntimeException('El rol Médico no está configurado.');
                    }

                    $usuario = User::create([
                        'username' => $username,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'rol_id' => $roleId,
                        'activo' => true,
                    ]);

                    return MedicoModel::create([
                        'usuario_id' => $usuario->id,
                        'especialidad_id' => $specialtyId,
                        'nombre_completo' => $fullName,
                        'licencia' => $license,
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

                return $this->json($response, ['error' => 'La licencia ya está registrada.'], 409);
            }

            throw $exception;
        }

        return $this->json($response, ['data' => $medico->toArray()], 201); #201 significa que el recurso fue creado exitosamente
    }

    public function update(Request $request, Response $response, array $args): Response {
        // Implementation for updating a Medico record would go here.
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $medico = MedicoModel::find($args['id']); #ORM, busca el medico

            if ($medico === null) {
                return $this->json($response, ['error' => 'Médico no encontrado.'], 404);
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
                $medico->nombre_completo = $fullName;
            }

            if (isset($data['telefono'])) {
                $phone = trim((string) $data['telefono']);
            if($phone !== null && mb_strlen($phone) > 20) {
                return $this->json($response, ['error' => 'Teléfono inválido.'], 422);
            }
                $medico->telefono = $phone;
            }


            if (isset($data['especialidad_id'])) {
                $specialtyId = filter_var($data['especialidad_id'] ?? null, FILTER_VALIDATE_INT);
            if($specialtyId === false || $specialtyId === null || $specialtyId < 1) {
                return $this->json($response, ['error' => 'Especialidad inválida.'], 422);
            }
                $medico->especialidad_id = $specialtyId;
            }


            if (isset($data['licencia'])) {
                $license = trim((string) ($data['licencia'] ?? ''));
            if($license === '' || mb_strlen($license) > 50) {
                return $this->json($response, ['error' => 'Licencia inválida.'], 422);
            }

                $medico->licencia = $license;
            }

            $medico->save();

            return $this->json($response, ['data' => $medico->toArray()], 200);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        // Implementation for deleting a Medico record would go here.
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $medico = MedicoModel::find($args['id']); #ORM, busca el medico

            if ($medico === null) {
                return $this->json($response, ['error' => 'Médico no encontrado.'], 404);
            }

            #Verificar si el médico a eliminar tenga citas asignadas
            $citasCount = $eloquent->table('citas')
                ->where('medico_id', $medico->id)
                ->count();

            if ($citasCount > 0){
                return $this->json($response, ['error' => 'No se puede eliminar el médico porque tiene citas asignadas.'], 409);
            }

            $eloquent->connection()->transaction(function() use ($medico) : void{
                //Obtener el id del usuarioId asociado al médico antes de eliminarlo
                $usuarioId = $medico->usuario_id;
                //Eliminar médico
                $medico->delete();

                //Eliminar el usuario asociado al médico
                if ($usuarioId !== null) {
                    $usuario = User::find($usuarioId);
                    if ($usuario !== null) {
                        $usuario->delete();
                    }
                }
            });

            return $this->json($response, ['message' => 'Médico eliminado correctamente.'], 200);
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