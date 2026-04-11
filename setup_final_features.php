<?php
$adminDir = __DIR__ . '/resources/js/Pages/Admin';
$profDir = __DIR__ . '/resources/js/Pages/Prof';
$adminControllerPath = __DIR__ . '/app/Http/Controllers/AdminController.php';
$profControllerPath = __DIR__ . '/app/Http/Controllers/ProfController.php';
$webRoutesPath = __DIR__ . '/routes/web.php';
$layoutPath = __DIR__ . '/resources/js/Layouts/AuthenticatedLayout.vue';

// 1. Routes
$webRoutesContent = file_get_contents($webRoutesPath);
if (strpos($webRoutesContent, 'enrollments') === false) {
    $routesReplacement = <<<EOT
    Route::get('/assignments', [AdminController::class, 'assignments'])->name('assignments');
    Route::post('/assignments', [AdminController::class, 'storeAssignment'])->name('assignments.store');
    Route::delete('/assignments/{id}', [AdminController::class, 'destroyAssignment'])->name('assignments.destroy');

    Route::get('/enrollments', [AdminController::class, 'enrollments'])->name('enrollments');
    Route::post('/enrollments', [AdminController::class, 'storeEnrollment'])->name('enrollments.store');
    Route::delete('/enrollments/{id}', [AdminController::class, 'destroyEnrollment'])->name('enrollments.destroy');
EOT;
    $webRoutesContent = preg_replace('/Route::get\(\'\/assignments\'.*destroyAssignment\'\]\)->name\(\'assignments\.destroy\'\);/s', $routesReplacement, $webRoutesContent);
    
    // Prof routes
    $profRoutesReplacement = <<<EOT
    Route::get('/dashboard', [ProfController::class, 'dashboard'])->name('dashboard');
    Route::get('/evaluations', [ProfController::class, 'evaluations'])->name('evaluations');
    Route::post('/evaluations', [ProfController::class, 'storeEvaluation'])->name('evaluations.store');
    Route::delete('/evaluations/{id}', [ProfController::class, 'destroyEvaluation'])->name('evaluations.destroy');
    Route::get('/grades', [ProfController::class, 'grades'])->name('grades');
EOT;
    $webRoutesContent = preg_replace('/Route::get\(\'\/dashboard\'.*grades\'\]\)->name\(\'grades\'\);/s', $profRoutesReplacement, $webRoutesContent);

    file_put_contents($webRoutesPath, $webRoutesContent);
}

// 2. Layout
$layoutContent = file_get_contents($layoutPath);
if (strpos($layoutContent, 'admin.enrollments') === false) {
    // Add Enrollments to Admin Nav
    $layoutContent = str_replace(
        "<NavLink :href=\"route('admin.students')\" :active=\"route().current('admin.students')\">Étudiants</NavLink>", 
        "<NavLink :href=\"route('admin.students')\" :active=\"route().current('admin.students')\">Étudiants</NavLink>\n                                    <NavLink :href=\"route('admin.enrollments')\" :active=\"route().current('admin.enrollments')\">Inscriptions</NavLink>", 
        $layoutContent
    );
     $layoutContent = str_replace(
        "<ResponsiveNavLink :href=\"route('admin.students')\" :active=\"route().current('admin.students')\">Étudiants</ResponsiveNavLink>", 
        "<ResponsiveNavLink :href=\"route('admin.students')\" :active=\"route().current('admin.students')\">Étudiants</ResponsiveNavLink>\n                            <ResponsiveNavLink :href=\"route('admin.enrollments')\" :active=\"route().current('admin.enrollments')\">Inscriptions</ResponsiveNavLink>", 
        $layoutContent
    );
    file_put_contents($layoutPath, $layoutContent);
}

