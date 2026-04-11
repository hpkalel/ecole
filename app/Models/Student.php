<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['matricule', 'nom', 'prenom', 'sexe'];

    public function enrollments() {
        return $this->hasMany(StudentEnrollment::class);
    }
    public function grades() {
        return $this->hasMany(Grade::class);
    }
}