<?php
declare (strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;

final class MedicoValidator {
    private function reglas(): array {
        return [
            'especialidad_id' => [
                'regla' => v::intType()->positive(),
                'mensaje' => 'Debe seleccionar una especialidad válida.',
            ],
            'nombre_completo' => [
                'regla' => v::stringType()
                    ->notBlank()
                    ->length(v::between(11, 150))
                    ->regex('/^[A-Za-zÑñÁÉÍÓÚáéíóú]{2,}(?: [A-Za-zÑñÁÉÍÓÚáéíóú]{2,}){1,3}$/u'),
                'mensaje' => 'El nombre completo es obligatorio y debe tener entre 11 y 150 caracteres, solo letras y espacios.',
            ],
            'licencia' => [
                'regla' => v::stringType()->notBlank()->length(v::between(1, 50)),
                'mensaje' => 'La licencia es obligatoria y debe tener entre 1 y 50 caracteres.',
            ],
            'username' => [
                'regla' => v::stringType()->notBlank()->length(v::between(8, 16))->regex('/^[a-z0-9]{8,16}$/'),
                'mensaje' => 'El username debe tener entre 8 y 16 caracteres y solo letras minúsculas y números, sin espacios.',
            ],
            'password' => [
                'regla' => v::stringType()
                    ->notBlank()
                    ->length(v::between(8, 64))
                    ->regex('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[#$@])[\s\S]{8,64}$/'),
                'mensaje' => 'La contraseña debe tener entre 8 y 64 caracteres, incluir mayúscula, minúscula, número y un símbolo especial (#$@).',
            ],
            'telefono' => [
                'regla' => v::stringType()->regex('/^[2-9][0-9]{3}-[0-9]{4}$/')->length(v::equals(9)),
                'mensaje' => 'El teléfono debe tener el formato ####-#### incluyendo el guión.',
            ],
        ];
    }

    public function validate(array $data): array {
        $errores = [];

        foreach ($this->reglas() as $campo => $config) {
            $valor = $data[$campo] ?? null;
            $regla = $config['regla'];
            $mensaje = $config['mensaje'];

            if ($campo === 'telefono' && ($valor === null || $valor === ""))
                continue;

            if (!$regla->isValid($valor)) {
                $errores[$campo] = $mensaje;
            }
        }

        return $errores;
    }
}