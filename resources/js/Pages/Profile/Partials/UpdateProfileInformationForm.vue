<script setup>
import { ref, computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = computed(() => usePage().props.auth.user);
const isAdmin = computed(() => user.value.role === 'admin');
const editing = ref(false);

const form = useForm({
    nom: user.nom,
    username: user.username,
    grade: user.grade || '',
    statut: user.statut || '',
    corps: user.corps || '',
});

const openModal = () => {
    form.nom = user.value.nom;
    form.username = user.value.username;
    form.grade = user.value.grade || '';
    form.statut = user.value.statut || '';
    form.corps = user.value.corps || '';
    editing.value = true;
};

const saveProfile = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">
                Informations Personnelles
            </h2>
            <p class="mt-1 text-sm text-gray-500 font-medium italic">
                Vos informations administratives officielles.
            </p>
        </header>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nom Complet (Read Only) -->
            <div class="group">
                <InputLabel value="Nom Complet" class="text-xs uppercase font-black text-gray-400 mb-1" />
                <div class="mt-1 p-3.5 bg-gray-50 border border-gray-100 rounded-xl text-gray-900 font-bold flex items-center justify-between">
                    <span>{{ user.nom }}</span>
                    <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                </div>
            </div>

            <!-- Username (Read Only) -->
            <div>
                <InputLabel value="Nom d'utilisateur" class="text-xs uppercase font-black text-gray-400 mb-1" />
                <div class="mt-1 p-3.5 bg-gray-50 border border-gray-100 rounded-xl text-gray-600 font-mono text-sm tracking-widest border-dashed">
                    {{ user.username }}
                </div>
            </div>

            <!-- Grade -->
            <div>
                <InputLabel value="Grade" class="text-xs uppercase font-black text-gray-400 mb-1" />
                <div class="mt-1 p-3 bg-indigo-50 border border-indigo-100 rounded-xl text-indigo-700 font-black text-sm uppercase tracking-widest">
                    {{ user.grade || 'Non spécifié' }}
                </div>
            </div>

            <!-- Statut -->
            <div>
                <InputLabel value="Statut" class="text-xs uppercase font-black text-gray-400 mb-1" />
                <div class="mt-1 p-3 bg-emerald-50 border border-emerald-100 rounded-xl text-emerald-700 font-black text-sm uppercase tracking-widest">
                    {{ user.statut || 'Non spécifié' }}
                </div>
            </div>

            <!-- Corps -->
            <div class="md:col-span-2">
                <InputLabel value="Corps" class="text-xs uppercase font-black text-gray-400 mb-1" />
                <div class="mt-1 p-3 bg-amber-50 border border-amber-100 rounded-xl text-amber-700 font-black text-sm uppercase tracking-widest">
                    {{ user.corps || 'Non spécifié' }}
                </div>
            </div>

            <!-- Edit Button at the bottom (Admin Only) -->
            <div v-if="isAdmin" class="md:col-span-2 flex justify-start pt-2">
                <button 
                    @click="openModal"
                    class="flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-black text-xs uppercase tracking-widest transition-all shadow-md shadow-indigo-100 group"
                >
                    <svg class="w-4 h-4 group-hover:scale-125 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Modifier mes informations
                </button>
            </div>
        </div>

        <div v-if="!isAdmin" class="mt-6 p-4 bg-rose-50 rounded-2xl border border-rose-100 flex items-center gap-3">
             <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
             <p class="text-xs text-rose-700 font-bold italic">Pour toute modification de ces informations, veuillez contacter la direction de l'établissement.</p>
        </div>

        <!-- Update Modal (Admin Only) -->
        <Modal :show="editing" @close="editing = false" maxWidth="2xl">
            <div class="p-8">
                <div class="flex items-center gap-3 mb-8">
                    <div class="p-3 bg-indigo-100 text-indigo-700 rounded-2xl">
                         <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">Modifier Profil</h2>
                        <p class="text-sm text-gray-500 font-bold uppercase tracking-widest">Informations Administratives</p>
                    </div>
                </div>

                <form @submit.prevent="saveProfile" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="modal_nom" value="Nom Complet" />
                            <TextInput id="modal_nom" type="text" class="mt-1 block w-full" v-model="form.nom" required />
                            <InputError class="mt-2" :message="form.errors.nom" />
                        </div>
                        <div>
                            <InputLabel for="modal_username" value="Identifiant" />
                            <TextInput id="modal_username" type="text" class="mt-1 block w-full font-mono" v-model="form.username" required />
                            <InputError class="mt-2" :message="form.errors.username" />
                        </div>
                        <div>
                            <InputLabel for="modal_grade" value="Grade" />
                            <TextInput id="modal_grade" type="text" class="mt-1 block w-full" v-model="form.grade" />
                            <InputError class="mt-2" :message="form.errors.grade" />
                        </div>
                        <div>
                            <InputLabel for="modal_statut" value="Statut" />
                            <TextInput id="modal_statut" type="text" class="mt-1 block w-full" v-model="form.statut" />
                            <InputError class="mt-2" :message="form.errors.statut" />
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel for="modal_corps" value="Corps" />
                            <TextInput id="modal_corps" type="text" class="mt-1 block w-full" v-model="form.corps" />
                            <InputError class="mt-2" :message="form.errors.corps" />
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-gray-100">
                        <SecondaryButton @click="editing = false">Annuler</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Enregistrer les changements
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </section>
</template>
