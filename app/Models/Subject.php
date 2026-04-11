<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['nom', 'coefficient'];

    public function assignments() {
        return $this->hasMany(Assignment::class);
    }
}