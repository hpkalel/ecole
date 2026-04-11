<?php
// setup_bulletin_logic.php
$adminDir = __DIR__ . '/resources/js/Pages/Admin';
$adminControllerPath = __DIR__ . '/app/Http/Controllers/AdminController.php';
$webRoutesPath = __DIR__ . '/routes/web.php';
$enrollmentsVuePath = $adminDir . '/Enrollments.vue';

// 1. Web Routes
$webRoutesContent = file_get_contents($webRoutesPath);
if (strpos($webRoutesContent, '/bulletin') === false) {
    $routesReplacement = <<<EOT
    Route::get('/enrollments', [AdminController::class, 'enrollments'])->name('enrollments');
    Route::post('/enrollments', [AdminController::class, 'storeEnrollment'])->name('enrollments.store');
    Route::delete('/enrollments/{id}', [AdminController::class, 'destroyEnrollment'])->name('enrollments.destroy');
    Route::get('/bulletin/{student_id}', [AdminController::class, 'bulletin'])->name('bulletin');
EOT;
    $webRoutesContent = str_replace(
        "Route::get('/enrollments', [AdminController::class, 'enrollments'])->name('enrollments');\n    Route::post('/enrollments', [AdminController::class, 'storeEnrollment'])->name('enrollments.store');\n    Route::delete('/enrollments/{id}', [AdminController::class, 'destroyEnrollment'])->name('enrollments.destroy');", 
        $routesReplacement, 
        $webRoutesContent
    );
    file_put_contents($webRoutesPath, $webRoutesContent);
}

