<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import DeleteConfirmationModal from '@/Components/DeleteConfirmationModal.vue';

const props = defineProps({ evaluations: Array, assignment: Object });

const creating = ref(false);
const editing = ref(false);

const formatDate = (dateString) => {
    if (!dateString) return 'Non définie';
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }).format(d);
};

const form = useForm({ 
    id: null,
    nom: '', 
    type: 'interrogation', 
    periode: 'Semestre 1', 
    assignment_id: props.assignment.id, 
    date: '' 
});

const openCreate = () => {
    form.reset();
    form.id = null;
    form.assignment_id = props.assignment.id;
    creating.value = true;
};

const openEdit = (evaluation) => {
    form.id = evaluation.id;
    form.nom = evaluation.nom;
    form.type = evaluation.type;
    form.periode = evaluation.periode;
    form.assignment_id = props.assignment.id;
    form.date = evaluation.date || '';
    editing.value = true;
};

const saveItem = () => {
    if (form.id) {
        form.patch(route('prof.evaluations.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('prof.evaluations.store'), {
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
    deleteForm.delete(route('prof.evaluations.destroy', itemToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeletion.value = false;
            itemToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Mes Évaluations" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-black leading-tight text-gray-800 tracking-tight">
                    <span class="text-indigo-600">{{ assignment.classe.nom }}</span> 
                    <span class="text-gray-400 text-sm ml-2">({{ assignment.subject.nom }})</span>
                </h2>
                <PrimaryButton @click="openCreate" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Programmer une Évaluation
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-xs uppercase font-black tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200">Évaluation & Matière</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Date et Période</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Classe</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in evaluations" :key="item.id" class="hover:bg-indigo-50 transition-colors group">
                                    <td class="p-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-900">{{ item.nom }}</div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[0.65rem] text-gray-400 font-semibold uppercase tracking-widest">{{ assignment.subject.nom }}</span>
                                            <span class="px-1.5 py-0.5 bg-gray-100 text-gray-400 rounded text-[0.6rem] font-bold uppercase">{{ item.type }}</span>
                                        </div>
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-700 text-sm">{{ formatDate(item.date) }}</div>
                                        <div class="text-[0.6rem] text-indigo-400 font-bold uppercase">{{ item.periode }}</div>
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-xs font-bold">
                                            {{ assignment.classe.nom }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-right flex justify-end gap-2">
                                        <Link :href="route('prof.grades', {evaluation_id: item.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-xl transition-all" title="Saisir les notes">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                            </svg>
                                        </Link>
                                        <button @click="openEdit(item)" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all" title="Modifier">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                        </button>
                                        <button @click="deleteItem(item.id)" class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition-all" title="Supprimer">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="evaluations.length === 0"><td colspan="4" class="p-12 text-center text-gray-300 font-bold italic">Aucune évaluation programmée.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="creating || editing" @close="closeModal">
            <div class="p-8">
                <h2 class="text-2xl font-black text-gray-900 mb-8 border-b-4 border-indigo-600 inline-block">{{ form.id ? 'Modifier' : 'Programmer' }} l'Évaluation</h2>
                
                <div class="space-y-6">
                    <div>
                        <InputLabel value="Titre de l'épreuve (ex: Devoir de Maison n°1)" />
                        <TextInput v-model="form.nom" type="text" class="mt-1 block w-full" placeholder="Entrez le titre..." />
                        <InputError :message="form.errors.nom" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <InputLabel value="Type d'évaluation" />
                            <select v-model="form.type" class="mt-1 block w-full border-gray-100 bg-gray-50 rounded-xl font-bold">
                                <option value="interrogation">Interrogation</option>
                                <option value="devoir">Devoir de Synthèse</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Période" />
                            <select v-model="form.periode" class="mt-1 block w-full border-gray-100 bg-gray-50 rounded-xl font-bold">
                                <option value="Semestre 1">1er Semestre</option>
                                <option value="Semestre 2">2ème Semestre</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <InputLabel value="Classe concernée" />
                            <div class="mt-1 block w-full px-3 py-2 border border-gray-100 bg-gray-50 rounded-xl font-bold text-gray-500 cursor-not-allowed">
                                {{ assignment.classe.nom }} - {{ assignment.subject.nom }}
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Date de l'épreuve" />
                            <TextInput v-model="form.date" type="date" class="mt-1 block w-full" />
                            <InputError :message="form.errors.date" class="mt-1" />
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex justify-end gap-3 pt-6 border-t border-gray-50">
                    <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="saveItem">
                        {{ form.id ? 'Mettre à jour' : 'Confirmer la programmation' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmationModal 
            :show="confirmingDeletion" 
            title="Supprimer l'Évaluation"
            message="Voulez-vous vraiment supprimer cette évaluation ? Toutes les notes saisies seront définitivement perdues."
            :processing="deleteForm.processing"
            @close="confirmingDeletion = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
EOT;