<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
        protected $fillable = ['title', 'user_id'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
