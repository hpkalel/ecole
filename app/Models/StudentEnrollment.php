<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    protected $fillable = ['student_id', 'class_id', 'school_year_id', 'statut'];

    public function student() {
        return $this->belongsTo(Student::class);
    }
    public function classe() {
        return $this->belongsTo(Classe::class, 'class_id');
    }
    public function schoolYear() {
        return $this->belongsTo(SchoolYear::class);
    }
}