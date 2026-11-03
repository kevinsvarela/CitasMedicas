<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitasModel extends Model {
    protected $table = 'citas';

    public $timestamps = false;

    protected $fillable = [
        'paciente_id',
        'medico_id',
        'fecha_hora',
        'estado',
        'motivo',
    ];

    protected $casts = [
        'id' => 'integer',
        'paciente_id' => 'integer',
        'medico_id' => 'integer',
    ];

}