<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $fillable = ['assignment_id', 'type', 'nom', 'periode', 'date'];

    protected $casts = [
        'date' => 'date',
    ];

    public function assignment() {
        return $this->belongsTo(Assignment::class);
    }
    public function grades() {
        return $this->hasMany(Grade::class);
    }
}