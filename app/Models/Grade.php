<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['student_id', 'evaluation_id', 'valeur', 'appreciation'];

    public function student() {
        return $this->belongsTo(Student::class);
    }
    public function evaluation() {
        return $this->belongsTo(Evaluation::class);
    }
}