<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = ['prof_id', 'subject_id', 'class_id', 'school_year_id', 'coefficient'];

    public function prof() {
        return $this->belongsTo(User::class, 'prof_id');
    }
    public function subject() {
        return $this->belongsTo(Subject::class);
    }
    public function classe() {
        return $this->belongsTo(Classe::class, 'class_id');
    }
    public function schoolYear() {
        return $this->belongsTo(SchoolYear::class);
    }
    public function evaluations() {
        return $this->hasMany(Evaluation::class);
    }
}