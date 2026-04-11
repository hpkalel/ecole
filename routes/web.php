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
    $user = request()->user();
    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('prof.dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth', 'role:admin', 'activeYear'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/classes', [AdminController::class, 'classes'])->name('classes');
    Route::post('/classes', [AdminController::class, 'storeClass'])->name('classes.store');
    Route::patch('/classes/{classe}', [AdminController::class, 'updateClass'])->name('classes.update');
    Route::delete('/classes/{id}', [AdminController::class, 'destroyClass'])->name('classes.destroy');
    Route::get('/classes/{id}/export', [AdminController::class, 'exportClassExcel'])->name('classes.export');
    Route::post('/classes/set-principal', [AdminController::class, 'setPrincipal'])->name('classes.set-principal');

    Route::get('/subjects', [AdminController::class, 'subjects'])->name('subjects');
    Route::post('/subjects', [AdminController::class, 'storeSubject'])->name('subjects.store');
    Route::patch('/subjects/{subject}', [AdminController::class, 'updateSubject'])->name('subjects.update');
    Route::delete('/subjects/{id}', [AdminController::class, 'destroySubject'])->name('subjects.destroy');

    Route::get('/students', [AdminController::class, 'students'])->name('students');
    Route::post('/students', [AdminController::class, 'storeStudent'])->name('students.store');
    Route::patch('/students/{student}', [AdminController::class, 'updateStudent'])->name('students.update');
    Route::delete('/students/{id}', [AdminController::class, 'destroyStudent'])->name('students.destroy');
    Route::post('/students/import', [AdminController::class, 'importStudents'])->name('students.import');

    Route::get('/profs', [AdminController::class, 'profs'])->name('profs');
    Route::post('/profs', [AdminController::class, 'storeProf'])->name('profs.store');
    Route::patch('/profs/{prof}', [AdminController::class, 'updateProf'])->name('profs.update');
    Route::delete('/profs/{id}', [AdminController::class, 'destroyProf'])->name('profs.destroy');

    Route::get('/assignments', [AdminController::class, 'assignments'])->name('assignments');
    Route::post('/assignments', [AdminController::class, 'storeAssignment'])->name('assignments.store');
    Route::patch('/assignments/{assignment}', [AdminController::class, 'updateAssignment'])->name('assignments.update');
    Route::delete('/assignments/{id}', [AdminController::class, 'destroyAssignment'])->name('assignments.destroy');

    Route::get('/enrollments', [AdminController::class, 'enrollments'])->name('enrollments');
    Route::post('/enrollments', [AdminController::class, 'storeEnrollment'])->name('enrollments.store');
    Route::delete('/enrollments/{id}', [AdminController::class, 'destroyEnrollment'])->name('enrollments.destroy');
    Route::patch('/enrollments/{id}', [AdminController::class, 'updateEnrollment'])->name('enrollments.update');
    Route::get('/bulletin/{student_id}', [AdminController::class, 'bulletin'])->name('bulletin');

    Route::get('/years', [AdminController::class, 'years'])->name('years');
    Route::post('/years', [AdminController::class, 'storeYear'])->name('years.store');
    Route::post('/years/{id}/activate', [AdminController::class, 'activateYear'])->name('years.activate');
    Route::patch('/years/{year}', [AdminController::class, 'updateYear'])->name('years.update');
    Route::delete('/years/{id}', [AdminController::class, 'destroyYear'])->name('years.destroy');

    Route::get('/promote', [AdminController::class, 'promote'])->name('promote');
    Route::post('/promote', [AdminController::class, 'processPromote'])->name('promote.process');
});

Route::middleware(['auth', 'role:prof', 'activeYear'])->prefix('prof')->name('prof.')->group(function () {
    Route::get('/dashboard', [ProfController::class, 'dashboard'])->name('dashboard');
    Route::get('/evaluations', [ProfController::class, 'evaluations'])->name('evaluations');
    Route::post('/evaluations', [ProfController::class, 'storeEvaluation'])->name('evaluations.store');
    Route::patch('/evaluations/{evaluation}', [ProfController::class, 'updateEvaluation'])->name('evaluations.update');
    Route::delete('/evaluations/{id}', [ProfController::class, 'destroyEvaluation'])->name('evaluations.destroy');
    Route::get('/grades', [ProfController::class, 'grades'])->name('grades');
    Route::post('/grades', [ProfController::class, 'storeGrades'])->name('grades.store');

    // Conduite (Prof Principal)
    Route::get('/conduite/{classe_id}', [ProfController::class, 'conduite'])->name('conduite');
    Route::post('/conduite', [ProfController::class, 'storeConduite'])->name('conduite.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';