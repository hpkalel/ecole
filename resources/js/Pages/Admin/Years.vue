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

defineProps({ years: Array });

const creating = ref(false);
const editing = ref(false);
const confirmingDeletion = ref(false);
const itemToDelete = ref(null);

const form = useForm({ id: null, name: '', start_date: '', end_date: '', is_active: false });

const yearError = ref('');
const dateError = ref('');

const startInputRef = ref(null);
const endInputRef = ref(null);

const validateDates = () => {
    dateError.value = '';

    // Check validity of native inputs if possible through refs
    if (startInputRef.value?.input?.validity?.badInput) {
        dateError.value = "La date de début saisie n'est pas une date valide au calendrier.";
        return false;
    }
    if (endInputRef.value?.input?.validity?.badInput) {
        dateError.value = "La date de fin saisie n'est pas une date valide au calendrier.";
        return false;
    }

    if (!form.name || form.name.length !== 9) return true;
    
    const parts = form.name.split('-');
    if (parts.length !== 2) return true;

    const startYear = parts[0];
    const endYear = parts[1];

    if (form.start_date) {
        const dStartYear = form.start_date.split('-')[0];
        if (dStartYear !== startYear) {
            dateError.value = "La date de début doit être comprise dans l'année " + startYear + ".";
            return false;
        }
    }

    if (form.end_date) {
        const dEndYear = form.end_date.split('-')[0];
        if (dEndYear !== endYear) {
            dateError.value = "La date de fin doit être comprise dans l'année " + endYear + ".";
            return false;
        }
    }

    if (form.start_date && form.end_date) {
        if (new Date(form.start_date) >= new Date(form.end_date)) {
            dateError.value = "La date de fin doit être postérieure à la date de début.";
            return false;
        }
    }

    return true;
};

const formatDate = (dateString) => {
    if (!dateString) return null;
    let d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(d);
};

watch(() => [form.start_date, form.end_date], () => {
    validateDates();
}, { deep: true });

watch(() => form.name, (newVal, oldVal) => {
    if (!newVal) {
        yearError.value = '';
        return;
    }
    
    let isDeleting = oldVal && newVal.length < oldVal.length;
    let value = newVal.replace(/[^0-9]/g, '');
    
    if (value.length === 4 && !isDeleting && (!oldVal || oldVal.replace(/[^0-9]/g, '').length < 4)) {
        const startYear = parseInt(value);
        const endYear = startYear + 1;
        value = `${startYear}-${endYear}`;
        
        // Pré-remplissage automatique supprimé à la demande de l'utilisateur.
    } else if (value.length >= 4) {
        if (value.length === 4 && isDeleting) {
            // Laisse sans le tiret si l'utilisateur l'efface
        } else {
            value = value.substring(0, 4) + '-' + value.substring(4, 8);
        }
    }
    
    if (value !== newVal) {
        form.name = value;
        return;
    }

    yearError.value = '';
    if (form.name.length === 9) {
        const parts = form.name.split('-');
        if (parts.length === 2) {
            const startYear = parseInt(parts[0]);
            const endYear = parseInt(parts[1]);
            if (endYear !== startYear + 1) {
                yearError.value = "L'année de fin doit suivre directement l'année de début (ex: 2023-2024).";
            }
        }
    }
    
    validateDates();
});

