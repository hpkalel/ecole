<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $fillable = ['code', 'is_used'];

    protected $casts = [
        'is_used' => 'boolean',
    ];
}