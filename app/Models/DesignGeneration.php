<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignGeneration extends Model
{
    protected $table = 'design_generations';
    protected $fillable = [
        'prompt',
        'image_url',
        'task_id',
        'error'
    ];
}
