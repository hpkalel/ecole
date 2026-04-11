<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $fillable = ['name', 'is_active', 'start_date', 'end_date'];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function enrollments() {
        return $this->hasMany(StudentEnrollment::class);
    }
    public function assignments() {
        return $this->hasMany(Assignment::class);
    }
}