// 2. AdminController bulletin method
$adminControllerContent = file_get_contents($adminControllerPath);
$bulletinLogic = <<<EOT
    private function calculatePeriodStats(\$student_id, \$periode, \$school_year_id)
    {
        \$grades = App\Models\Grade::with(['evaluation.assignment.subject'])
            ->whereHas('evaluation', function (\$q) use (\$periode, \$school_year_id) {
                \$q->where('periode', \$periode)
                  ->whereHas('assignment', function (\$q2) use (\$school_year_id) {
                      \$q2->where('school_year_id', \$school_year_id);
                  });
            })
            ->where('student_id', \$student_id)
            ->get();

        \$totalScore = 0;
        \$totalCoeff = 0;
        \$subjects = [];

        foreach (\$grades as \$grade) {
            \$subjectName = \$grade->evaluation->assignment->subject->nom;
            if (!isset(\$subjects[\$subjectName])) {
                \$subjects[\$subjectName] = [
                    'sum' => 0,
                    'count' => 0,
                    'coeff' => \$grade->evaluation->assignment->coefficient,
                ];
            }
            \$subjects[\$subjectName]['sum'] += \$grade->valeur;
            \$subjects[\$subjectName]['count']++;
        }

        foreach (\$subjects as \$sub) {
            \$avg = \$sub['sum'] / \$sub['count'];
            \$totalScore += \$avg * \$sub['coeff'];
            \$totalCoeff += \$sub['coeff'];
        }

        return \$totalCoeff > 0 ? \$totalScore / \$totalCoeff : null;
    }

    public function bulletin(Request \$request, \$student_id)
    {
        \$student = App\Models\Student::findOrFail(\$student_id);
        \$periode = \$request->query('periode', 'Semestre 1');
        \$school_year_id = \$request->query('school_year_id');

        if (!\$school_year_id) {
            \$activeYear = App\Models\SchoolYear::where('is_active', true)->first();
            if(!\$activeYear) {
                \$activeYear = App\Models\SchoolYear::first();
            }
            if(!\$activeYear) abort(404, 'Aucune année scolaire active.');
            \$school_year_id = \$activeYear->id;
        } else {
            \$activeYear = App\Models\SchoolYear::find(\$school_year_id);
        }

        \$enrollment = App\Models\StudentEnrollment::with('classe')
            ->where('student_id', \$student_id)
            ->where('school_year_id', \$school_year_id)
            ->first();

        // Get count of students in class
        \$classCount = 0;
        if (\$enrollment) {
            \$classCount = App\Models\StudentEnrollment::where('class_id', \$enrollment->class_id)
                ->where('school_year_id', \$school_year_id)->count();
        }

        // Fetch grades explicitly mimicking legacy join logic
        \$gradesRaw = \DB::select("
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
            ORDER BY s.nom, e.date_evaluation DEFAULT CURRENT_DATE
        ", [\$student_id, \$periode, \$school_year_id]);

        \$subjectsData = [];
        \$maxInterros = 0;
        \$maxDevoirs = 0;

        foreach (\$gradesRaw as \$row) {
            \$subject = \$row->subject_name;
            if (!isset(\$subjectsData[\$subject])) {
                \$subjectsData[\$subject] = [
                    'subject_name' => \$subject,
                    'prof' => \$row->prof_name,
                    'coeff' => \$row->coefficient,
                    'interros' => [],
                    'devoirs' => [],
                    'total_val' => 0,
                    'count_val' => 0,
                    'total_interro' => 0,
                    'count_interro' => 0
                ];
            }
            
            if (strtolower(\$row->type) === 'interrogation') {
                \$subjectsData[\$subject]['interros'][] = \$row;
                \$subjectsData[\$subject]['total_interro'] += \$row->valeur;
                \$subjectsData[\$subject]['count_interro']++;
            } else {
                \$subjectsData[\$subject]['devoirs'][] = \$row;
            }

            \$subjectsData[\$subject]['total_val'] += \$row->valeur;
            \$subjectsData[\$subject]['count_val']++;

            if (count(\$subjectsData[\$subject]['interros']) > \$maxInterros) \$maxInterros = count(\$subjectsData[\$subject]['interros']);
            if (count(\$subjectsData[\$subject]['devoirs']) > \$maxDevoirs) \$maxDevoirs = count(\$subjectsData[\$subject]['devoirs']);
        }

        \$globalTotal = 0;
        \$globalCoeff = 0;
        \$formattedSubjects = [];

        foreach (\$subjectsData as \$subject => \$data) {
            if (\$data['count_interro'] > 0) \$data['avg_interro'] = round(\$data['total_interro'] / \$data['count_interro'], 2);
            else \$data['avg_interro'] = null;

            if (\$data['count_val'] > 0) {
                \$data['average'] = round(\$data['total_val'] / \$data['count_val'], 2);
                \$data['weighted_score'] = round(\$data['average'] * \$data['coeff'], 2);
                \$globalTotal += \$data['weighted_score'];
                \$globalCoeff += \$data['coeff'];
            } else {
                \$data['average'] = null;
                \$data['weighted_score'] = 0;
            }
            \$formattedSubjects[] = \$data;
        }

        \$globalAverage = \$globalCoeff > 0 ? round(\$globalTotal / \$globalCoeff, 2) : null;
        
        \$annualAverage = null;
        \$sem1Average = null;
        
        if (\$periode === 'Semestre 2') {
            \$sem1Average = \$this->calculatePeriodStats(\$student_id, 'Semestre 1', \$school_year_id);
            if (\$sem1Average !== null && \$globalAverage !== null) {
                \$annualAverage = round(((\$sem1Average * 2) + \$globalAverage) / 3, 2);
            }
        }

        return Inertia::render('Admin/Bulletin', [
            'student' => \$student,
            'enrollment' => \$enrollment,
            'classCount' => \$classCount,
            'activeYear' => \$activeYear,
            'periode' => \$periode,
            'subjectsData' => \$formattedSubjects,
            'maxInterros' => \$maxInterros,
            'maxDevoirs' => \$maxDevoirs,
            'globalTotal' => \$globalTotal,
            'globalCoeff' => \$globalCoeff,
            'globalAverage' => \$globalAverage,
            'sem1Average' => \$sem1Average,
            'annualAverage' => \$annualAverage
        ]);
    }
EOT;

if (strpos($adminControllerContent, 'function bulletin(') === false) {
    if (strpos($adminControllerContent, 'public function promote') !== false) {
        $adminControllerContent = str_replace(
            "public function promote()", 
            $bulletinLogic . "\n\n    public function promote()", 
            $adminControllerContent
        );
    } else {
        $adminControllerContent = preg_replace('/\}\s*$/', "\n" . $bulletinLogic . "\n}", $adminControllerContent);
    }
    file_put_contents($adminControllerPath, $adminControllerContent);
}

// 3. Mod Enrollments.vue
$enrollmentsVueContent = file_get_contents($enrollmentsVuePath);
if (strpos($enrollmentsVueContent, 'admin.bulletin') === false) {
    $enrollmentsVueContent = str_replace(
        "import { Head, useForm } from '@inertiajs/vue3';",
        "import { Head, useForm, Link } from '@inertiajs/vue3';", // ensure Link is imported
        $enrollmentsVueContent
    );
    $enrollmentsVueContent = str_replace(
        '<button @click="deleteItem(item.id)"',
        '<a :href="route(\'admin.bulletin\', {student_id: item.student.id, school_year_id: item.school_year_id})" class="text-blue-600 hover:text-blue-900 font-medium transition cursor-pointer mr-2">Bulletin</a>
                                        <button @click="deleteItem(item.id)"',
        $enrollmentsVueContent
    );
    file_put_contents($enrollmentsVuePath, $enrollmentsVueContent);
}

// 4. Create Bulletin.vue
$bulletinVue = <<<EOT
<script setup>
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    student: Object,
    enrollment: Object,
    classCount: Number,
    activeYear: Object,
    periode: String,
    subjectsData: Array,
    maxInterros: Number,
    maxDevoirs: Number,
    globalTotal: Number,
    globalCoeff: Number,
    globalAverage: Number,
    sem1Average: Number,
    annualAverage: Number
});

