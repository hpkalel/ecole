<?php

$migrationsDir = __DIR__ . '/database/migrations';
$files = scandir($migrationsDir);

$schemas = [
    'users' => <<<'EOT'
$table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('nom');
            $table->enum('role', ['admin', 'prof'])->default('prof');
            $table->boolean('is_active')->default(true);
            $table->string('code_invitation', 20)->nullable();
            $table->string('grade', 100)->nullable();
            $table->string('statut', 100)->nullable();
            $table->string('corps', 100)->nullable();
            $table->rememberToken();
            $table->timestamps();
EOT,
    'school_years' => <<<'EOT'
$table->id();
            $table->string('name', 20)->unique();
            $table->boolean('is_active')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
EOT,
    'classes' => <<<'EOT'
$table->id();
            $table->string('nom', 50)->unique();
            $table->timestamps();
EOT,
    'students' => <<<'EOT'
$table->id();
            $table->string('matricule', 50)->unique()->nullable();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->enum('sexe', ['M', 'F'])->default('M');
            $table->timestamps();
EOT,
    'student_enrollments' => <<<'EOT'
$table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained()->onDelete('cascade');
            $table->enum('statut', ['Nouveau', 'Redoublant'])->default('Nouveau');
            $table->unique(['student_id', 'school_year_id']);
            $table->timestamps();
EOT,
    'subjects' => <<<'EOT'
$table->id();
            $table->string('nom', 100)->unique();
            $table->integer('coefficient')->default(1);
            $table->timestamps();
EOT,
    'assignments' => <<<'EOT'
$table->id();
            $table->foreignId('prof_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained()->onDelete('cascade');
            $table->integer('coefficient')->default(1);
            $table->unique(['subject_id', 'class_id', 'school_year_id']);
            $table->timestamps();
EOT,
    'invitations' => <<<'EOT'
$table->id();
            $table->string('code', 20)->unique();
            $table->boolean('is_used')->default(false);
            $table->timestamps();
EOT,
    'evaluations' => <<<'EOT'
$table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['interrogation', 'devoir']);
            $table->string('nom', 100);
            $table->enum('periode', ['Semestre 1', 'Semestre 2'])->default('Semestre 1');
            $table->date('date')->nullable();
            $table->timestamps();
EOT,
    'grades' => <<<'EOT'
$table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('evaluation_id')->constrained()->onDelete('cascade');
            $table->decimal('valeur', 4, 2);
            $table->text('appreciation')->nullable();
            $table->timestamps();
EOT
];

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    foreach ($schemas as $tableName => $schemaContent) {
        if (strpos($file, "create_{$tableName}_table") !== false) {
            $path = $migrationsDir . '/' . $file;
            $content = file_get_contents($path);
            
            $start = strpos($content, '$table->id();');
            $end = strpos($content, '$table->timestamps();');
            
            if ($start !== false && $end !== false) {
                // Ensure we get the full timestamps(); call
                $end += strlen('$table->timestamps();');
                
                $newContent = substr($content, 0, $start) . $schemaContent . substr($content, $end);
                file_put_contents($path, $newContent);
                echo "Updated {$tableName}\n";
            }
        }
    }
}
echo "Done.\n";
