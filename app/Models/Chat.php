<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
        // Para pruebas sin login, user_id puede ser null
        // Cuando se implemente login, restaurar user_id obligatorio
        protected $fillable = ['title'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
