<?php
$adminDir = __DIR__ . '/resources/js/Pages/Admin';

// CLASSES
$classesVue = <<<EOT
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

defineProps({
    classes: Array,
});

const creating = ref(false);
const form = useForm({
    nom: '',
});

const createItem = () => {
    form.post(route('admin.classes.store'), {
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
    if (confirm('Êtes-vous sûr de vouloir supprimer cette classe ?')) {
        deleteForm.delete(route('admin.classes.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Gestion des Classes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Gestion des Classes</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Liste des Classes</h3>
                        <PrimaryButton @click="creating = true" class="transform hover:-translate-y-0.5 transition">+ Ajouter</PrimaryButton>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200">ID</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Nom de la Classe</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="cls in classes" :key="cls.id" class="hover:bg-indigo-50 transition">
                                    <td class="p-4 text-gray-500">#{{ cls.id }}</td>
                                    <td class="p-4 font-medium text-gray-900">{{ cls.nom }}</td>
                                    <td class="p-4 text-right space-x-2">
                                        <button @click="deleteItem(cls.id)" class="text-red-600 hover:text-red-900 font-medium transition cursor-pointer">Supprimer</button>
                                    </td>
                                </tr>
                                <tr v-if="classes.length === 0">
                                    <td colspan="3" class="p-8 text-center text-gray-500">Aucune classe enregistrée.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <Modal :show="creating" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Ajouter une nouvelle classe</h2>
                <div class="mt-6">
                    <InputLabel for="nom" value="Nom de la classe (ex: 6ème A)" />
                    <TextInput id="nom" v-model="form.nom" type="text" class="mt-1 block w-full" @keyup.enter="createItem" />
                    <InputError :message="form.errors.nom" class="mt-2" />
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

// SUBJECTS
$subjectsVue = <<<EOT
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

defineProps({ subjects: Array });

const creating = ref(false);
const form = useForm({ nom: '', coefficient: 1 });

const createItem = () => {
    form.post(route('admin.subjects.store'), {
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
    if (confirm('Êtes-vous sûr de vouloir supprimer cette matière ?')) {
        deleteForm.delete(route('admin.subjects.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Gestion des Matières" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Gestion des Matières</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Liste des Matières</h3>
                        <PrimaryButton @click="creating = true" class="transform hover:-translate-y-0.5 transition">+ Ajouter</PrimaryButton>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200">Nom</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Coefficient</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in subjects" :key="item.id" class="hover:bg-indigo-50 transition">
                                    <td class="p-4 font-medium text-gray-900">{{ item.nom }}</td>
                                    <td class="p-4 text-gray-500">{{ item.coefficient }}</td>
                                    <td class="p-4 text-right space-x-2">
                                        <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900 font-medium transition cursor-pointer">Supprimer</button>
                                    </td>
                                </tr>
                                <tr v-if="subjects.length === 0"><td colspan="3" class="p-8 text-center text-gray-500">Aucune matière.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="creating" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Ajouter une Matière</h2>
                <div class="mt-6">
                    <InputLabel for="nom" value="Nom de la matière" />
                    <TextInput id="nom" v-model="form.nom" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.nom" class="mt-2" />
                </div>
                <div class="mt-6">
                    <InputLabel for="coef" value="Coefficient" />
                    <TextInput id="coef" v-model="form.coefficient" type="number" class="mt-1 block w-full" />
                    <InputError :message="form.errors.coefficient" class="mt-2" />
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

// STUDENTS
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

defineProps({ students: Array });

const creating = ref(false);
const form = useForm({ matricule: '', nom: '', prenom: '', sexe: 'M' });

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
    if (confirm('Êtes-vous sûr de vouloir supprimer cet élève ?')) {
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
                        <PrimaryButton @click="creating = true" class="transform hover:-translate-y-0.5 transition">+ Ajouter</PrimaryButton>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200">Matricule</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Nom Complet</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Sexe</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in students" :key="item.id" class="hover:bg-indigo-50 transition">
                                    <td class="p-4 text-gray-500 font-mono">{{ item.matricule }}</td>
                                    <td class="p-4 font-medium text-gray-900">{{ item.nom }} {{ item.prenom }}</td>
                                    <td class="p-4 text-gray-600"><span :class="item.sexe === 'M' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'" class="px-2 py-1 rounded-full text-xs font-bold">{{ item.sexe }}</span></td>
                                    <td class="p-4 text-right space-x-2">
                                        <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900 font-medium transition cursor-pointer">Supprimer</button>
                                    </td>
                                </tr>
                                <tr v-if="students.length === 0"><td colspan="4" class="p-8 text-center text-gray-500">Aucun élève enregistré.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="creating" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Ajouter un Élève</h2>
                <div class="mt-6">
                    <InputLabel for="mat" value="Matricule (optionnel)" />
                    <TextInput id="mat" v-model="form.matricule" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.matricule" class="mt-2" />
                </div>
                <div class="mt-4">
                    <InputLabel for="nom" value="Nom" />
                    <TextInput id="nom" v-model="form.nom" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.nom" class="mt-2" />
                </div>
                <div class="mt-4">
                    <InputLabel for="prenom" value="Prénom" />
                    <TextInput id="prenom" v-model="form.prenom" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.prenom" class="mt-2" />
                </div>
                <div class="mt-4">
                    <InputLabel for="sexe" value="Sexe" />
                    <select id="sexe" v-model="form.sexe" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="M">Masculin (M)</option>
                        <option value="F">Féminin (F)</option>
                    </select>
                    <InputError :message="form.errors.sexe" class="mt-2" />
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

file_put_contents("$adminDir/Classes.vue", $classesVue);
file_put_contents("$adminDir/Subjects.vue", $subjectsVue);
file_put_contents("$adminDir/Students.vue", $studentsVue);

echo "CRUD Vue Interfaces integrated successfully.";
