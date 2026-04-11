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
import PasswordChecklist from '@/Components/PasswordChecklist.vue';
import DeleteConfirmationModal from '@/Components/DeleteConfirmationModal.vue';

const props = defineProps({ profs: Array });

const creating = ref(false);
const editing = ref(false);

const form = useForm({ 
    id: null,
    nom: '', 
    username: '', 
    password: '',
    password_confirmation: '',
    grade: '',
    statut: 'AME',
    corps: 'EPA'
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const openCreate = () => {
    form.reset();
    form.id = null;
    creating.value = true;
};

const openEdit = (prof) => {
    form.id = prof.id;
    form.nom = prof.nom;
    form.username = prof.username;
    form.password = ''; // Keep empty unless changing
    form.grade = prof.grade || '';
    form.statut = prof.statut || '';
    form.corps = prof.corps || '';
    editing.value = true;
};

const saveItem = () => {
    if (form.id) {
        form.patch(route('admin.profs.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.profs.store'), {
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
    deleteForm.delete(route('admin.profs.destroy', itemToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeletion.value = false;
            itemToDelete.value = null;
        },
    });
};

watch(() => form.nom, (val) => {
    if (val) {
        const parts = val.split(' ');
        if (parts.length > 1) {
            const nom = parts[0].toUpperCase();
            const rest = parts.slice(1).map(p => p.charAt(0).toUpperCase() + p.slice(1).toLowerCase()).join(' ');
            form.nom = nom + ' ' + rest;
        }
    }
});
</script>

<template>
    <Head title="Gestion des Professeurs" />
    <AuthenticatedLayout>
        <template #header>
             <div class="flex justify-between items-center">
                <h2 class="text-xl font-black leading-tight text-gray-800 uppercase tracking-tight">Gestion des Professeurs</h2>
                <PrimaryButton @click="openCreate" class="flex items-center gap-2 shadow-lg shadow-indigo-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Ajouter un Professeur
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl">
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                    <div class="p-8 border-b border-gray-50 bg-gray-50/50">
                        <h3 class="text-xl font-black text-gray-900 uppercase tracking-tighter">Corps Enseignant</h3>
                        <p class="text-sm text-gray-500 font-medium">Gérez les comptes et les informations administratives des professeurs.</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50 text-gray-600 text-xs uppercase font-black tracking-wider">
                                    <th class="p-6 border-b border-gray-100 whitespace-nowrap">Nom</th>
                                    <th class="p-6 border-b border-gray-100 whitespace-nowrap">Statut / Titre</th>
                                    <th class="p-6 border-b border-gray-100 whitespace-nowrap">Grade</th>
                                    <th class="p-6 border-b border-gray-100 whitespace-nowrap">Corps</th>
                                    <th class="p-6 border-b border-gray-100 whitespace-nowrap">Identifiant</th>
                                    <th class="p-6 border-b border-gray-100 text-right whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="item in profs" :key="item.id" class="hover:bg-indigo-50/30 transition-colors group">
                                    <td class="p-6 whitespace-nowrap">
                                        <div class="flex items-center gap-3 flex-nowrap">
                                            <div class="h-10 w-10 flex-shrink-0 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-700 font-black text-lg shadow-sm group-hover:scale-110 transition-transform">
                                                {{ item.nom.charAt(0) }}
                                            </div>
                                            <span class="font-black text-gray-900 text-sm tracking-tight whitespace-nowrap">{{ item.nom }}</span>
                                        </div>
                                    </td>
                                    <td class="p-6 whitespace-nowrap">
                                        <span class="text-xs font-black text-indigo-600 uppercase tracking-widest bg-indigo-50 px-2.5 py-1 rounded-lg">{{ item.statut || '-' }}</span>
                                    </td>
                                    <td class="p-6 whitespace-nowrap">
                                        <span class="text-sm font-bold text-gray-700 uppercase">{{ item.grade || '-' }}</span>
                                    </td>
                                    <td class="p-6 whitespace-nowrap">
                                        <span class="text-sm font-bold text-gray-500 uppercase">{{ item.corps || '-' }}</span>
                                    </td>
                                    <td class="p-6 whitespace-nowrap">
                                        <code class="text-xs bg-gray-100 px-2.5 py-1 rounded-lg font-mono text-gray-600 border border-gray-200">{{ item.username }}</code>
                                    </td>
                                    <td class="p-6 text-right whitespace-nowrap">
                                        <div class="flex justify-end gap-2">
                                             <button @click="openEdit(item)" class="p-2 text-indigo-600 hover:bg-indigo-100 rounded-xl transition-all" title="Modifier">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button @click="deleteItem(item.id)" class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition-all" title="Supprimer">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="profs.length === 0"><td colspan="6" class="p-12 text-center text-gray-500 font-bold italic">Aucun professeur enregistré.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Modal :show="creating || editing" @close="closeModal">
            <div class="p-8">
                <h2 class="text-2xl font-black text-gray-900 mb-8 border-b-4 border-indigo-600 inline-block">
                    {{ form.id ? 'Modifier' : 'Ajouter' }} un Professeur
                </h2>
                
                <div class="space-y-6">
                    <!-- Bannière d'erreurs globale -->
                    <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 bg-rose-50 rounded-2xl border border-rose-100">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="h-6 w-6 bg-rose-100 rounded-full flex items-center justify-center text-rose-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span class="text-rose-800 font-black text-sm uppercase tracking-tight">Erreur de validation détectée</span>
                        </div>
                        <ul class="list-disc list-inside text-xs text-rose-600 font-bold space-y-1">
                            <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                        </ul>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="nom" value="Nom Complet" />
                                <TextInput id="nom" v-model="form.nom" type="text" class="mt-1 block w-full" placeholder="Ex: Jean Dupont" />
                                <InputError :message="form.errors.nom" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="username" value="Nom d'utilisateur" />
                                <TextInput id="username" v-model="form.username" type="text" class="mt-1 block w-full" placeholder="ex: J. Dupont" />
                                <InputError :message="form.errors.username" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <InputLabel value="Grade" />
                                <TextInput v-model="form.grade" type="text" class="mt-1 block w-full font-bold" placeholder="ex: A1-1" />
                                <InputError :message="form.errors.grade" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Statut" />
                                <select 
                                    v-model="form.statut" 
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-bold"
                                >
                                    <option value="AME">AME</option>
                                    <option value="ACDPE">ACDPE</option>
                                    <option value="FE">FE</option>
                                </select>
                                <InputError :message="form.errors.statut" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Corps" />
                                <select 
                                    v-model="form.corps" 
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-bold"
                                >
                                    <option value="EPA">EPA</option>
                                    <option value="EPC">EPC</option>
                                    <option value="PA">PA</option>
                                    <option value="PC">PC</option>
                                </select>
                                <InputError :message="form.errors.corps" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="password" :value="form.id ? 'Modifier le Mot de Passe (laisser vide pour ne pas changer)' : 'Mot de passe temporaire'" />
                            <div class="relative group">
                                <TextInput
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="mt-1 block w-full pr-12 transition-all duration-300 focus:ring-4 focus:ring-indigo-100"
                                    v-model="form.password"
                                    autocomplete="new-password"
                                    :placeholder="form.id ? '••••••••' : 'Entrez un mot de passe...'"
                                />
                                <button 
                                    type="button" 
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-indigo-600 transition-colors focus:outline-none"
                                >
                                    <svg v-if="!showPassword" class="w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg v-else class="w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            
                            <Transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 translate-y-[-10px]"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition ease-in duration-150"
                                leave-to-class="opacity-0 translate-y-[-10px]"
                            >
                                <PasswordChecklist v-if="form.password" :password="form.password" />
                            </Transition>
                            <InputError :message="form.errors.password" class="mt-1" />
                        </div>

                        <div v-if="!form.id || form.password">
                            <InputLabel for="password_confirmation" value="Confirmez le mot de passe" />
                            <div class="relative group">
                                <TextInput
                                    id="password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    class="mt-1 block w-full pr-12 transition-all duration-300 focus:ring-4 focus:ring-indigo-100"
                                    v-model="form.password_confirmation"
                                    autocomplete="new-password"
                                    :placeholder="form.id ? '••••••••' : 'Confirmez le mot de passe...'"
                                />
                                <button 
                                    type="button" 
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-indigo-600 transition-colors focus:outline-none"
                                >
                                    <svg v-if="!showConfirmPassword" class="w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg v-else class="w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            
                            <Transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 translate-y-[-10px]"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition ease-in duration-150"
                                leave-to-class="opacity-0 translate-y-[-10px]"
                            >
                                <div v-if="form.password && form.password_confirmation" class="mt-3 flex items-center gap-3 px-4 py-2 bg-gray-50/30 rounded-xl border border-gray-50">
                                    <div 
                                        class="h-5 w-5 rounded-full flex items-center justify-center border-2 transition-all duration-300"
                                        :class="form.password === form.password_confirmation ? 'bg-green-100 border-green-500 text-green-600' : 'bg-rose-100 border-rose-500 text-rose-600'"
                                    >
                                        <svg v-if="form.password === form.password_confirmation" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <svg v-else class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                    <span 
                                        class="text-sm font-black uppercase tracking-tight transition-colors"
                                        :class="form.password === form.password_confirmation ? 'text-green-700' : 'text-rose-700'"
                                    >
                                        {{ form.password === form.password_confirmation ? 'Mots de passe identiques' : 'Mots de passe différents' }}
                                    </span>
                                </div>
                            </Transition>
                            <InputError :message="form.errors.password_confirmation" class="mt-1" />
                        </div>
                    </div>

                    <div class="mt-10 flex justify-end gap-3 pt-6 border-t border-gray-50">
                        <SecondaryButton type="button" @click="closeModal">Annuler</SecondaryButton>
                        <PrimaryButton 
                            class="shadow-lg shadow-indigo-100" 
                            :class="{ 'opacity-25 pointer-events-none cursor-not-allowed': form.processing }" 
                            :disabled="form.processing" 
                            @click="saveItem"
                        >
                            {{ form.id ? 'Mettre à jour' : 'Enregistrer le Professeur' }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>

        <DeleteConfirmationModal 
            :show="confirmingDeletion" 
            title="Supprimer le Professeur"
            message="Voulez-vous vraiment supprimer ce professeur ? Son compte sera désactivé et il n'aura plus accès au système."
            :processing="deleteForm.processing"
            @close="confirmingDeletion = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>