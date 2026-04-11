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
import DeleteConfirmationModal from '@/Components/DeleteConfirmationModal.vue';

defineProps({
    classes: Array,
});

const creating = ref(false);
const editing = ref(false);
const form = useForm({ nom: '' });
const editForm = useForm({ id: null, nom: '' });

const principalForm = useForm({
    classe_id: null,
    prof_id: null
});

const setPrincipal = (classeId, profId) => {
    if (!profId) return;
    principalForm.classe_id = classeId;
    principalForm.prof_id = profId;
    principalForm.post(route('admin.classes.set-principal'), {
        preserveScroll: true,
    });
};

const createItem = () => {
    form.post(route('admin.classes.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const openEdit = (cls) => {
    editForm.id = cls.id;
    editForm.nom = cls.nom;
    editing.value = true;
};

const updateItem = () => {
    editForm.patch(route('admin.classes.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    creating.value = false;
    editing.value = false;
    form.reset();
    form.clearErrors();
    editForm.reset();
    editForm.clearErrors();
};

const deleteForm = useForm({});
const confirmingDeletion = ref(false);
const itemToDelete = ref(null);

const deleteItem = (id) => {
    itemToDelete.value = id;
    confirmingDeletion.value = true;
};

const confirmDelete = () => {
    deleteForm.delete(route('admin.classes.destroy', itemToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeletion.value = false;
            itemToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Gestion des Classes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Gestion des Classes</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Liste des Classes</h3>
                        <PrimaryButton @click="creating = true" class="transform hover:-translate-y-0.5 transition">+ Ajouter</PrimaryButton>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-xs uppercase font-black tracking-wider whitespace-nowrap">
                                    <th class="p-4 font-semibold border-b border-gray-200">Nom de la Classe</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Prof. Principal</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="cls in classes" :key="cls.id" class="hover:bg-indigo-50 transition">
                                    <td class="p-4 font-medium text-gray-900 whitespace-nowrap">{{ cls.nom }}</td>
                                    <td class="p-4">
                                        <select 
                                            @change="setPrincipal(cls.id, $event.target.value)"
                                            class="text-[0.7rem] border-gray-300 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 block w-full min-w-[160px] bg-white shadow-sm font-bold"
                                        >
                                            <option value="">Non défini</option>
                                            <option 
                                                v-for="prof in cls.available_profs" 
                                                :key="prof.id" 
                                                :value="prof.id"
                                                :selected="cls.active_principal && cls.active_principal.id === prof.id"
                                            >
                                                {{ prof.nom }} {{ prof.prenom }}
                                            </option>
                                        </select>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex justify-end items-center gap-2">
                                            <div class="flex items-center gap-1.5 mr-2">
                                                <a :href="route('admin.classes.export', { id: cls.id, periode: 'Semestre 1' })" 
                                                   class="inline-flex items-center h-8 px-2.5 text-[0.7rem] font-black uppercase tracking-widest bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-lg hover:bg-emerald-600 hover:text-white transition shadow-sm" 
                                                   title="Exporter Semestre 1">
                                                    S1
                                                </a>
                                                <a :href="route('admin.classes.export', { id: cls.id, periode: 'Semestre 2' })" 
                                                   class="inline-flex items-center h-8 px-2.5 text-[0.7rem] font-black uppercase tracking-widest bg-blue-50 text-blue-700 border border-blue-100 rounded-lg hover:bg-blue-600 hover:text-white transition shadow-sm" 
                                                   title="Exporter Semestre 2">
                                                    S2
                                                </a>
                                            </div>
                                            <button @click.stop="openEdit(cls)" class="p-2 text-indigo-600 hover:bg-indigo-100 rounded-lg transition" title="Modifier">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button @click.stop="deleteItem(cls.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Supprimer">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
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

        <!-- Edit Modal -->
        <Modal :show="editing" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-black text-gray-800 uppercase tracking-tight mb-6">Modifier la Classe</h2>
                <div class="mt-2">
                    <InputLabel for="edit_nom" value="Nom de la classe" />
                    <TextInput id="edit_nom" v-model="editForm.nom" type="text" class="mt-1 block w-full" @keyup.enter="updateItem" />
                    <InputError :message="editForm.errors.nom" class="mt-2" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': editForm.processing }" :disabled="editForm.processing" @click="updateItem">Enregistrer</PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmationModal 
            :show="confirmingDeletion" 
            title="Supprimer la Classe"
            message="Voulez-vous vraiment supprimer cette classe ? Cette action entraînera la désinscription de tous les élèves concernés."
            :processing="deleteForm.processing"
            @close="confirmingDeletion = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>