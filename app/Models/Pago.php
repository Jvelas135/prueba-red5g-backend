<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'documento',
        'nombre',
        'correo',
        'fecha_pago',
        'fecha_limite',
        'fecha_limite',
        'id_pago',
        'estado_pago',
    ];
}
