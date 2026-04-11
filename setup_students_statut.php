<?php
$adminDir = __DIR__ . '/resources/js/Pages/Admin';
$adminControllerPath = __DIR__ . '/app/Http/Controllers/AdminController.php';

// 1. Mod AdminController pour fournir records & gérer l'enrollment natif
$adminControllerContent = file_get_contents($adminControllerPath);

$studentsLogic = <<<EOT
    public function students()
    {
        return Inertia::render('Admin/Students', [
            'students' => \App\Models\Student::with(['enrollments.classe', 'enrollments.schoolYear'])->latest()->get(),
            'classes' => \App\Models\Classe::all(),
            'years' => \App\Models\SchoolYear::all(),
            'activeYearId' => \App\Models\SchoolYear::where('is_active', true)->value('id') ?? \App\Models\SchoolYear::value('id')
        ]);
    }

    public function storeStudent(Request \$request)
    {
        \$request->validate([
            'matricule' => 'nullable|string|max:50|unique:students,matricule',
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'sexe' => 'required|in:M,F',
            'classe_id' => 'required|exists:classes,id',
            'school_year_id' => 'required|exists:school_years,id',
            'statut' => 'required|in:Nouveau,Redoublant'
        ]);

        \DB::transaction(function () use (\$request) {
            \$student = \App\Models\Student::create(\$request->only('matricule', 'nom', 'prenom', 'sexe'));
            
            \App\Models\StudentEnrollment::create([
                'student_id' => \$student->id,
                'class_id' => \$request->classe_id, // Watch out for class_id vs classe_id. In StudentEnrollment it is class_id
                'school_year_id' => \$request->school_year_id,
                'statut' => \$request->statut
            ]);
        });
        
        return redirect()->back()->with('success', 'Élève ajouté et inscrit.');
    }
EOT;

$adminControllerContent = preg_replace(
    '/public function students\(\).*?return redirect\(\)->back\(\)->with\(\'success\', \'Élève ajouté\.\'\);\s*\}/s', 
    $studentsLogic, 
    $adminControllerContent
);
file_put_contents($adminControllerPath, $adminControllerContent);

// 2. Mod Students.vue
$studentsVue = <<<EOT
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

const props = defineProps({ students: Array, classes: Array, years: Array, activeYearId: Number });

const creating = ref(false);
const form = useForm({ 
    matricule: '', 
    nom: '', 
    prenom: '', 
    sexe: 'M',
    classe_id: '',
    school_year_id: props.activeYearId || '',
    statut: 'Nouveau'
});

const createItem = () => {
    form.post(route('admin.students.store'), {
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
    if (confirm('Supprimer cet élève effacera tout son historique. Voulez-vous continuer ?')) {
        deleteForm.delete(route('admin.students.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Gestion des Élèves" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Gestion des Élèves</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Liste des Élèves</h3>
                        <PrimaryButton @click="creating = true" class="transform hover:-translate-y-0.5 transition">+ Ajouter un Élève</PrimaryButton>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200">Matricule</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Nom & Prénom</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Sexe</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Classe Actuelle</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in students" :key="item.id" class="hover:bg-indigo-50 transition">
                                    <td class="p-4 text-gray-500 font-mono">{{ item.matricule || '-' }}</td>
                                    <td class="p-4 font-medium text-gray-900">{{ item.nom }} {{ item.prenom }}</td>
                                    <td class="p-4 text-gray-500">{{ item.sexe }}</td>
                                    <td class="p-4 text-indigo-700 font-bold">
                                        <span v-if="item.enrollments && item.enrollments.length > 0">
                                            {{ item.enrollments[item.enrollments.length - 1].classe.nom }}
                                            <span class="text-xs text-gray-400 font-normal ml-1">({{ item.enrollments[item.enrollments.length - 1].statut }})</span>
                                        </span>
                                        <span v-else class="text-red-400 font-normal">Non affecté</span>
                                    </td>
                                    <td class="p-4 text-right space-x-2">
                                        <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900 font-medium transition cursor-pointer">Supprimer</button>
                                    </td>
                                </tr>
                                <tr v-if="students.length === 0"><td colspan="5" class="p-8 text-center text-gray-500">Aucun élève enregistré.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="creating" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-6">Ajouter et Inscrire un Élève</h2>
                
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Identité</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Nom" />
                        <TextInput v-model="form.nom" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.nom" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Prénom" />
                        <TextInput v-model="form.prenom" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.prenom" class="mt-1" />
                    </div>
                </div>
                
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Matricule (Optionnel)" />
                        <TextInput v-model="form.matricule" type="text" class="mt-1 block w-full font-mono" />
                        <InputError :message="form.errors.matricule" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Sexe" />
                        <select v-model="form.sexe" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="M">Masculin</option>
                            <option value="F">Féminin</option>
                        </select>
                    </div>
                </div>

                <hr class="my-6 border-gray-100">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Affectation</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Année Scolaire" />
                        <select v-model="form.school_year_id" class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50 font-medium">
                            <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                        </select>
                        <InputError :message="form.errors.school_year_id" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Classe" />
                        <select v-model="form.classe_id" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option disabled value="">Sélectionner une classe</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nom }}</option>
                        </select>
                        <InputError :message="form.errors.classe_id" class="mt-1" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel value="Statut" />
                    <select v-model="form.statut" class="mt-1 block w-full border-gray-300 rounded-md font-bold text-indigo-700">
                        <option value="Nouveau">Nouveau</option>
                        <option value="Redoublant">Redoublant</option>
                    </select>
                    <InputError :message="form.errors.statut" class="mt-1" />
                </div>

                <div class="mt-8 flex justify-end gap-3 bg-gray-50 p-4 -mx-6 -mb-6 rounded-b">
                    <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="createItem">Créer et Inscrire</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
EOT;
file_put_contents($adminDir . '/Students.vue', $studentsVue);

// We need to ensure StudentEnrollment is in `$fillable`? No, `$fillable` is usually not restricted dynamically if used right, but let's make sure.
$enrollmentModel = __DIR__ . '/app/Models/StudentEnrollment.php';
$content = file_get_contents($enrollmentModel);
if (strpos($content, "'statut'") === false) {
    $content = str_replace(
        "protected \$fillable = ['student_id', 'class_id', 'school_year_id'];",
        "protected \$fillable = ['student_id', 'class_id', 'school_year_id', 'statut'];",
        $content
    );
    file_put_contents($enrollmentModel, $content);
}

echo "Student Update OK.";
