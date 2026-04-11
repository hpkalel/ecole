<?php
$profDir = __DIR__ . '/resources/js/Pages/Prof';
$profControllerPath = __DIR__ . '/app/Http/Controllers/ProfController.php';
$webRoutesPath = __DIR__ . '/routes/web.php';
$evaluationsVuePath = $profDir . '/Evaluations.vue';

// 1. Web Routes
$webRoutesContent = file_get_contents($webRoutesPath);
if (strpos($webRoutesContent, 'grades.store') === false) {
    $routesReplacement = <<<EOT
    Route::get('/grades', [ProfController::class, 'grades'])->name('grades');
    Route::post('/grades', [ProfController::class, 'storeGrades'])->name('grades.store');
EOT;
    $webRoutesContent = str_replace(
        "Route::get('/grades', [ProfController::class, 'grades'])->name('grades');", 
        $routesReplacement, 
        $webRoutesContent
    );
    file_put_contents($webRoutesPath, $webRoutesContent);
}

// 2. ProfController
$profControllerContent = file_get_contents($profControllerPath);
$gradesLogic = <<<EOT
    public function grades(Request \$request)
    {
        \$evaluation_id = \$request->query('evaluation_id');
        if (!\$evaluation_id) {
            return redirect()->route('prof.evaluations');
        }

        \$evaluation = Evaluation::with(['assignment.classe', 'assignment.subject'])->findOrFail(\$evaluation_id);
        
        if (\$evaluation->assignment->prof_id !== \$request->user()->id) {
            abort(403);
        }

        \$studentsQuery = App\Models\StudentEnrollment::with('student')
            ->where('class_id', \$evaluation->assignment->classe_id)
            ->where('school_year_id', \$evaluation->assignment->school_year_id)
            ->get();
            
        \$students = \$studentsQuery->pluck('student')->sortBy(['nom', 'prenom'])->values();

        \$grades = App\Models\Grade::where('evaluation_id', \$evaluation_id)->get()->keyBy('student_id');

        return Inertia::render('Prof/Grades', [
            'evaluation' => \$evaluation,
            'students' => \$students,
            'existingGrades' => \$grades
        ]);
    }

    public function storeGrades(Request \$request)
    {
        \$evaluation_id = \$request->input('evaluation_id');
        \$notes = \$request->input('notes', []);
        \$comments = \$request->input('comments', []);

        foreach (\$notes as \$student_id => \$val) {
            if (\$val === null || \$val === '') continue;

            App\Models\Grade::updateOrCreate(
                ['evaluation_id' => \$evaluation_id, 'student_id' => \$student_id],
                ['valeur' => \$val, 'appreciation' => \$comments[\$student_id] ?? null]
            );
        }

        return redirect()->back();
    }
EOT;

if (strpos($profControllerContent, 'storeGrades') === false) {
    // Replace the empty grades() method with the new logic
    $profControllerContent = preg_replace('/public function grades\(Request \$request\)\s*\{\s*return Inertia::render\(\'Prof\/Grades\'\);\s*\}/s', $gradesLogic, $profControllerContent);
    // If not found, append before the end
    if (strpos($profControllerContent, 'storeGrades') === false) {
        $profControllerContent = preg_replace('/\}\s*$/', "\n" . $gradesLogic . "\n}", $profControllerContent);
    }
    file_put_contents($profControllerPath, $profControllerContent);
}

// 3. Update Evaluations.vue to add the "Saisir les notes" link
$evaluationsVueContent = file_get_contents($evaluationsVuePath);
if (strpos($evaluationsVueContent, 'Saisir les notes') === false) {
    // Inject Link import
    $evaluationsVueContent = str_replace(
        "import { Head, useForm } from '@inertiajs/vue3';",
        "import { Head, useForm, Link } from '@inertiajs/vue3';",
        $evaluationsVueContent
    );
    // Add Link in action column
    $evaluationsVueContent = str_replace(
        '<button @click="deleteItem(item.id)"',
        '<Link :href="route(\'prof.grades\', {evaluation_id: item.id})" class="text-blue-600 hover:text-blue-900 font-medium transition cursor-pointer">Saisir les notes</Link>
                                        <button @click="deleteItem(item.id)"',
        $evaluationsVueContent
    );
    file_put_contents($evaluationsVuePath, $evaluationsVueContent);
}

// 4. Create Grades.vue
$gradesVue = <<<EOT
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({ evaluation: Object, students: Array, existingGrades: Object });

// Initialize form dynamically
const form = useForm({
    evaluation_id: props.evaluation.id,
    notes: {},
    comments: {}
});

onMounted(() => {
    props.students.forEach(student => {
        if (props.existingGrades[student.id]) {
            form.notes[student.id] = props.existingGrades[student.id].valeur;
            form.comments[student.id] = props.existingGrades[student.id].appreciation || '';
        } else {
            form.notes[student.id] = '';
            form.comments[student.id] = '';
        }
    });
});

const saveGrades = () => {
    form.post(route('prof.grades.store'), {
        preserveScroll: true,
        onSuccess: () => {
            alert('Notes enregistrées avec succès !');
        }
    });
};
</script>

<template>
    <Head title="Saisie des Notes" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Saisie des Notes : {{ evaluation.nom }}
                </h2>
                <Link :href="route('prof.evaluations')" class="text-indigo-600 hover:text-indigo-900 font-medium">&larr; Retour</Link>
            </div>
        </template>
        
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-indigo-50">
                        <div>
                            <h3 class="text-lg font-bold text-indigo-900">{{ evaluation.assignment.classe.nom }} - {{ evaluation.assignment.subject.nom }}</h3>
                            <p class="text-sm text-indigo-700 mt-1">Type : {{ evaluation.type }} | Période : {{ evaluation.periode }}</p>
                        </div>
                        <PrimaryButton @click="saveGrades" :disabled="form.processing">Enregistrer les Notes</PrimaryButton>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200" style="width: 30%">Élève</th>
                                    <th class="p-4 font-semibold border-b border-gray-200" style="width: 15%">Note / 20</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Appréciation</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="student in students" :key="student.id" class="hover:bg-gray-50 transition">
                                    <td class="p-4 font-medium text-gray-900">{{ student.nom }} {{ student.prenom }}</td>
                                    <td class="p-4">
                                        <TextInput 
                                            type="number" 
                                            step="0.25" 
                                            min="0" 
                                            max="20"
                                            v-model="form.notes[student.id]" 
                                            class="w-full text-center"
                                            placeholder="—"
                                        />
                                    </td>
                                    <td class="p-4">
                                        <TextInput 
                                            type="text" 
                                            v-model="form.comments[student.id]" 
                                            class="w-full"
                                            placeholder="Commentaire optionnel..."
                                        />
                                    </td>
                                </tr>
                                <tr v-if="students.length === 0">
                                    <td colspan="3" class="p-8 text-center text-gray-500">Aucun élève inscrit dans cette classe.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
EOT;

file_put_contents("$profDir/Grades.vue", $gradesVue);

echo "Grades Setup OK.";
