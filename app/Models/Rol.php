<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Rol extends Model
{
    use HasFactory;
    public function leerRoles(){
        $sql = sprintf("SELECT * FROM roles");
        $roles = DB::select($sql);

        return $roles;
    }
}
