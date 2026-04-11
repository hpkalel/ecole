<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    protected $fillable = ['nom'];

    public function enrollments() {
        return $this->hasMany(StudentEnrollment::class, 'class_id');
    }
    public function assignments() {
        return $this->hasMany(Assignment::class, 'class_id');
    }
}