// 3. AdminController Enrollments
$adminControllerCode = file_get_contents($adminControllerPath);
$storeEnrollmentLogic = <<<EOT
    public function enrollments()
    {
        return Inertia::render('Admin/Enrollments', [
            'enrollments' => App\Models\StudentEnrollment::with(['student', 'classe', 'schoolYear'])->latest()->get(),
            'students' => Student::all(),
            'classes' => Classe::all(),
            'years' => SchoolYear::all()
        ]);
    }

    public function storeEnrollment(Request \$request)
    {
        \$request->validate([
            'student_id' => 'required|exists:students,id',
            'classe_id' => 'required|exists:classes,id',
            'school_year_id' => 'required|exists:school_years,id'
        ]);
        App\Models\StudentEnrollment::create(\$request->all());
        return redirect()->back();
    }

    public function destroyEnrollment(\$id)
    {
        App\Models\StudentEnrollment::findOrFail(\$id)->delete();
        return redirect()->back();
    }
EOT;

if (strpos($adminControllerCode, 'storeEnrollment') === false) {
    $adminControllerCode = str_replace(
        "public function promote()", 
        $storeEnrollmentLogic . "\n\n    public function promote()", 
        $adminControllerCode
    );
    file_put_contents($adminControllerPath, $adminControllerCode);
}

// 4. Enrollments.vue
$enrollmentsVue = <<<EOT
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({ enrollments: Array, students: Array, classes: Array, years: Array });

const creating = ref(false);
const form = useForm({ student_id: '', classe_id: '', school_year_id: '' });

