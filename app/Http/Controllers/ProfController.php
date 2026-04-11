<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use \App\Models\Assignment;
use \App\Models\Evaluation;

class ProfController extends Controller
{
    public function dashboard(Request $request)
    {
        $prof = $request->user();
        $activeYear = \App\Models\SchoolYear::where('is_active', true)->first();
        
        $query = Assignment::with(['classe', 'subject', 'schoolYear'])
            ->where('prof_id', $prof->id);
            
        if ($activeYear) {
            $query->where('school_year_id', $activeYear->id);
        } else {
            $query->where('id', -1);
        }

        $managedClasses = [];
        if ($activeYear) {
            $managedClasses = \App\Models\ClassPrincipal::with('classe')
                ->where('prof_id', $prof->id)
                ->where('school_year_id', $activeYear->id)
                ->get()
                ->pluck('classe');
        }

        return Inertia::render('Prof/Dashboard', [
            'assignments' => $query->get(),
            'managedClasses' => $managedClasses
        ]);
    }

    public function evaluations(Request $request)
    {
        $prof = $request->user();
        $assignment_id = $request->query('assignment_id');

        if (!$assignment_id) {
            return redirect()->route('prof.dashboard');
        }

        $activeYear = \App\Models\SchoolYear::where('is_active', true)->first();

        $assignment = \App\Models\Assignment::with(['classe', 'subject'])
            ->where('prof_id', $prof->id)
            ->where('school_year_id', $activeYear ? $activeYear->id : -1)
            ->findOrFail($assignment_id);

        return Inertia::render('Prof/Evaluations', [
            'evaluations' => Evaluation::where('assignment_id', $assignment_id)->latest()->get(),
            'assignment' => $assignment
        ]);
    }

        public function storeEvaluation(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'nom' => 'required|string|max:255',
            'type' => 'required|in:interrogation,devoir',
            'periode' => 'required|string',
            'date' => 'required|date'
        ]);

        \App\Models\Evaluation::create($request->all());

        return redirect()->back()->with('success', 'Évaluation créée.');
    }

    public function updateEvaluation(Request $request, \App\Models\Evaluation $evaluation)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|in:interrogation,devoir',
            'periode' => 'required|string',
            'date' => 'required|date'
        ]);

        $evaluation->update($request->all());

        return redirect()->back()->with('success', 'Évaluation mise à jour.');
    }

    public function destroyEvaluation($id)
    {
        \App\Models\Evaluation::destroy($id);
        return redirect()->back()->with('success', 'Évaluation supprimée.');
    }

        public function grades(Request $request)
    {
        $evaluation_id = $request->query('evaluation_id');
        if (!$evaluation_id) {
            return redirect()->route('prof.evaluations');
        }

        $evaluation = Evaluation::with(['assignment.classe', 'assignment.subject'])->findOrFail($evaluation_id);
        
        if ($evaluation->assignment->prof_id !== $request->user()->id) {
            abort(403);
        }

        $studentsQuery = \App\Models\StudentEnrollment::with('student')
            ->where('class_id', $evaluation->assignment->class_id)
            ->where('school_year_id', $evaluation->assignment->school_year_id)
            ->get();
            
        $students = $studentsQuery->pluck('student')->sortBy(['nom', 'prenom'])->values();

        $grades = \App\Models\Grade::where('evaluation_id', $evaluation_id)->get()->keyBy('student_id');

        return Inertia::render('Prof/Grades', [
            'evaluation' => $evaluation,
            'students' => $students,
            'existingGrades' => $grades
        ]);
    }

    public function storeGrades(Request $request)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:evaluations,id',
            'notes' => 'required|array',
            'notes.*' => 'nullable|numeric|min:0|max:20',
            'comments' => 'nullable|array',
        ]);

        $evaluation_id = $request->input('evaluation_id');
        $notes = $request->input('notes', []);
        $comments = $request->input('comments', []);

        \DB::transaction(function () use ($evaluation_id, $notes, $comments) {
            foreach ($notes as $student_id => $val) {
                if ($val === null || $val === '') {
                    // Optionnel: supprimer la note si elle est vidée ? 
                    // Pour l'instant on garde le comportement existant (ne rien faire)
                    continue;
                }

                \App\Models\Grade::updateOrCreate(
                    ['evaluation_id' => $evaluation_id, 'student_id' => $student_id],
                    ['valeur' => $val, 'appreciation' => $comments[$student_id] ?? null]
                );
            }
        });

        return redirect()->back()->with('success', 'Notes enregistrées avec succès.');
    }

    public function conduite(Request $request, $classe_id)
    {
        $prof = $request->user();
        $activeYear = \App\Models\SchoolYear::where('is_active', true)->first();
        if (!$activeYear) $activeYear = \App\Models\SchoolYear::first();

        $isPrincipal = \App\Models\ClassPrincipal::where('prof_id', $prof->id)
            ->where('classe_id', $classe_id)
            ->where('school_year_id', $activeYear->id)
            ->exists();

        if (!$isPrincipal) return redirect()->route('prof.dashboard');

        $classe = \App\Models\Classe::findOrFail($classe_id);
        $enrollments = \App\Models\StudentEnrollment::with('student')
            ->where('class_id', $classe_id)
            ->where('school_year_id', $activeYear->id)
            ->get();

        $periode = $request->query('periode', 'Semestre 1');
        
        $behaviorGrades = \App\Models\BehaviorGrade::where('school_year_id', $activeYear->id)
            ->where('periode', $periode)
            ->whereIn('student_id', $enrollments->pluck('student_id'))
            ->get()
            ->keyBy('student_id');

        return Inertia::render('Prof/Conduite', [
            'classe' => $classe,
            'students' => $enrollments->map(function($e) use ($behaviorGrades) {
                $e->student->current_behavior_grade = $behaviorGrades[$e->student->id] ?? null;
                return $e->student;
            }),
            'periode' => $periode
        ]);
    }

    public function storeConduite(Request $request)
    {
        $request->validate([
            'periode' => 'required',
            'grades' => 'required|array',
            'grades.*' => 'nullable|numeric|min:0|max:20',
        ]);

        $activeYear = \App\Models\SchoolYear::where('is_active', true)->first();
        if (!$activeYear) $activeYear = \App\Models\SchoolYear::first();

        foreach ($request->grades as $student_id => $valeur) {
            if ($valeur === null || $valeur === '') continue;
            
            \App\Models\BehaviorGrade::updateOrCreate(
                [
                    'student_id' => $student_id,
                    'school_year_id' => $activeYear->id,
                    'periode' => $request->periode
                ],
                ['valeur' => $valeur]
            );
        }

        return redirect()->back()->with('success', 'Notes de conduite enregistrées.');
    }
}
