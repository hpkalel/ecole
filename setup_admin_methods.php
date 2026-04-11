<?php
$adminControllerPath = __DIR__ . '/app/Http/Controllers/AdminController.php';
$webRoutesPath = __DIR__ . '/routes/web.php';

$adminControllerContent = <<<EOT
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Classe;
use App\Models\Student;
use App\Models\User;
use App\Models\Subject;
use App\Models\SchoolYear;

class AdminController extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'students' => Student::count(),
                'classes' => Classe::count(),
                'profs' => User::where('role', 'prof')->count(),
                'subjects' => Subject::count(),
            ]
        ]);
    }

    /* CLASSES */
    public function classes()
    {
        return Inertia::render('Admin/Classes', [
            'classes' => Classe::latest()->get()
        ]);
    }

    public function storeClass(Request \$request)
    {
        \$request->validate(['nom' => 'required|string|max:50|unique:classes,nom']);
        Classe::create(\$request->only('nom'));
        return redirect()->back()->with('success', 'Classe ajoutée avec succès.');
    }

    public function destroyClass(\$id)
    {
        Classe::findOrFail(\$id)->delete();
        return redirect()->back()->with('success', 'Classe supprimée.');
    }

    /* SUBJECTS */
    public function subjects()
    {
        return Inertia::render('Admin/Subjects', [
            'subjects' => Subject::latest()->get()
        ]);
    }

    public function storeSubject(Request \$request)
    {
        \$request->validate([
            'nom' => 'required|string|max:100|unique:subjects,nom',
            'coefficient' => 'required|integer|min:1'
        ]);
        Subject::create(\$request->only('nom', 'coefficient'));
        return redirect()->back()->with('success', 'Matière ajoutée.');
    }

    public function destroySubject(\$id)
    {
        Subject::findOrFail(\$id)->delete();
        return redirect()->back()->with('success', 'Matière supprimée.');
    }

    /* STUDENTS */
    public function students()
    {
        return Inertia::render('Admin/Students', [
            'students' => Student::with(['enrollments.classe', 'enrollments.schoolYear'])->latest()->get()
        ]);
    }

    public function storeStudent(Request \$request)
    {
        \$request->validate([
            'matricule' => 'nullable|string|max:50|unique:students,matricule',
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'sexe' => 'required|in:M,F',
        ]);
        Student::create(\$request->all());
        return redirect()->back()->with('success', 'Élève ajouté.');
    }

    public function destroyStudent(\$id)
    {
        Student::findOrFail(\$id)->delete();
        return redirect()->back()->with('success', 'Élève supprimé.');
    }

    /* PROFS */
    public function profs()
    {
        return Inertia::render('Admin/Profs', [
            'profs' => User::where('role', 'prof')->latest()->get()
        ]);
    }

    public function destroyProf(\$id)
    {
        User::findOrFail(\$id)->delete();
        return redirect()->back()->with('success', 'Professeur supprimé.');
    }

    /* YEARS */
    public function years()
    {
        return Inertia::render('Admin/Years', [
            'years' => SchoolYear::latest()->get()
        ]);
    }

    public function storeYear(Request \$request)
    {
        \$request->validate([
            'name' => 'required|string|max:20|unique:school_years,name',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);
        SchoolYear::create(\$request->all());
        return redirect()->back()->with('success', 'Année scolaire ajoutée.');
    }

    public function destroyYear(\$id)
    {
        SchoolYear::findOrFail(\$id)->delete();
        return redirect()->back();
    }

    public function promote()
    {
        return Inertia::render('Admin/Promote');
    }
}
EOT;

file_put_contents($adminControllerPath, $adminControllerContent);

$webRoutesContent = <<<EOT
<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    \$user = request()->user();
    if (\$user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('prof.dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    Route::get('/classes', [AdminController::class, 'classes'])->name('classes');
    Route::post('/classes', [AdminController::class, 'storeClass'])->name('classes.store');
    Route::delete('/classes/{id}', [AdminController::class, 'destroyClass'])->name('classes.destroy');

    Route::get('/subjects', [AdminController::class, 'subjects'])->name('subjects');
    Route::post('/subjects', [AdminController::class, 'storeSubject'])->name('subjects.store');
    Route::delete('/subjects/{id}', [AdminController::class, 'destroySubject'])->name('subjects.destroy');

    Route::get('/students', [AdminController::class, 'students'])->name('students');
    Route::post('/students', [AdminController::class, 'storeStudent'])->name('students.store');
    Route::delete('/students/{id}', [AdminController::class, 'destroyStudent'])->name('students.destroy');

    Route::get('/profs', [AdminController::class, 'profs'])->name('profs');
    Route::delete('/profs/{id}', [AdminController::class, 'destroyProf'])->name('profs.destroy');

    Route::get('/years', [AdminController::class, 'years'])->name('years');
    Route::post('/years', [AdminController::class, 'storeYear'])->name('years.store');
    Route::delete('/years/{id}', [AdminController::class, 'destroyYear'])->name('years.destroy');

    Route::get('/promote', [AdminController::class, 'promote'])->name('promote');
});

Route::middleware(['auth', 'role:prof'])->prefix('prof')->name('prof.')->group(function () {
    Route::get('/dashboard', [ProfController::class, 'dashboard'])->name('dashboard');
    Route::get('/evaluations', [ProfController::class, 'evaluations'])->name('evaluations');
    Route::get('/grades', [ProfController::class, 'grades'])->name('grades');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
EOT;

file_put_contents($webRoutesPath, $webRoutesContent);
echo "Backend Logic Updated!";
