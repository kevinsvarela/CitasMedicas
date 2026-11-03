<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medico extends Model {
    protected $table = 'medicos';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'especialidad_id',
        'nombre_completo',
        'licencia',
        'telefono',
    ];

    protected $casts = [
        'id' => 'integer',
        'usuario_id' => 'integer',
        'especialidad_id' => 'integer',
    ];

    public function usuario(){
        return $this->belongsTo(User::class, 'usuario_id');
    }
}