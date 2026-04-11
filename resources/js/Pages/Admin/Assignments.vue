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

const props = defineProps({ assignments: Array, profs: Array, classes: Array, subjects: Array, years: Array });

const creating = ref(false);
const editing = ref(false);

const form = useForm({ 
    id: null,
    prof_id: '', 
    subject_id: '', 
    class_id: '', 
    school_year_id: props.years.find(y => y.is_active)?.id || '',
    coefficient: 1 
});

const openCreate = () => {
    form.reset();
    form.id = null;
    form.class_id = '';
    creating.value = true;
};

const openEdit = (assignment) => {
    form.id = assignment.id;
    form.prof_id = assignment.prof_id;
    form.subject_id = assignment.subject_id;
    form.school_year_id = assignment.school_year_id;
    form.coefficient = assignment.coefficient;
    editing.value = true;
};

const saveItem = () => {
    if (form.id) {
        form.patch(route('admin.assignments.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.assignments.store'), {
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
    deleteForm.delete(route('admin.assignments.destroy', itemToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeletion.value = false;
            itemToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Attribution des Matières" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-black leading-tight text-gray-800 uppercase tracking-tight">Attribution des Matières</h2>
                <PrimaryButton @click="openCreate" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Nouvelle Attribution
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
                                    <th class="p-4 font-semibold border-b border-gray-200">Professeur</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Matière & Coeff</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Classe</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in assignments" :key="item.id" class="hover:bg-indigo-50 transition-colors group">
                                    <td class="p-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-900">{{ item.prof.nom }}</div>
                                        <div class="text-[0.65rem] text-gray-400 font-semibold uppercase tracking-widest">{{ item.school_year.name }}</div>
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        <div class="font-bold text-indigo-700">{{ item.subject.nom }}</div>
                                        <div class="text-[0.65rem] text-indigo-400 font-semibold uppercase">Coeff: {{ item.coefficient }}</div>
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 bg-gray-100 text-gray-600 rounded text-xs font-bold">
                                            {{ item.classe.nom }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-right flex justify-end gap-2">
                                        <button @click="openEdit(item)" class="p-2 text-indigo-600 hover:bg-indigo-100 rounded-lg transition" title="Transférer / Modifier">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                        </button>
                                        <button @click="deleteItem(item.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Supprimer">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="assignments.length === 0"><td colspan="4" class="p-12 text-center text-gray-300 font-bold italic">Aucune attribution pour l'instant.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Modal :show="creating || editing" @close="closeModal">
            <div class="p-8">
                <h2 class="text-2xl font-black text-gray-900 mb-8 border-b-4 border-indigo-600 inline-block">{{ form.id ? 'Modifier' : 'Attribuer' }} une Matière</h2>
                
                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <InputLabel value="Professeur" />
                            <select v-model="form.prof_id" class="mt-1 block w-full border-gray-100 bg-gray-50 rounded-xl font-bold">
                                <option disabled value="">Sélectionner...</option>
                                <option v-for="p in profs" :key="p.id" :value="p.id">{{ p.nom }}</option>
                            </select>
                            <InputError :message="form.errors.prof_id" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Matière" />
                            <select v-model="form.subject_id" :disabled="form.id" class="mt-1 block w-full border-gray-100 bg-gray-50 rounded-xl font-bold disabled:opacity-50">
                                <option disabled value="">Sélectionner...</option>
                                <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.nom }}</option>
                            </select>
                            <InputError :message="form.errors.subject_id" class="mt-1" />
                        </div>
                    </div>

                    <div v-if="!form.id">
                        <InputLabel value="Classe" />
                        <select v-model="form.class_id" class="mt-1 block w-full border-gray-100 bg-gray-50 rounded-xl font-bold">
                            <option disabled value="">Sélectionner...</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nom }}</option>
                        </select>
                        <InputError :message="form.errors.class_id" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <InputLabel value="Coefficient" />
                            <TextInput v-model="form.coefficient" type="number" min="1" max="10" class="mt-1 block w-full" />
                            <InputError :message="form.errors.coefficient" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Année Scolaire" />
                            <select v-model="form.school_year_id" :disabled="form.id" class="mt-1 block w-full border-gray-100 bg-gray-50 rounded-xl font-bold disabled:opacity-50">
                                <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex justify-end gap-3 pt-6 border-t border-gray-50">
                    <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="saveItem">
                        {{ form.id ? 'Valider le transfert' : 'Confirmer les attributions' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmationModal 
            :show="confirmingDeletion" 
            title="Supprimer l'Attribution"
            message="Voulez-vous vraiment supprimer cette attribution ? Le professeur n'aura plus accès à cette matière pour cette classe."
            :processing="deleteForm.processing"
            @close="confirmingDeletion = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>