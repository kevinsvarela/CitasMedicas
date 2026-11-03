<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PacienteModel extends Model {
    protected $table = 'pacientes';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'cedula',
        'nombre_completo',
        'fecha_nacimiento',
        'telefono',
    ];

    protected $casts = [
        'id' => 'integer',
        'usuario_id' => 'integer',
    ];

    public function usuario(){
        return $this->belongsTo(User::class, 'usuario_id');
    }
}