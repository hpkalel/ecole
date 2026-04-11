<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use \App\Models\Classe;
use \App\Models\Student;
use \App\Models\User;
use \App\Models\Subject;
use \App\Models\SchoolYear;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminController extends Controller
{
    public function dashboard()
    {
        $activeYear = SchoolYear::where('is_active', true)->first();

        // Base student counts
        $totalStudents = Student::count();
        $boys = Student::where('sexe', 'M')->count();
        $girls = Student::where('sexe', 'F')->count();

        // Enrollment stats for active year
        $nouveaux = 0;
        $redoublants = 0;
        $enrolledStudents = 0;
        $classesWithCounts = collect();

        if ($activeYear) {
            $nouveaux = \App\Models\StudentEnrollment::where('school_year_id', $activeYear->id)
                ->where('statut', 'Nouveau')->count();
            $redoublants = \App\Models\StudentEnrollment::where('school_year_id', $activeYear->id)
                ->where('statut', 'Redoublant')->count();
            $enrolledStudents = \App\Models\StudentEnrollment::where('school_year_id', $activeYear->id)
                ->distinct('student_id')->count();

            $classesWithCounts = Classe::withCount([
                'enrollments' => function ($q) use ($activeYear) {
                    $q->where('school_year_id', $activeYear->id);
                }
            ])->orderBy('nom')->get()->map(function ($c) {
                return ['id' => $c->id, 'nom' => $c->nom, 'count' => $c->enrollments_count];
            });
        }

        $activeAssignments = $activeYear
            ? \App\Models\Assignment::where('school_year_id', $activeYear->id)->count()
            : 0;

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'students' => $totalStudents,
                'enrolled' => $enrolledStudents,
                'boys' => $boys,
                'girls' => $girls,
                'nouveaux' => $nouveaux,
                'redoublants' => $redoublants,
                'classes' => Classe::count(),
                'profs' => User::where('role', 'prof')->count(),
                'subjects' => Subject::count(),
                'assignments' => $activeAssignments,
            ],
            'activeYear' => $activeYear,
            'classesWithCounts' => $classesWithCounts,
        ]);
    }

    /* CLASSES */
    public function classes()
    {
        $activeYear = SchoolYear::where('is_active', true)->first();
        $classes = Classe::latest()->get()->map(function ($classe) use ($activeYear) {
            $principal = null;
            $profs = collect();

            if ($activeYear) {
                $cp = \App\Models\ClassPrincipal::with('prof')
                    ->where('classe_id', $classe->id)
                    ->where('school_year_id', $activeYear->id)
                    ->first();
                $principal = $cp ? $cp->prof : null;

                $profs = \App\Models\Assignment::where('class_id', $classe->id)
                    ->where('school_year_id', $activeYear->id)
                    ->with('prof')
                    ->get()
                    ->pluck('prof')
                    ->unique('id');
            }

            $classe->active_principal = $principal;
            $classe->available_profs = $profs->values();
            return $classe;
        });

        return Inertia::render('Admin/Classes', [
            'classes' => $classes
        ]);
    }

    public function storeClass(Request $request)
    {
        $request->validate(['nom' => 'required|string|max:50|unique:classes,nom']);
        Classe::create(['nom' => trim($request->nom)]);
        return redirect()->back()->with('success', 'Classe ajoutée avec succès.');
    }

    public function destroyClass($id)
    {
        Classe::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Classe supprimée.');
    }

    /* SUBJECTS */
    public function subjects()
    {
        return Inertia::render('Admin/Subjects', [
            'subjects' => Subject::latest()->get()
        ]);
    }

    public function storeSubject(Request $request)
    {
        $request->validate(['nom' => 'required|string|max:100|unique:subjects,nom']);
        Subject::create(['nom' => strtoupper(trim($request->nom))]);
        return redirect()->back()->with('success', 'Matière ajoutée.');
    }

    public function destroySubject($id)
    {
        Subject::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Matière supprimée.');
    }

    /* STUDENTS */
    public function students()
    {
        $activeYearId = \App\Models\SchoolYear::where('is_active', true)->value('id') ?? \App\Models\SchoolYear::value('id');

        $students = \App\Models\Student::with(['enrollments.classe', 'enrollments.schoolYear'])
            ->select('students.*', 'student_enrollments.statut as statut')
            ->leftJoin('student_enrollments', function ($join) use ($activeYearId) {
                $join->on('students.id', '=', 'student_enrollments.student_id')
                    ->where('student_enrollments.school_year_id', '=', $activeYearId);
            })
            ->leftJoin('classes', 'student_enrollments.class_id', '=', 'classes.id')
            ->orderBy('classes.nom', 'asc')
            ->orderBy('students.nom', 'asc')
            ->orderBy('students.prenom', 'asc')
            ->get();

        return Inertia::render('Admin/Students', [
            'students' => $students,
            'classes' => \App\Models\Classe::withCount([
                'enrollments as total_count' => function ($q) use ($activeYearId) {
                    $q->where('school_year_id', $activeYearId);
                },
                'enrollments as nouveaux_count' => function ($q) use ($activeYearId) {
                    $q->where('school_year_id', $activeYearId)->where('statut', 'Nouveau');
                },
                'enrollments as redoublants_count' => function ($q) use ($activeYearId) {
                    $q->where('school_year_id', $activeYearId)->where('statut', 'Redoublant');
                }
            ])->get(),
            'years' => \App\Models\SchoolYear::all(),
            'activeYearId' => $activeYearId
        ]);
    }

    public function storeStudent(Request $request)
    {
        $request->validate([
            'matricule' => 'nullable|string|max:50|unique:students,matricule',
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'sexe' => 'required|in:M,F',
            'class_id' => 'required|exists:classes,id',
            'school_year_id' => 'required|exists:school_years,id',
            'statut' => 'required|in:Nouveau,Redoublant'
        ]);

        \DB::transaction(function () use ($request) {
            $nom = strtoupper(trim($request->nom));
            $prenom = mb_convert_case(trim($request->prenom), MB_CASE_TITLE, "UTF-8");

            $student = \App\Models\Student::create([
                'matricule' => $request->matricule,
                'nom' => $nom,
                'prenom' => $prenom,
                'sexe' => $request->sexe
            ]);

            \App\Models\StudentEnrollment::create([
                'student_id' => $student->id,
                'class_id' => $request->class_id,
                'school_year_id' => $request->school_year_id,
                'statut' => $request->statut
            ]);
        });

        return redirect()->back()->with('success', 'Élève ajouté et inscrit.');
    }

    public function destroyStudent($id)
    {
        Student::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Élève supprimé.');
    }

    /* PROFS */
    public function profs()
    {
        return Inertia::render('Admin/Profs', [
            'profs' => User::where('role', 'prof')->latest()->get()
        ]);
    }

    public function storeProf(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'grade' => 'nullable|string|max:100',
            'statut' => 'nullable|string|max:100',
            'corps' => 'nullable|string|max:100',
        ]);

        $nameParts = explode(' ', trim($request->nom));
        $nomPart = strtoupper(array_shift($nameParts));
        $prenomPart = mb_convert_case(implode(' ', $nameParts), MB_CASE_TITLE, "UTF-8");
        $nomComplet = trim($nomPart . ' ' . $prenomPart);

        User::create([
            'nom' => $nomComplet,
            'username' => $request->username,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role' => 'prof',
            'is_active' => true,
            'grade' => $request->grade,
            'statut' => $request->statut,
            'corps' => $request->corps,
        ]);

        return redirect()->back()->with('success', 'Professeur ajouté.');
    }

    public function updateProf(Request $request, User $prof)
    {
        $request->validate([
            'nom' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $prof->id,
            'password' => 'nullable|string|min:8|confirmed',
            'grade' => 'nullable|string|max:100',
            'statut' => 'nullable|string|max:100',
            'corps' => 'nullable|string|max:100',
        ]);

        $nameParts = explode(' ', trim($request->nom));
        $nomPart = strtoupper(array_shift($nameParts));
        $prenomPart = mb_convert_case(implode(' ', $nameParts), MB_CASE_TITLE, "UTF-8");
        $nomComplet = trim($nomPart . ' ' . $prenomPart);

        $data = [
            'nom' => $nomComplet,
            'username' => $request->username,
            'grade' => $request->grade,
            'statut' => $request->statut,
            'corps' => $request->corps,
        ];

        if ($request->filled('password')) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $prof->update($data);

        return redirect()->back()->with('success', 'Informations du professeur mises à jour.');
    }

    public function destroyProf($id)
    {
        User::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Professeur supprimé.');
    }

    /* YEARS */
    public function years()
    {
        return Inertia::render('Admin/Years', [
            'years' => SchoolYear::latest()->get()
        ]);
    }

    public function storeYear(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:20|unique:school_years,name',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);
        SchoolYear::create($request->all());
        return redirect()->back()->with('success', 'Année scolaire ajoutée.');
    }

    public function destroyYear($id)
    {
        SchoolYear::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Année supprimée.');
    }

    public function activateYear($id)
    {
        \DB::transaction(function () use ($id) {
            SchoolYear::query()->update(['is_active' => false]);
            SchoolYear::where('id', $id)->update(['is_active' => true]);
        });
        return redirect()->back()->with('success', 'Année scolaire activée comme année de travail.');
    }

    public function assignments()
    {
        return Inertia::render('Admin/Assignments', [
            'assignments' => \App\Models\Assignment::with(['prof', 'subject', 'classe', 'schoolYear'])->latest()->get(),
            'profs' => User::where('role', 'prof')->get(),
            'classes' => Classe::all(),
            'subjects' => Subject::all(),
            'years' => SchoolYear::all()
        ]);
    }

    // --- UPDATE METHODS ---

    public function updateStudent(Request $request, \App\Models\Student $student)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'sexe' => 'required|in:M,F',
            'matricule' => 'nullable|string|max:50|unique:students,matricule,' . $student->id,
            'class_id' => 'required|exists:classes,id',
            'statut' => 'required|in:Nouveau,Redoublant',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        $nom = strtoupper(trim($request->nom));
        $prenom = mb_convert_case(trim($request->prenom), MB_CASE_TITLE, "UTF-8");

        $student->update([
            'nom' => $nom,
            'prenom' => $prenom,
            'sexe' => $request->sexe,
            'matricule' => $request->matricule
        ]);

        // Update enrollment for the given school year
        \App\Models\StudentEnrollment::updateOrCreate(
            ['student_id' => $student->id, 'school_year_id' => $data['school_year_id']],
            ['class_id' => $data['class_id'], 'statut' => $data['statut']]
        );

        return redirect()->back()->with('success', 'Élève mis à jour.');
    }

    public function updateClass(Request $request, \App\Models\Classe $classe)
    {
        $request->validate(['nom' => 'required|string|max:255|unique:classes,nom,' . $classe->id]);
        $classe->update(['nom' => trim($request->nom)]);
        return redirect()->back()->with('success', 'Classe mise à jour.');
    }

    public function updateSubject(Request $request, \App\Models\Subject $subject)
    {
        $request->validate(['nom' => 'required|string|max:255|unique:subjects,nom,' . $subject->id]);
        $subject->update(['nom' => strtoupper(trim($request->nom))]);
        return redirect()->back()->with('success', 'Matière mise à jour.');
    }

    public function updateAssignment(Request $request, \App\Models\Assignment $assignment)
    {
        $request->validate([
            'prof_id' => 'required|exists:users,id',
            'coefficient' => 'required|integer|min:1|max:10'
        ]);
        $assignment->update($request->only('prof_id', 'coefficient'));
        return redirect()->back()->with('success', 'Attribution mise à jour.');
    }

    public function updateYear(Request $request, \App\Models\SchoolYear $year)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:school_years,name,' . $year->id,
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);
        $year->update($request->only('name', 'start_date', 'end_date'));
        return redirect()->back()->with('success', 'Année mise à jour.');
    }

    // --- CSV IMPORT ---

    public function importStudents(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'school_year_id' => 'required|exists:school_years,id',
            'import_file' => 'required|file|mimes:csv,txt,xlsx,xls'
        ]);

        $file = $request->file('import_file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Erreur lors de la lecture du fichier : " . $e->getMessage());
        }

        $count = 0;

        \DB::transaction(function () use ($rows, $request, &$count) {
            foreach ($rows as $index => $data) {
                // Skip row if first two columns are empty
                if (empty($data[0]) && empty($data[1]))
                    continue;

                $matricule = trim($data[0] ?? '');
                $nom = strtoupper(trim($data[1] ?? ''));
                $prenom = mb_convert_case(trim($data[2] ?? ''), MB_CASE_TITLE, "UTF-8");

                // Skip header if first column contains 'MATRICULE' or 'NOM'
                if (strtoupper($matricule) == 'MATRICULE' || strtoupper($nom) == 'NOM')
                    continue;
                $sexe = strtoupper(trim($data[3] ?? 'M'));
                if ($sexe != 'F')
                    $sexe = 'M';

                $statutRaw = strtolower(trim($data[4] ?? ''));
                $statut = str_contains($statutRaw, 'redoubl') ? 'Redoublant' : 'Nouveau';

                // Create or get student (by matricule if exists, else by name)
                $student = null;
                if ($matricule) {
                    $student = \App\Models\Student::where('matricule', $matricule)->first();
                }

                if (!$student) {
                    $student = \App\Models\Student::firstOrCreate(
                        ['nom' => $nom, 'prenom' => $prenom],
                        ['sexe' => $sexe, 'matricule' => $matricule]
                    );
                } else {
                    // Always update current info
                    $student->update([
                        'nom' => $nom,
                        'prenom' => $prenom,
                        'sexe' => $sexe,
                        'matricule' => $matricule ?: $student->matricule
                    ]);
                }

                // Enrollment
                \App\Models\StudentEnrollment::updateOrCreate(
                    ['student_id' => $student->id, 'school_year_id' => $request->school_year_id],
                    ['class_id' => $request->class_id, 'statut' => $statut]
                );
                $count++;
            }
        });

        return redirect()->back()->with('success', "$count élèves importés avec succès.");
    }

    // --- STORE ASSIGNMENT (SINGLE-CLASS) ---

    public function storeAssignment(Request $request)
    {
        $request->validate([
            'prof_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'school_year_id' => 'required|exists:school_years,id',
            'coefficient' => 'required|integer|min:1'
        ]);

        \App\Models\Assignment::updateOrCreate(
            [
                'subject_id' => $request->subject_id,
                'class_id' => $request->class_id,
                'school_year_id' => $request->school_year_id
            ],
            [
                'prof_id' => $request->prof_id,
                'coefficient' => $request->coefficient
            ]
        );

        return redirect()->back()->with('success', "Attribution effectuée.");
    }

    public function destroyAssignment($id)
    {
        \App\Models\Assignment::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Attribution supprimée avec succès.');
    }

    public function enrollments()
    {
        $enrollments = \App\Models\StudentEnrollment::with(['student', 'classe', 'schoolYear'])
            ->select('student_enrollments.*')
            ->join('classes', 'student_enrollments.class_id', '=', 'classes.id')
            ->join('students', 'student_enrollments.student_id', '=', 'students.id')
            ->orderBy('classes.nom', 'asc')
            ->orderBy('students.nom', 'asc')
            ->orderBy('students.prenom', 'asc')
            ->get();

        return Inertia::render('Admin/Enrollments', [
            'enrollments' => $enrollments,
            'students' => Student::all(),
            'classes' => \App\Models\Classe::withCount([
                'enrollments' => function ($q) {
                    $activeYearId = \App\Models\SchoolYear::where('is_active', true)->value('id');
                    if ($activeYearId)
                        $q->where('school_year_id', $activeYearId);
                }
            ])->get(),
            'years' => SchoolYear::all(),
            'activeYearId' => \App\Models\SchoolYear::where('is_active', true)->value('id') ?? \App\Models\SchoolYear::value('id')
        ]);
    }

    public function storeEnrollment(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
            'school_year_id' => 'required|exists:school_years,id'
        ]);
        \App\Models\StudentEnrollment::create($request->all());
        return redirect()->back()->with('success', 'Élève inscrit avec succès.');
    }

    public function destroyEnrollment($id)
    {
        \App\Models\StudentEnrollment::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Inscription supprimée.');
    }

    public function updateEnrollment(Request $request, $id)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);
        \App\Models\StudentEnrollment::where('id', $id)->update([
            'class_id' => $request->class_id
        ]);
        return redirect()->back()->with('success', 'Élève transféré avec succès.');
    }

    private function calculatePeriodStats($student_id, $periode, $school_year_id)
    {
        $grades = \App\Models\Grade::with(['evaluation.assignment.subject'])
            ->whereHas('evaluation', function ($q) use ($periode, $school_year_id) {
                $q->where('periode', $periode)
                    ->whereHas('assignment', function ($q2) use ($school_year_id) {
                        $q2->where('school_year_id', $school_year_id);
                    });
            })
            ->where('student_id', $student_id)
            ->get();

        $totalScore = 0;
        $totalCoeff = 0;
        $subjects = [];

        foreach ($grades as $grade) {
            $subjectName = $grade->evaluation->assignment->subject->nom;
            if (!isset($subjects[$subjectName])) {
                $subjects[$subjectName] = [
                    'sum' => 0,
                    'count' => 0,
                    'coeff' => $grade->evaluation->assignment->coefficient,
                ];
            }
            $subjects[$subjectName]['sum'] += $grade->valeur;
            $subjects[$subjectName]['count']++;
        }

        foreach ($subjects as $sub) {
            $avg = $sub['sum'] / $sub['count'];
            $totalScore += $avg * $sub['coeff'];
            $totalCoeff += $sub['coeff'];
        }

        // Ajout de la conduite (Coeff 1)
        $behaviorGrade = \App\Models\BehaviorGrade::where('student_id', $student_id)
            ->where('school_year_id', $school_year_id)
            ->where('periode', $periode)
            ->first();

        if ($behaviorGrade) {
            $totalScore += $behaviorGrade->valeur;
            $totalCoeff += 1;
        }

        return $totalCoeff > 0 ? $totalScore / $totalCoeff : null;
    }

    public function bulletin(Request $request, $student_id)
    {
        $student = \App\Models\Student::findOrFail($student_id);
        $periode = $request->query('periode', 'Semestre 1');
        $school_year_id = $request->query('school_year_id');

        if (!$school_year_id) {
            $activeYear = \App\Models\SchoolYear::where('is_active', true)->first();
            if (!$activeYear) {
                $activeYear = \App\Models\SchoolYear::first();
            }
            if (!$activeYear)
                abort(404, 'Aucune année scolaire active.');
            $school_year_id = $activeYear->id;
        } else {
            $activeYear = \App\Models\SchoolYear::find($school_year_id);
        }

        $enrollment = \App\Models\StudentEnrollment::with('classe')
            ->where('student_id', $student_id)
            ->where('school_year_id', $school_year_id)
            ->first();

        // Get count of students in class
        $classCount = 0;
        if ($enrollment) {
            $classCount = \App\Models\StudentEnrollment::where('class_id', $enrollment->class_id)
                ->where('school_year_id', $school_year_id)->count();
        }

        // Fetch grades explicitly mimicking legacy join logic
        $gradesRaw = \DB::select("
            SELECT 
                s.nom as subject_name,
                a.coefficient,
                u.nom as prof_name,
                e.nom as eval_name,
                e.type,
                g.valeur,
                g.appreciation
            FROM grades g
            JOIN evaluations e ON g.evaluation_id = e.id
            JOIN assignments a ON e.assignment_id = a.id
            JOIN subjects s ON a.subject_id = s.id
            JOIN users u ON a.prof_id = u.id
            WHERE g.student_id = ? 
            AND e.periode = ?
            AND a.school_year_id = ?
            ORDER BY s.nom, e.date
        ", [$student_id, $periode, $school_year_id]);

        $subjectsData = [];
        $maxInterros = 0;
        $maxDevoirs = 0;

        foreach ($gradesRaw as $row) {
            $subject = $row->subject_name;
            if (!isset($subjectsData[$subject])) {
                $subjectsData[$subject] = [
                    'subject_name' => $subject,
                    'prof' => $row->prof_name,
                    'coeff' => $row->coefficient,
                    'interros' => [],
                    'devoirs' => [],
                    'total_val' => 0,
                    'count_val' => 0,
                    'total_interro' => 0,
                    'count_interro' => 0
                ];
            }

            if (strtolower($row->type) === 'interrogation') {
                $subjectsData[$subject]['interros'][] = $row;
                $subjectsData[$subject]['total_interro'] += $row->valeur;
                $subjectsData[$subject]['count_interro']++;
            } else {
                $subjectsData[$subject]['devoirs'][] = $row;
            }

            $subjectsData[$subject]['total_val'] += $row->valeur;
            $subjectsData[$subject]['count_val']++;

            if (count($subjectsData[$subject]['interros']) > $maxInterros)
                $maxInterros = count($subjectsData[$subject]['interros']);
            if (count($subjectsData[$subject]['devoirs']) > $maxDevoirs)
                $maxDevoirs = count($subjectsData[$subject]['devoirs']);
        }

        $globalTotal = 0;
        $globalCoeff = 0;
        $formattedSubjects = [];

        foreach ($subjectsData as $subject => $data) {
            if ($data['count_interro'] > 0)
                $data['avg_interro'] = round($data['total_interro'] / $data['count_interro'], 2);
            else
                $data['avg_interro'] = null;

            if ($data['count_val'] > 0) {
                $data['average'] = round($data['total_val'] / $data['count_val'], 2);
                $data['weighted_score'] = round($data['average'] * $data['coeff'], 2);
                $globalTotal += $data['weighted_score'];
                $globalCoeff += $data['coeff'];
            } else {
                $data['average'] = null;
                $data['weighted_score'] = 0;
            }
            $formattedSubjects[] = $data;
        }

        // Ajout de la ligne "Conduite" dans le bulletin
        $behaviorGrade = \App\Models\BehaviorGrade::where('student_id', $student_id)
            ->where('school_year_id', $school_year_id)
            ->where('periode', $periode)
            ->first();

        if ($behaviorGrade) {
            $globalTotal += $behaviorGrade->valeur;
            $globalCoeff += 1;

            $formattedSubjects[] = [
                'subject_name' => 'Conduite',
                'prof' => 'Professeur Principal',
                'coeff' => 1,
                'interros' => [],
                'devoirs' => [],
                'average' => $behaviorGrade->valeur,
                'weighted_score' => $behaviorGrade->valeur,
                'is_behavior' => true
            ];
        }

        $globalAverage = $globalCoeff > 0 ? round($globalTotal / $globalCoeff, 2) : null;

        $annualAverage = null;
        $sem1Average = null;

        if ($periode === 'Semestre 2') {
            $sem1Average = $this->calculatePeriodStats($student_id, 'Semestre 1', $school_year_id);
            if ($sem1Average !== null && $globalAverage !== null) {
                $annualAverage = round((($sem1Average * 2) + $globalAverage) / 3, 2);
            }
        }

        return Inertia::render('Admin/Bulletin', [
            'student' => $student,
            'enrollment' => $enrollment,
            'classCount' => $classCount,
            'activeYear' => $activeYear,
            'periode' => $periode,
            'subjectsData' => $formattedSubjects,
            'maxInterros' => $maxInterros,
            'maxDevoirs' => $maxDevoirs,
            'globalTotal' => $globalTotal,
            'globalCoeff' => $globalCoeff,
            'globalAverage' => $globalAverage,
            'sem1Average' => $sem1Average,
            'annualAverage' => $annualAverage
        ]);
    }

    private function getAnnualAverage($student_id, $school_year_id)
    {
        $sem1 = $this->calculatePeriodStats($student_id, 'Semestre 1', $school_year_id);
        $sem2 = $this->calculatePeriodStats($student_id, 'Semestre 2', $school_year_id);
        if ($sem1 === null && $sem2 === null)
            return null;
        if ($sem2 === null)
            return $sem1;
        if ($sem1 === null)
            return $sem2;
        return round((($sem1 * 2) + $sem2) / 3, 2);
    }

    private function getNextClassName($currentClass)
    {
        $map = [
            '6ème' => '5ème',
            '6e' => '5e',
            '6' => '5',
            '5ème' => '4ème',
            '5e' => '4e',
            '5' => '4',
            '4ème' => '3ème',
            '4e' => '3e',
            '4' => '3',
            '3ème' => '2nde',
            '3e' => '2nde',
            '3' => '2nde',
            '2nde' => '1ère',
            '1ère' => 'Terminale',
            '1ere' => 'Terminale'
        ];
        foreach ($map as $k => $v) {
            if (str_starts_with(strtolower($currentClass), strtolower($k))) {
                return str_ireplace($k, $v, $currentClass);
            }
        }
        return null;
    }

    public function promote()
    {
        $activeYear = \App\Models\SchoolYear::where('is_active', true)->first();
        if (!$activeYear)
            $activeYear = \App\Models\SchoolYear::first();
        if (!$activeYear)
            return redirect()->route('admin.dashboard');

        $enrollments = \App\Models\StudentEnrollment::with(['student', 'classe'])
            ->where('school_year_id', $activeYear->id)
            ->limit(50)
            ->get();

        $preview = [];
        foreach ($enrollments as $e) {
            $avg = $this->getAnnualAverage($e->student_id, $activeYear->id);
            $decision = 'Inconnu';
            $color = 'text-gray-500';

            if ($avg !== null) {
                if ($avg >= 10) {
                    $next = $this->getNextClassName($e->classe->nom);
                    if ($next) {
                        $decision = "Passage en $next";
                        $color = 'text-green-600';
                    } else {
                        $decision = "Fin de cursus (Diplômé?)";
                        $color = 'text-blue-600';
                    }
                } else {
                    $decision = "Redoublement ({$e->classe->nom})";
                    $color = 'text-red-600';
                }
            }

            $preview[] = [
                'id' => $e->id,
                'nom' => $e->student->nom,
                'prenom' => $e->student->prenom,
                'classe' => $e->classe->nom,
                'avg' => $avg !== null ? $avg : 'N/A',
                'decision' => $decision,
                'color' => $color
            ];
        }

        // Suggest name
        $suggested = '';
        $parts = explode('-', $activeYear->name);
        if (count($parts) == 2 && is_numeric($parts[0])) {
            $suggested = (intval($parts[0]) + 1) . '-' . (intval($parts[1]) + 1);
        }

        return Inertia::render('Admin/Promote', [
            'activeYear' => $activeYear,
            'suggestedName' => $suggested,
            'preview' => $preview
        ]);
    }

    public function processPromote(Request $request)
    {
        $request->validate([
            'target_year_name' => 'required|string',
            'current_year_id' => 'required|exists:school_years,id'
        ]);

        $target_year_name = $request->target_year_name;
        $current_year_id = $request->current_year_id;

        $targetYear = \App\Models\SchoolYear::firstOrCreate(
            ['name' => $target_year_name],
            ['is_active' => false]
        );

        $enrollments = \App\Models\StudentEnrollment::with(['student', 'classe'])
            ->where('school_year_id', $current_year_id)
            ->get();

        $promoted = 0;
        $repeated = 0;

        foreach ($enrollments as $e) {
            $avg = $this->getAnnualAverage($e->student_id, $current_year_id);
            if ($avg !== null && $avg >= 10) {
                $next = $this->getNextClassName($e->classe->nom);
                if ($next) {
                    $nextClass = \App\Models\Classe::firstOrCreate(['nom' => trim($next)]);
                    \App\Models\StudentEnrollment::updateOrCreate([
                        'student_id' => $e->student_id,
                        'school_year_id' => $targetYear->id
                    ], ['class_id' => $nextClass->id]);
                    $promoted++;
                    continue;
                }
            }

            // Repeat or fin de cursus (we just re-enroll them if avg < 10)
            if ($avg !== null && $avg < 10) {
                \App\Models\StudentEnrollment::updateOrCreate([
                    'student_id' => $e->student_id,
                    'school_year_id' => $targetYear->id
                ], ['class_id' => $e->class_id]);
                $repeated++;
            }
        }

        return redirect()->back()->with('success', "Promotion globale terminée avec succès ($promoted passages, $repeated redoublements générés).");
    }

    public function exportClassExcel(Request $request, $id)
    {
        $classe = Classe::findOrFail($id);
        $activeYear = SchoolYear::where('is_active', true)->first();
        if (!$activeYear)
            $activeYear = SchoolYear::first();
        if (!$activeYear)
            return redirect()->back();

        $enrollments = \App\Models\StudentEnrollment::with('student')
            ->where('class_id', $classe->id)
            ->where('school_year_id', $activeYear->id)
            ->get();

        $assignments = \App\Models\Assignment::with('subject')
            ->where('class_id', $classe->id)
            ->where('school_year_id', $activeYear->id)
            ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($assignments as $assignment) {
            $sheet = $spreadsheet->createSheet();
            // Nettoyage du titre de la feuille (limite 31 chars et pas de caractères interdits)
            $sheetTitle = str_replace(['*', ':', '/', '\\', '?', '[', ']'], ' ', $assignment->subject->nom);
            $sheet->setTitle(substr($sheetTitle, 0, 31));

            // Header styling (bold)
            $sheet->setCellValue('A1', 'Matricule');
            $sheet->setCellValue('B1', 'Nom');
            $sheet->setCellValue('C1', 'Prénoms');
            $sheet->setCellValue('D1', 'Moy. interro');
            $sheet->setCellValue('E1', 'Devoir 1');
            $sheet->setCellValue('F1', 'Devoir 2');
            $sheet->getStyle('A1:F1')->getFont()->setBold(true);

            // Définition des largeurs fixes basées sur les pixels demandés (conversion approx)
            $sheet->getColumnDimension('A')->setWidth(23.14); // ~167px
            $sheet->getColumnDimension('B')->setWidth(46);    // ~327px
            $sheet->getColumnDimension('C')->setWidth(46);    // ~327px
            $sheet->getColumnDimension('D')->setWidth(11.7);  // ~87px
            $sheet->getColumnDimension('E')->setWidth(11.7);  // ~87px
            $sheet->getColumnDimension('F')->setWidth(11.7);  // ~87px

            // Alignements
            $sheet->getStyle('A1:A500')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B1:B500')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

            $rowIdx = 2;
            $periode = $request->query('periode', 'Semestre 1');

            foreach ($enrollments as $e) {
                // Pour chaque élève, on récupère ses notes pour CETTE matière (assignment) filtrée par période
                $evals = \App\Models\Evaluation::where('assignment_id', $assignment->id)
                    ->where('periode', $periode)
                    ->orderBy('date', 'asc')
                    ->get();

                $evalIds = $evals->pluck('id');
                $grades = \App\Models\Grade::where('student_id', $e->student_id)
                    ->whereIn('evaluation_id', $evalIds)
                    ->get();

                $interros = [];
                $devoirs = [];

                foreach ($evals as $eval) {
                    $grade = $grades->where('evaluation_id', $eval->id)->first();
                    if ($grade) {
                        if (strtolower($eval->type) === 'interrogation') {
                            $interros[] = $grade->valeur;
                        } else {
                            $devoirs[] = $grade->valeur;
                        }
                    }
                }

                $moyInterro = count($interros) > 0 ? round(array_sum($interros) / count($interros), 2) : '';
                $dev1 = $devoirs[0] ?? '';
                $dev2 = $devoirs[1] ?? '';

                $sheet->setCellValue('A' . $rowIdx, $e->student->matricule);
                $sheet->setCellValue('B' . $rowIdx, strtoupper($e->student->nom));
                $sheet->setCellValue('C' . $rowIdx, mb_convert_case($e->student->prenom, MB_CASE_TITLE, "UTF-8"));
                $sheet->setCellValue('D' . $rowIdx, $moyInterro);
                $sheet->setCellValue('E' . $rowIdx, $dev1);
                $sheet->setCellValue('F' . $rowIdx, $dev2);

                $rowIdx++;
            }
        }

        if ($spreadsheet->getSheetCount() === 0) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle('Aucune donnée');
            $sheet->setCellValue('A1', 'Aucune matière ou note enregistrée pour cette classe.');
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'Liste_' . str_replace(' ', '_', $classe->nom) . '_' . str_replace(' ', '_', $periode) . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function setPrincipal(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'prof_id' => 'required|exists:users,id',
        ]);

        $activeYear = SchoolYear::where('is_active', true)->first();
        if (!$activeYear)
            $activeYear = SchoolYear::first();
        if (!$activeYear)
            return redirect()->back()->with('error', 'Aucune année scolaire active.');

        \App\Models\ClassPrincipal::updateOrCreate(
            ['classe_id' => $request->classe_id, 'school_year_id' => $activeYear->id],
            ['prof_id' => $request->prof_id]
        );

        return redirect()->back()->with('success', 'Professeur principal mis à jour.');
    }
}