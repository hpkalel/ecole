<?php
$modelsDir = __DIR__ . '/app/Models';

$models = [
    'User' => <<<'EOT'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'nom',
        'role',
        'is_active',
        'code_invitation',
        'grade',
        'statut',
        'corps',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
    
    public function assignments() {
        return $this->hasMany(Assignment::class, 'prof_id');
    }
}
EOT,
    'SchoolYear' => <<<'EOT'
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
EOT,
    'Classe' => <<<'EOT'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    protected $fillable = ['nom'];

    public function enrollments() {
        return $this->hasMany(StudentEnrollment::class);
    }
    public function assignments() {
        return $this->hasMany(Assignment::class);
    }
}
EOT,
    'Student' => <<<'EOT'
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
EOT,
    'StudentEnrollment' => <<<'EOT'
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
EOT,
    'Subject' => <<<'EOT'
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
EOT,
    'Assignment' => <<<'EOT'
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
EOT,
    'Invitation' => <<<'EOT'
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
EOT,
    'Evaluation' => <<<'EOT'
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
EOT,
    'Grade' => <<<'EOT'
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
EOT
];

foreach ($models as $name => $content) {
    file_put_contents("$modelsDir/$name.php", $content);
    echo "Updated Model $name\n";
}
echo "Models generated.\n";
