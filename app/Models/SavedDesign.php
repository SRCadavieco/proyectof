<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedDesign extends Model
{
    protected $fillable = ['user_id', 'image_data', 'title'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
