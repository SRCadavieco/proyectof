<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintifyConnection extends Model
{
    protected $fillable = [
        'user_id',
        'api_token',
        'shop_id',
        'shop_name',
    ];

    protected $hidden = ['api_token'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