const saveItem = () => {
    if (!form.name || form.name.length < 9) {
        yearError.value = "Veuillez entrer une année scolaire complète (ex: 2023-2024).";
        return;
    }
    if (yearError.value) {
        return;
    }
    
    if (!validateDates()) {
        return;
    }

    if (form.id) {
        form.patch(route('admin.years.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.years.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};
const openEdit = (item) => {
    form.id = item.id;
    form.name = item.name;
    form.start_date = item.start_date ? item.start_date.split('T')[0] : '';
    form.end_date = item.end_date ? item.end_date.split('T')[0] : '';
    editing.value = true;
};
const closeModal = () => {
    creating.value = false;
    editing.value = false;
    form.reset();
    form.clearErrors();
    yearError.value = '';
    dateError.value = '';
};

const deleteForm = useForm({});
const deleteItem = (id) => {
    itemToDelete.value = id;
    confirmingDeletion.value = true;
};

const confirmDelete = () => {
    deleteForm.delete(route('admin.years.destroy', itemToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeletion.value = false;
            itemToDelete.value = null;
        },
    });
};

const activateForm = useForm({});
const activateItem = (id) => {
    activateForm.post(route('admin.years.activate', id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Années Scolaires" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 tracking-tight">Gestion des Années Scolaires</h2>
        </template>
        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl">
                <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                    <div class="p-6 sm:p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                        <div>
                            <h3 class="text-xl font-black text-gray-900 uppercase tracking-tighter">Sessions Scolaires</h3>
                            <p class="text-sm text-gray-500 font-medium">Définissez l'année de travail active pour l'ensemble du système.</p>
                        </div>
                        <PrimaryButton @click="creating = true" class="shadow-lg shadow-indigo-200">+ Nouvelle Année</PrimaryButton>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50 text-gray-600 text-xs uppercase font-black tracking-wider">
                                    <th class="p-6 border-b border-gray-100">Année</th>
                                    <th class="p-6 border-b border-gray-100 text-center">Statut</th>
                                    <th class="p-6 border-b border-gray-100 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="item in years" :key="item.id" :class="[item.is_active ? 'bg-indigo-50/30' : 'hover:bg-gray-50']" class="transition-colors group">
                                    <td class="p-6">
                                        <div class="flex flex-col">
                                            <span class="font-black text-gray-900 text-lg tracking-tight whitespace-nowrap">{{ item.name }}</span>
                                            <span class="text-xs text-gray-400 font-bold uppercase tracking-widest whitespace-nowrap">{{ item.start_date ? 'Du ' + formatDate(item.start_date) : 'Début non défini' }} — {{ formatDate(item.end_date) || '?' }}</span>
                                        </div>
                                    </td>
                                    <td class="p-6">
                                        <div class="flex justify-center">
                                            <span v-if="item.is_active" class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[0.65rem] font-black uppercase tracking-widest border border-emerald-200 ring-4 ring-emerald-50">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Active
                                            </span>
                                            <span v-else class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-400 rounded-full text-[0.65rem] font-black uppercase tracking-widest border border-gray-200">
                                                Inactive
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-6 text-right">
                                        <div class="flex justify-end items-center gap-3">
                                            <button v-if="!item.is_active" @click="activateItem(item.id)" 
                                                class="px-4 py-2 bg-white text-indigo-600 border border-indigo-200 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all shadow-sm">
                                                Activer
                                            </button>
                                            <button v-if="!item.is_active" @click="openEdit(item)" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all" title="Modifier">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button v-if="!item.is_active" @click="deleteItem(item.id)" class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition-all" title="Supprimer">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="years.length === 0"><td colspan="4" class="p-8 text-center text-gray-500">Aucune année enregistrée.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="creating || editing" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">{{ form.id ? 'Modifier l\'Année Scolaire' : 'Nouvelle Année Scolaire' }}</h2>
                <div class="mt-6">
                    <InputLabel for="name" value="Nom de l'année (ex: 2023-2024)" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" maxlength="9" placeholder="2023-2024" />
                    <InputError :message="yearError || form.errors.name" class="mt-2" />
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="start" value="Date de début (optionnel)" />
                        <TextInput ref="startInputRef" id="start" v-model="form.start_date" type="date" class="mt-1 block w-full" :isError="!!(dateError && dateError.includes('début'))" />
                    </div>
                    <div>
                        <InputLabel for="end" value="Date de fin (optionnel)" />
                        <TextInput ref="endInputRef" id="end" v-model="form.end_date" type="date" class="mt-1 block w-full" :isError="!!(dateError && (dateError.includes('fin') || dateError.includes('postérieure')))" />
                    </div>
                </div>
                <InputError v-if="dateError || form.errors.start_date || form.errors.end_date" :message="dateError || form.errors.start_date || form.errors.end_date" class="mt-2" />
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
                    <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="saveItem">
                        {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmationModal 
            :show="confirmingDeletion" 
            title="Supprimer l'Année Scolaire"
            message="Voulez-vous vraiment supprimer cette année ? Toutes les données associées pourraient être affectées."
            :processing="deleteForm.processing"
            @close="confirmingDeletion = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>