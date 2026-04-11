<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import DeleteConfirmationModal from '@/Components/DeleteConfirmationModal.vue';

const props = defineProps({ subjects: Array });

const creating = ref(false);
const editing = ref(false);

const form = useForm({ id: null, nom: '' });

const openCreate = () => {
    form.reset();
    form.id = null;
    creating.value = true;
};

const openEdit = (subject) => {
    form.id = subject.id;
    form.nom = subject.nom;
    editing.value = true;
};

const saveItem = () => {
    if (form.id) {
        form.patch(route('admin.subjects.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.subjects.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    creating.value = false;
    editing.value = false;
    form.reset();
    form.clearErrors();
};

const deleteForm = useForm({});
const confirmingDeletion = ref(false);
const itemToDelete = ref(null);

const deleteItem = (id) => {
    itemToDelete.value = id;
    confirmingDeletion.value = true;
};

const confirmDelete = () => {
    deleteForm.delete(route('admin.subjects.destroy', itemToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeletion.value = false;
            itemToDelete.value = null;
        },
    });
};

watch(() => form.nom, (val) => {
    if (val) form.nom = val.toUpperCase();
});
</script>

<template>
    <Head title="Gestion des Matières" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-black leading-tight text-gray-800 uppercase tracking-tight">Gestion des Matières</h2>
                <PrimaryButton @click="openCreate">+ Ajouter une Matière</PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-[0.7rem] uppercase font-black tracking-wider whitespace-nowrap">
                                    <th class="p-4 border-b border-gray-200">Matière</th>
                                    <th class="p-4 border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in subjects" :key="item.id" class="hover:bg-indigo-50 transition-colors group">
                                    <td class="p-4">
                                        <div class="font-bold text-gray-900">{{ item.nom }}</div>
                                    </td>
                                    <td class="p-4 text-right flex justify-end gap-2">
                                        <button @click="openEdit(item)" class="p-2 text-indigo-600 hover:bg-indigo-100 rounded-lg transition" title="Renommer">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="deleteItem(item.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Supprimer">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="subjects.length === 0"><td colspan="2" class="p-12 text-center text-gray-300 font-bold italic">Aucune matière enregistrée.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="creating || editing" @close="closeModal">
            <div class="p-8">
                <h2 class="text-2xl font-black text-gray-900 mb-8 border-b-4 border-indigo-600 inline-block">{{ form.id ? 'Modifier' : 'Créer' }} la Matière</h2>
                <div class="space-y-6">
                    <div>
                        <InputLabel for="nom" value="Désignation" />
                        <TextInput id="nom" v-model="form.nom" type="text" class="mt-1 block w-full uppercase" placeholder="Ex: MATHEMATIQUES" />
                        <InputError :message="form.errors.nom" class="mt-1" />
                    </div>
                </div>
                <div class="mt-10 flex justify-end gap-3 pt-6 border-t border-gray-50">
                    <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="saveItem">Enregistrer</PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmationModal 
            :show="confirmingDeletion" 
            title="Supprimer la Matière"
            message="Voulez-vous vraiment supprimer cette matière ? Cela retirera la matière de toutes les classes où elle est enseignée."
            :processing="deleteForm.processing"
            @close="confirmingDeletion = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
EOT;