const getBadgeClasses = (avg) => {
    if (avg === null) return 'text-gray-400';
    if (avg < 8) return 'bg-red-100 text-red-700 px-2 rounded';
    if (avg < 10) return 'bg-orange-100 text-orange-700 px-2 rounded';
    if (avg < 12) return 'bg-yellow-100 text-yellow-700 px-2 rounded';
    if (avg < 14) return 'bg-blue-100 text-blue-700 px-2 rounded';
    if (avg < 16) return 'bg-green-100 text-green-700 px-2 rounded';
    return 'bg-indigo-600 text-white px-2 rounded font-bold';
};

const getAppreciationText = (avg) => {
    if (avg === null) return '-';
    if (avg < 8) return 'Médiocre';
    if (avg < 10) return 'Insuffisant';
    if (avg < 12) return 'Passable';
    if (avg < 14) return 'Assez Bien';
    if (avg < 16) return 'Bien';
    return 'T. Bien';
};

const changePeriode = (e) => {
    router.visit(route('admin.bulletin', {
        student_id: props.student.id,
        school_year_id: props.activeYear.id,
        periode: e.target.value
    }));
};
</script>

<template>
    <Head :title="'Bulletin - ' + student.nom" />

    <div class="min-h-screen bg-gray-50 flex flex-col p-4 sm:p-8 relative">
        <div class="max-w-[1100px] mx-auto w-full print:hidden mb-6 flex justify-between items-center">
            <Link :href="route('admin.enrollments')" class="bg-white border shadow-sm px-4 py-2 rounded-md font-medium text-gray-700 hover:bg-gray-50">&larr; Retour</Link>
            <button onclick="window.print()" class="bg-indigo-600 text-white shadow px-6 py-2 rounded-md font-bold hover:bg-indigo-700">🖨️ Imprimer le bulletin</button>
        </div>

        <div class="max-w-[1100px] mx-auto w-full bg-white rounded-xl shadow-xl overflow-hidden print:shadow-none print:w-full print:max-w-none border border-gray-200">
            <!-- Header -->
            <div class="p-8 border-b-2 border-indigo-600 flex justify-between items-start flex-wrap gap-4 print:p-0 print:pb-4">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 uppercase">GROUPE SCOLAIRE ECOLE 2</h1>
                    <p class="text-xs font-bold text-gray-500 tracking-widest mt-1">RÉPUBLIQUE DU BÉNIN</p>
                    <p class="text-[0.65rem] text-gray-400 font-semibold tracking-wider">Fraternité - Justice - Travail</p>
                </div>
                <div class="text-right">
                    <h2 class="text-3xl font-black text-indigo-700 tracking-tight uppercase">BULLETIN DE NOTES</h2>
                    <p class="font-bold text-gray-600 mt-1">ANNÉE SCOLAIRE : {{ activeYear.name }}</p>
                    
                    <select :value="periode" @change="changePeriode" class="print:hidden mt-2 border-gray-300 rounded font-semibold text-indigo-700">
                        <option value="Semestre 1">Semestre 1</option>
                        <option value="Semestre 2">Semestre 2</option>
                    </select>
                    <p class="hidden print:block text-lg font-bold text-gray-500 mt-1">{{ periode }}</p>
                </div>
            </div>

            <!-- Info -->
            <div class="p-6 bg-slate-50 border-b border-gray-200 flex justify-between print:p-2 print:bg-transparent">
                <div class="space-y-2">
                    <div class="grid grid-cols-[100px_1fr] items-baseline">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Élève :</span>
                        <span class="text-xl font-black text-gray-900">{{ student.nom }} {{ student.prenom }}</span>
                    </div>
                    <div class="grid grid-cols-[100px_1fr] items-baseline">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Matricule :</span>
                        <span class="font-mono text-gray-700 font-bold bg-gray-200 px-2 rounded">{{ student.matricule || student.id.toString().padStart(6, '0') }}</span>
                    </div>
                </div>
                <div class="space-y-2 text-right">
                    <div class="flex justify-end gap-3 items-baseline">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Classe :</span>
                        <span class="text-lg font-bold text-indigo-800">{{ enrollment ? enrollment.classe.nom : 'Non assigné' }}</span>
                    </div>
                    <div class="flex justify-end gap-3 items-baseline">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Effectif :</span>
                        <span class="font-semibold text-gray-700">{{ classCount }} élèves</span>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="p-6 print:p-0">
                <table class="w-full text-[0.8rem] text-center border-collapse print:text-[0.65rem]">
                    <thead>
                        <tr class="bg-gray-100 text-gray-500 font-bold tracking-wider border border-gray-300">
                            <th class="py-2 px-3 text-left border-r border-gray-300 uppercase">Matière</th>
                            <th class="py-2 px-1 border-r border-gray-300">Coeff</th>
                            <th v-for="i in maxInterros" :key="'hi'+i" class="py-2 px-1 border-r border-gray-200 text-gray-400">Int. {{i}}</th>
                            <th class="py-2 px-1 border-r border-gray-300 bg-gray-200">Moy. Int.</th>
                            <th v-for="i in maxDevoirs" :key="'hd'+i" class="py-2 px-1 border-r border-gray-200 text-gray-400">Dev. {{i}}</th>
                            <th class="py-2 px-2 border-r border-gray-300 bg-gray-200">Moy. Gén.</th>
                            <th class="py-2 px-2 border-r border-gray-300 bg-slate-200 text-indigo-900 border-l-2 border-l-gray-400">Moy. Coef.</th>
                            <th class="py-2 px-3 text-left border-r border-gray-300">Appréciation</th>
                            <th class="py-2 px-2 border-gray-300 text-xs">Professeur</th>
                        </tr>
                    </thead>
                    <tbody class="border border-gray-300">
                        <tr v-for="sub in subjectsData" :key="sub.subject_name" class="border-b border-gray-200 hover:bg-slate-50">
                            <td class="py-3 px-3 text-left font-bold text-gray-800 border-r border-gray-300 print:py-1">{{ sub.subject_name }}</td>
                            <td class="py-3 px-1 font-bold text-gray-400 border-r border-gray-300 print:py-1">{{ sub.coeff }}</td>
                            
                            <td v-for="i in maxInterros" :key="'di'+i" class="py-3 px-1 border-r border-gray-200 print:py-1">
                                {{ sub.interros[i-1] ? sub.interros[i-1].valeur : '-' }}
                            </td>
                            <td class="py-3 px-1 font-semibold text-gray-500 bg-slate-50 border-r border-gray-300 print:py-1">
                                {{ sub.avg_interro !== null ? sub.avg_interro : '-' }}
                            </td>
                            
                            <td v-for="i in maxDevoirs" :key="'dd'+i" class="py-3 px-1 border-r border-gray-200 print:py-1">
                                {{ sub.devoirs[i-1] ? sub.devoirs[i-1].valeur : '-' }}
                            </td>
                            <td class="py-3 px-2 font-black text-gray-700 bg-slate-50 border-r border-gray-300 print:py-1">
                                {{ sub.average !== null ? sub.average : '-' }}
                            </td>
                            <td class="py-3 px-2 font-black text-indigo-900 bg-slate-100 border-r border-gray-300 border-l-2 border-l-gray-300 print:py-1">
                                {{ sub.weighted_score !== null ? sub.weighted_score : '-' }}
                            </td>
                            <td class="py-3 px-3 text-left font-bold uppercase tracking-tight text-[0.7rem] border-r border-gray-300 print:py-1">
                                <span :class="getBadgeClasses(sub.average)">{{ getAppreciationText(sub.average) }}</span>
                            </td>
                            <td class="py-3 px-2 text-[0.65rem] text-gray-400 font-bold uppercase print:py-1">
                                {{ sub.prof }}
                            </td>
                        </tr>
                        <tr v-if="subjectsData.length === 0">
                            <td :colspan="6 + maxInterros + maxDevoirs" class="p-8 text-gray-400 font-bold">Aucune note enregistrée pour le {{ periode }}.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="subjectsData.length > 0">
                        <tr class="bg-gray-100 font-bold text-gray-800 border-l border-r border-gray-300">
                            <td class="py-3 px-3 text-left border-r border-gray-300">TOTAL</td>
                            <td class="py-3 px-1 border-r border-gray-300">{{ globalCoeff }}</td>
                            <td :colspan="maxInterros + maxDevoirs + 2" class="border-r border-gray-300 bg-slate-50"></td>
                            <td class="py-3 px-2 border-r border-gray-300 text-[0.95rem] bg-slate-200 border-l-2 border-l-gray-400 text-indigo-900">{{ globalTotal }}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr class="bg-slate-200 border-l border-r border-b border-gray-300">
                            <td :colspan="2 + maxInterros + maxDevoirs + 2" class="py-4 px-4 text-right font-black uppercase text-gray-600 tracking-wider">Moyenne Générale {{ periode }}</td>
                            <td class="py-4 px-2 font-black text-xl text-white bg-indigo-600 border-x border-indigo-700 shadow-inner align-middle">
                                {{ globalAverage !== null ? globalAverage : 'N/A' }}
                            </td>
                            <td colspan="2" class="bg-slate-100"></td>
                        </tr>
                        
                        <!-- Lignes Annuelles (Semestre 2 uniquement) -->
                        <tr v-if="periode === 'Semestre 2' && sem1Average !== null" class="bg-white border border-t-2 border-t-gray-400">
                            <td :colspan="2 + maxInterros + maxDevoirs + 2" class="py-3 px-4 text-right font-bold uppercase text-gray-400 text-xs">Moyenne Semestre 1 Rappel</td>
                            <td class="py-3 px-2 font-bold text-[0.95rem] text-gray-500 bg-gray-50 align-middle border-x border-gray-300">{{ sem1Average }}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr v-if="periode === 'Semestre 2' && sem1Average !== null" class="bg-green-50 border border-green-200">
                            <td :colspan="2 + maxInterros + maxDevoirs + 2" class="py-4 px-4 text-right font-black uppercase text-green-800 tracking-wider">MOYENNE ANNUELLE (S1×2 + S2) / 3</td>
                            <td class="py-4 px-2 font-black text-2xl text-green-900 bg-green-200 border-x border-green-300 shadow-inner align-middle">
                                {{ annualAverage }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Signatures -->
            <div class="mt-8 mb-16 px-16 flex justify-between items-end print:mt-4 print:mb-8 text-xs font-bold text-gray-400 uppercase tracking-wider">
                <div class="text-center w-64 border-b-2 border-gray-300 pb-20">Signatures des Parents</div>
                <div class="text-center w-64 border-b-2 border-gray-300 pb-20">Directeur des Études</div>
            </div>
            
            <!-- Watermark -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.02] overflow-hidden -z-10">
                <span class="text-[15rem] font-black tracking-tighter -rotate-12 whitespace-nowrap">ECOLE 2</span>
            </div>
        </div>
    </div>
</template>
<style>
@media print {
    @page { size: A4 portrait; margin: 10mm; }
    body { background-color: white !important; }
}
</style>
EOT;

file_put_contents($adminDir . '/Bulletin.vue', $bulletinVue);
echo "Bulletin Setup OK.";