const createItem = () => {
    form.post(route('admin.enrollments.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};
const closeModal = () => {
    creating.value = false; form.reset(); form.clearErrors();
};

const deleteForm = useForm({});
const deleteItem = (id) => {
    if (confirm('Désinscrire cet élève ?')) {
        deleteForm.delete(route('admin.enrollments.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Inscriptions Élèves" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Inscriptions dans les Classes</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Dossiers d'inscriptions</h3>
                        <PrimaryButton @click="creating = true" class="transform hover:-translate-y-0.5 transition">+ Inscrire un élève</PrimaryButton>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200">Élève</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Classe</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Année Scolaire</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in enrollments" :key="item.id" class="hover:bg-indigo-50 transition">
                                    <td class="p-4 font-medium text-gray-900">{{ item.student.nom }} {{ item.student.prenom }}</td>
                                    <td class="p-4 text-indigo-700 font-bold font-mono">{{ item.classe.nom }}</td>
                                    <td class="p-4 text-gray-500">{{ item.school_year.name }}</td>
                                    <td class="p-4 text-right space-x-2">
                                        <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900 font-medium transition cursor-pointer">Désinscrire</button>
                                    </td>
                                </tr>
                                <tr v-if="enrollments.length === 0"><td colspan="4" class="p-8 text-center text-gray-500">Aucune inscription.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="creating" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Nouvelle Inscription</h2>
                <div class="mt-6">
                    <InputLabel value="Élève" />
                    <select v-model="form.student_id" class="mt-1 block w-full border-gray-300 rounded-md">
                        <option v-for="s in students" :key="s.id" :value="s.id">{{ s.nom }} {{ s.prenom }} ({{ s.matricule || 'N/A' }})</option>
                    </select>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Classe" />
                        <select v-model="form.classe_id" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nom }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Année Scolaire" />
                        <select v-model="form.school_year_id" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
                    <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="createItem">Enregistrer</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
EOT;
file_put_contents("$adminDir/Enrollments.vue", $enrollmentsVue);


// 5. ProfController Evaluations
$profControllerCode = file_get_contents($profControllerPath);
$storeEvalLogic = <<<EOT
    public function storeEvaluation(Request \$request)
    {
        \$request->validate([
            'nom' => 'required',
            'type' => 'required',
            'periode' => 'required',
            'assignment_id' => 'required|exists:assignments,id',
            'date_evaluation' => 'required|date'
        ]);
        Evaluation::create(\$request->all());
        return redirect()->back();
    }
    
    public function destroyEvaluation(\$id)
    {
        Evaluation::findOrFail(\$id)->delete();
        return redirect()->back();
    }
EOT;
if (strpos($profControllerCode, 'storeEvaluation') === false) {
    $profControllerCode = str_replace(
        "public function grades(Request \$request)", 
        $storeEvalLogic . "\n\n    public function grades(Request \$request)", 
        $profControllerCode
    );
    // Oh, evaluations method in ProfController needs 'assignments' too
    $evaluationsReplacement = <<<EOT
    public function evaluations(Request \$request)
    {
        \$prof = \$request->user();
        return Inertia::render('Prof/Evaluations', [
            'evaluations' => Evaluation::with(['assignment.classe', 'assignment.subject'])
                ->whereHas('assignment', function(\$query) use (\$prof) {
                    \$query->where('prof_id', \$prof->id);
                })->latest()->get(),
            'assignments' => Assignment::with(['classe', 'subject'])
                ->where('prof_id', \$prof->id)->get()
        ]);
    }
EOT;
    $profControllerCode = preg_replace('/public function evaluations\(Request \$request\).*?\}\n/s', $evaluationsReplacement . "\n", $profControllerCode);
    file_put_contents($profControllerPath, $profControllerCode);
}


// 6. Evaluations.vue
$evaluationsVue = <<<EOT
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({ evaluations: Array, assignments: Array });

const creating = ref(false);
const form = useForm({ nom: '', type: 'Devoir', periode: '', assignment_id: '', date_evaluation: '' });

const createItem = () => {
    form.post(route('prof.evaluations.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};
const closeModal = () => {
    creating.value = false; form.reset(); form.clearErrors();
};

const deleteForm = useForm({});
const deleteItem = (id) => {
    if (confirm('Voulez-vous supprimer cette évaluation ? Toutes les notes associées seront perdues.')) {
        deleteForm.delete(route('prof.evaluations.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Mes Évaluations" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Mes Évaluations</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Toutes vos évaluations</h3>
                        <PrimaryButton @click="creating = true" class="transform hover:-translate-y-0.5 transition">+ Programmer Évaluation</PrimaryButton>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200">Nom & Sujet</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Date et Période</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Classe</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in evaluations" :key="item.id" class="hover:bg-indigo-50 transition">
                                    <td class="p-4 font-medium text-indigo-700">{{ item.nom }} <span class="text-gray-500 text-sm block">{{ item.assignment.subject.nom }} - {{ item.type }}</span></td>
                                    <td class="p-4 text-gray-700">{{ item.date_evaluation }}<span class="block text-xs text-gray-500">{{ item.periode }}</span></td>
                                    <td class="p-4 text-gray-800 font-bold">{{ item.assignment.classe.nom }}</td>
                                    <td class="p-4 text-right space-x-2">
                                        <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900 font-medium transition cursor-pointer">Supprimer</button>
                                    </td>
                                </tr>
                                <tr v-if="evaluations.length === 0"><td colspan="4" class="p-8 text-center text-gray-500">Aucune évaluation.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="creating" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Nouvelle Évaluation</h2>
                <div class="mt-6">
                    <InputLabel value="Nom de l'évaluation (ex: Contrôle continu 1)" />
                    <TextInput v-model="form.nom" type="text" class="mt-1 block w-full" />
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Type" />
                        <select v-model="form.type" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option>Devoir</option>
                            <option>Examen</option>
                            <option>TP</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Période (Trimester/Semestre)" />
                        <TextInput v-model="form.periode" type="text" class="mt-1 block w-full" />
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Classe et Matière" />
                        <select v-model="form.assignment_id" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option v-for="a in assignments" :key="a.id" :value="a.id">{{ a.classe.nom }} - {{ a.subject.nom }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Date prévue" />
                        <TextInput v-model="form.date_evaluation" type="date" class="mt-1 block w-full" />
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
                    <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="createItem">Enregistrer</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
EOT;
file_put_contents("$profDir/Evaluations.vue", $evaluationsVue);

echo "Final Features Setup Complete!";
