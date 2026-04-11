<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassPrincipal extends Model
{
    use HasFactory;

    protected $fillable = [
        'classe_id',
        'prof_id',
        'school_year_id',
    ];

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function prof()
    {
        return $this->belongsTo(User::class, 'prof_id');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
