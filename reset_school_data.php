<?php

/**
 * Reset School Data Script
 * Deletes all school-related data while preserving admin accounts.
 */

use Illuminate\Support\Facades\DB;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Initialisation du nettoyage de la base de données...\n";

DB::beginTransaction();
try {
    // Disable Foreign Key Checks (SQLite / MySQL)
    $driver = DB::connection()->getDriverName();
    if ($driver === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = OFF;');
    } else {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
    }

    echo "Suppression des notes et évaluations...\n";
    DB::table('grades')->truncate();
    DB::table('evaluations')->truncate();
    
    echo "Suppression des assignations et matières...\n";
    DB::table('assignments')->truncate();
    DB::table('subjects')->truncate();
    
    echo "Suppression des inscriptions et élèves...\n";
    DB::table('student_enrollments')->truncate();
    DB::table('students')->truncate();
    
    echo "Suppression des classes et années scolaires...\n";
    DB::table('classes')->truncate();
    DB::table('school_years')->truncate();
    
    echo "Suppression des invitations...\n";
    DB::table('invitations')->truncate();
    
    echo "Nettoyage des comptes utilisateurs (Admin conservé)...\n";
    // Delete all users EXCEPT those with 'admin' role
    $deletedUsers = DB::table('users')->where('role', '!=', 'admin')->delete();
    echo "Nombre de professeurs supprimés : $deletedUsers\n";

    if ($driver === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = ON;');
    } else {
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    DB::commit();
    echo "\n>>> SUCCÈS : La base de données a été vidée. Seuls les comptes administrateurs ont été conservés.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n>>> ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}
