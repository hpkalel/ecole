<?php
$adminDir = __DIR__ . '/resources/js/Pages/Admin';
$controllerPath = __DIR__ . '/app/Http/Controllers/AdminController.php';

// Add storeProf in AdminController
$controllerCode = file_get_contents($controllerPath);

$storeProfLogic = <<<EOT
    public function storeProf(Request \$request)
    {
        \$request->validate([
            'nom' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:4'
        ]);

        User::create([
            'nom' => \$request->nom,
            'username' => \$request->username,
            'password' => \Illuminate\Support\Facades\Hash::make(\$request->password),
            'role' => 'prof',
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Professeur ajouté.');
    }
EOT;

if (strpos($controllerCode, 'storeProf') === false) {
    // Insert before destroyProf
    $controllerCode = str_replace(
        "public function destroyProf(\$id)", 
        $storeProfLogic . "\n\n    public function destroyProf(\$id)", 
        $controllerCode
    );
    file_put_contents($controllerPath, $controllerCode);
}

// 1. Update Profs.vue
$profsVue = <<<EOT
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

defineProps({ profs: Array });

const creating = ref(false);
const form = useForm({ nom: '', username: '', password: '' });

const createItem = () => {
    form.post(route('admin.profs.store'), {
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
    if (confirm('Êtes-vous sûr de vouloir supprimer ce professeur ?')) {
        deleteForm.delete(route('admin.profs.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Gestion des Professeurs" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Gestion des Professeurs</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Liste des Professeurs</h3>
                        <PrimaryButton @click="creating = true" class="transform hover:-translate-y-0.5 transition">+ Ajouter un Professeur</PrimaryButton>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200">Nom Complet</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Nom d'utilisateur</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in profs" :key="item.id" class="hover:bg-indigo-50 transition">
                                    <td class="p-4 font-medium text-gray-900">{{ item.nom }}</td>
                                    <td class="p-4 text-gray-500 font-mono">{{ item.username }}</td>
                                    <td class="p-4 text-right space-x-2">
                                        <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900 font-medium transition cursor-pointer">Supprimer</button>
                                    </td>
                                </tr>
                                <tr v-if="profs.length === 0"><td colspan="3" class="p-8 text-center text-gray-500">Aucun professeur enregistré.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="creating" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Ajouter un Professeur</h2>
                <div class="mt-6">
                    <InputLabel for="nom" value="Nom Complet" />
                    <TextInput id="nom" v-model="form.nom" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.nom" class="mt-2" />
                </div>
                <div class="mt-4">
                    <InputLabel for="username" value="Nom d'utilisateur (pour connexion)" />
                    <TextInput id="username" v-model="form.username" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.username" class="mt-2" />
                </div>
                <div class="mt-4">
                    <InputLabel for="password" value="Mot de passe temporaire" />
                    <TextInput id="password" v-model="form.password" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.password" class="mt-2" />
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

file_put_contents("$adminDir/Profs.vue", $profsVue);

// 2. Update Years.vue
$yearsVue = <<<EOT
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

defineProps({ years: Array });

const creating = ref(false);
const form = useForm({ name: '', start_date: '', end_date: '', is_active: false });

const createItem = () => {
    form.post(route('admin.years.store'), {
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
    if (confirm('Êtes-vous sûr de vouloir supprimer cette année scolaire ?')) {
        deleteForm.delete(route('admin.years.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Années Scolaires" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Années Scolaires</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Liste des Années</h3>
                        <PrimaryButton @click="creating = true" class="transform hover:-translate-y-0.5 transition">+ Ajouter</PrimaryButton>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200">Nom (Ex: 2023-2024)</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Début</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Fin</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in years" :key="item.id" class="hover:bg-indigo-50 transition">
                                    <td class="p-4 font-medium text-gray-900">{{ item.name }}</td>
                                    <td class="p-4 text-gray-500">{{ item.start_date || '-' }}</td>
                                    <td class="p-4 text-gray-500">{{ item.end_date || '-' }}</td>
                                    <td class="p-4 text-right space-x-2">
                                        <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900 font-medium transition cursor-pointer">Supprimer</button>
                                    </td>
                                </tr>
                                <tr v-if="years.length === 0"><td colspan="4" class="p-8 text-center text-gray-500">Aucune année enregistrée.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="creating" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Nouvelle Année Scolaire</h2>
                <div class="mt-6">
                    <InputLabel for="name" value="Nom de l'année (ex: 2023-2024)" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="start" value="Date de début (optionnel)" />
                        <TextInput id="start" v-model="form.start_date" type="date" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="end" value="Date de fin (optionnel)" />
                        <TextInput id="end" v-model="form.end_date" type="date" class="mt-1 block w-full" />
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

file_put_contents("$adminDir/Years.vue", $yearsVue);
echo "Admin Views Updated";
