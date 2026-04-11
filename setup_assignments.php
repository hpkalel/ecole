<?php
$adminDir = __DIR__ . '/resources/js/Pages/Admin';
$controllerPath = __DIR__ . '/app/Http/Controllers/AdminController.php';
$profControllerPath = __DIR__ . '/app/Http/Controllers/ProfController.php';
$webRoutesPath = __DIR__ . '/routes/web.php';

// ADmin controller assignments routes
$controllerCode = file_get_contents($controllerPath);
$storeAssignmentLogic = <<<EOT
    public function assignments()
    {
        return Inertia::render('Admin/Assignments', [
            'assignments' => App\Models\Assignment::with(['prof', 'subject', 'classe', 'schoolYear'])->latest()->get(),
            'profs' => User::where('role', 'prof')->get(),
            'classes' => Classe::all(),
            'subjects' => Subject::all(),
            'years' => SchoolYear::all()
        ]);
    }

    public function storeAssignment(Request \$request)
    {
        \$request->validate([
            'prof_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'classe_id' => 'required|exists:classes,id',
            'school_year_id' => 'required|exists:school_years,id',
            'coefficient' => 'required|integer|min:1'
        ]);

        App\Models\Assignment::create(\$request->all());
        return redirect()->back()->with('success', 'Attribution réussie.');
    }

    public function destroyAssignment(\$id)
    {
        App\Models\Assignment::findOrFail(\$id)->delete();
        return redirect()->back();
    }
EOT;

if (strpos($controllerCode, 'storeAssignment') === false) {
    $controllerCode = str_replace(
        "public function promote()", 
        $storeAssignmentLogic . "\n\n    public function promote()", 
        $controllerCode
    );
    file_put_contents($controllerPath, $controllerCode);
}

// routes
$webRoutesContent = file_get_contents($webRoutesPath);
if (strpos($webRoutesContent, 'assignments') === false) {
    $routesReplacement = <<<EOT
    Route::get('/profs', [AdminController::class, 'profs'])->name('profs');
    Route::post('/profs', [AdminController::class, 'storeProf'])->name('profs.store');
    Route::delete('/profs/{id}', [AdminController::class, 'destroyProf'])->name('profs.destroy');

    Route::get('/assignments', [AdminController::class, 'assignments'])->name('assignments');
    Route::post('/assignments', [AdminController::class, 'storeAssignment'])->name('assignments.store');
    Route::delete('/assignments/{id}', [AdminController::class, 'destroyAssignment'])->name('assignments.destroy');
EOT;
    $webRoutesContent = preg_replace('/Route::get\(\'\/profs\'.*destroyProf\'\]\)->name\(\'profs\.destroy\'\);/s', $routesReplacement, $webRoutesContent);
    file_put_contents($webRoutesPath, $webRoutesContent);
}


// Layout Update for Assignments
$layoutPath = __DIR__ . '/resources/js/Layouts/AuthenticatedLayout.vue';
$layoutContent = file_get_contents($layoutPath);
if (strpos($layoutContent, 'admin.assignments') === false) {
    $layoutContent = str_replace(
        "<NavLink :href=\"route('admin.profs')\" :active=\"route().current('admin.profs')\">Professeurs</NavLink>", 
        "<NavLink :href=\"route('admin.profs')\" :active=\"route().current('admin.profs')\">Professeurs</NavLink>\n                                    <NavLink :href=\"route('admin.assignments')\" :active=\"route().current('admin.assignments')\">Attributions</NavLink>", 
        $layoutContent
    );
     $layoutContent = str_replace(
        "<ResponsiveNavLink :href=\"route('admin.profs')\" :active=\"route().current('admin.profs')\">Professeurs</ResponsiveNavLink>", 
        "<ResponsiveNavLink :href=\"route('admin.profs')\" :active=\"route().current('admin.profs')\">Professeurs</ResponsiveNavLink>\n                            <ResponsiveNavLink :href=\"route('admin.assignments')\" :active=\"route().current('admin.assignments')\">Attributions</ResponsiveNavLink>", 
        $layoutContent
    );
    file_put_contents($layoutPath, $layoutContent);
}


// Assignments.vue
$assignmentsVue = <<<EOT
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({ assignments: Array, profs: Array, classes: Array, subjects: Array, years: Array });

const creating = ref(false);
const form = useForm({ prof_id: '', subject_id: '', classe_id: '', school_year_id: '', coefficient: 1 });

const createItem = () => {
    form.post(route('admin.assignments.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};
const closeModal = () => {
    creating.value = false;
    form.reset();
    form.clearErrors();
};

const deleteForm = useForm({});
const deleteItem = (id) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette attribution ?')) {
        deleteForm.delete(route('admin.assignments.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Attribution des Matières" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Attribution des Matières</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Attributions existantes</h3>
                        <PrimaryButton @click="creating = true" class="transform hover:-translate-y-0.5 transition">+ Nouvelle Attribution</PrimaryButton>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200">Professeur</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Classe</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Matière</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Année</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in assignments" :key="item.id" class="hover:bg-indigo-50 transition">
                                    <td class="p-4 font-medium text-gray-900">{{ item.prof.nom }}</td>
                                    <td class="p-4 text-gray-800">{{ item.classe.nom }}</td>
                                    <td class="p-4 text-gray-600">{{ item.subject.nom }}</td>
                                    <td class="p-4 text-gray-500 text-sm">{{ item.school_year.name }}</td>
                                    <td class="p-4 text-right space-x-2">
                                        <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900 font-medium transition cursor-pointer">Annuler</button>
                                    </td>
                                </tr>
                                <tr v-if="assignments.length === 0"><td colspan="5" class="p-8 text-center text-gray-500">Aucune attribution.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="creating" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Nouvelle Attribution</h2>
                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Professeur" />
                        <select v-model="form.prof_id" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option v-for="p in profs" :key="p.id" :value="p.id">{{ p.nom }}</option>
                        </select>
                        <InputError :message="form.errors.prof_id" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel value="Classe" />
                        <select v-model="form.classe_id" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nom }}</option>
                        </select>
                        <InputError :message="form.errors.classe_id" class="mt-2" />
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Matière" />
                        <select v-model="form.subject_id" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.nom }}</option>
                        </select>
                        <InputError :message="form.errors.subject_id" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel value="Année Scolaire" />
                        <select v-model="form.school_year_id" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                        </select>
                        <InputError :message="form.errors.school_year_id" class="mt-2" />
                    </div>
                </div>
                <div class="mt-4">
                    <InputLabel value="Coefficient appliqué" />
                    <TextInput v-model="form.coefficient" type="number" class="mt-1 w-32" />
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
                    <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="createItem">Attribuer</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
EOT;

file_put_contents("$adminDir/Assignments.vue", $assignmentsVue);
echo "Assignments Setup completed.";
