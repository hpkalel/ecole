<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import DeleteConfirmationModal from '@/Components/DeleteConfirmationModal.vue';
const props = defineProps({ students: Array, classes: Array, years: Array, activeYearId: Number });

const selectedClasseId = ref(null);
const selectedClasse = computed(() => props.classes.find(c => c.id === selectedClasseId.value));

const filteredStudents = computed(() => {
    if (!selectedClasseId.value) return [];
    const students = props.students.filter(s => 
        s.enrollments && s.enrollments.some(e => 
            e.class_id === selectedClasseId.value && e.school_year_id === props.activeYearId
        )
    );
    
    return students.sort((a, b) => {
        const nomA = (a.nom || '').toString().trim().toUpperCase();
        const nomB = (b.nom || '').toString().trim().toUpperCase();
        const prenomA = (a.prenom || '').toString().trim().toUpperCase();
        const prenomB = (b.prenom || '').toString().trim().toUpperCase();
        
        if (nomA < nomB) return -1;
        if (nomA > nomB) return 1;
        if (prenomA < prenomB) return -1;
        if (prenomA > prenomB) return 1;
        return 0;
    });
});

const stats = computed(() => {
    const list = filteredStudents.value;
    return {
        total: list.length,
        girls: list.filter(s => s.sexe === 'F').length,
        boys: list.filter(s => s.sexe === 'M').length,
        new: list.filter(s => s.statut === 'Nouveau').length,
        repeating: list.filter(s => s.statut === 'Redoublant').length,
    };
});

const creating = ref(false);
const editing = ref(false);
const importing = ref(false);

const form = useForm({ 
    id: null,
    matricule: '', 
    nom: '', 
    prenom: '', 
    sexe: 'M',
    class_id: '',
    school_year_id: props.activeYearId || '',
    statut: 'Nouveau'
});

const importForm = useForm({
    class_id: '',
    school_year_id: props.activeYearId || '',
    import_file: null
});

const openCreate = () => {
    form.reset();
    form.id = null;
    form.school_year_id = props.activeYearId || '';
    if (selectedClasseId.value) {
        form.class_id = selectedClasseId.value;
    }
    creating.value = true;
};

const openEdit = (student) => {
    const enrollment = student.enrollments && student.enrollments.length > 0 ? student.enrollments[student.enrollments.length - 1] : null;
    form.id = student.id;
    form.nom = student.nom;
    form.prenom = student.prenom;
    form.sexe = student.sexe;
    form.matricule = student.matricule || '';
    form.class_id = enrollment ? enrollment.class_id : '';
    form.school_year_id = enrollment ? enrollment.school_year_id : (props.activeYearId || '');
    form.statut = enrollment ? enrollment.statut : 'Nouveau';
    editing.value = true;
};

const saveItem = () => {
    if (form.id) {
        form.patch(route('admin.students.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.students.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const openImport = () => {
    importForm.reset();
    importForm.school_year_id = props.activeYearId || '';
    if (selectedClasseId.value) {
        importForm.class_id = selectedClasseId.value;
    }
    importing.value = true;
};

const runImport = () => {
    importForm.post(route('admin.students.import'), {
        preserveScroll: true,
        onSuccess: () => {
            importing.value = false;
            importForm.reset();
        }
    });
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
    deleteForm.delete(route('admin.students.destroy', itemToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeletion.value = false;
            itemToDelete.value = null;
        },
    });
};

// Automatic formatting
watch(() => form.nom, (val) => {
    if (val) form.nom = val.toUpperCase();
});

watch(() => form.prenom, (val) => {
    if (val) {
        form.prenom = val.replace(/\b\w/g, l => l.toUpperCase());
    }
});
</script>

<template>
    <Head title="Gestion des Élèves" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2 sm:gap-6 flex-nowrap overflow-x-auto pb-1 invisible-scrollbar">
                <button v-if="selectedClasseId" @click="selectedClasseId = null" class="p-2 bg-indigo-50 text-indigo-600 rounded-full hover:bg-indigo-100 transition shadow-sm flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <h2 class="text-lg sm:text-xl font-black leading-tight text-gray-800 tracking-tight whitespace-nowrap flex-shrink-0">
                    {{ selectedClasseId ? selectedClasse.nom : 'GESTION DES ÉLÈVES' }}
                </h2>
                <div class="flex items-center gap-2 sm:gap-4 ml-auto">
                    <SecondaryButton @click="openImport" class="flex items-center gap-2 px-3 sm:px-4">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                        <span class="hidden sm:inline text-indigo-600 font-bold">Import CSV / Excel</span>
                        <span class="sm:hidden text-indigo-600 font-bold">Import</span>
                    </SecondaryButton>
                    <PrimaryButton @click="openCreate" class="flex items-center gap-2 px-3 sm:px-4 whitespace-nowrap">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        <span class="hidden sm:inline">{{ selectedClasseId ? 'Ajouter à cette classe' : 'Inscrire un élève' }}</span>
                        <span class="sm:hidden">{{ selectedClasseId ? 'Ajouter' : 'Inscrire' }}</span>
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- View 1: Class Summary -->
                    <div v-if="!selectedClasseId" class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-[0.7rem] uppercase font-black tracking-wider">
                                    <th class="p-6 border-b border-gray-100 whitespace-nowrap">Classe</th>
                                    <th class="p-6 border-b border-gray-100 text-center whitespace-nowrap">Effectif Total</th>
                                    <th class="p-6 border-b border-gray-100 text-center whitespace-nowrap">Nouveaux</th>
                                    <th class="p-6 border-b border-gray-100 text-center whitespace-nowrap">Redoublants</th>
                                    <th class="p-6 border-b border-gray-100 text-right whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="c in classes" :key="c.id" class="hover:bg-indigo-50/30 transition-all group cursor-pointer" @click="selectedClasseId = c.id">
                                    <td class="p-6">
                                        <div class="inline-flex h-10 px-4 rounded-xl bg-indigo-100 items-center justify-center text-indigo-700 font-black text-sm shadow-sm group-hover:scale-105 transition-transform tracking-tight whitespace-nowrap">
                                            {{ c.nom }}
                                        </div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <span class="inline-flex items-center px-3 py-1 bg-indigo-50 text-indigo-700 text-sm font-black rounded-lg">
                                            {{ c.total_count }}
                                        </span>
                                    </td>
                                    <td class="p-6 text-center text-green-600 font-bold text-sm">
                                        {{ c.nouveaux_count }}
                                    </td>
                                    <td class="p-6 text-center text-orange-600 font-bold text-sm">
                                        {{ c.redoublants_count }}
                                    </td>
                                    <td class="p-6 text-right">
                                        <button class="bg-gray-900 text-white text-[0.65rem] font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow-lg hover:bg-gray-800 transition active:scale-95 flex items-center gap-2 float-right ml-auto whitespace-nowrap">
                                            Voir la liste
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- View 2: Detailed Students List -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-[0.7rem] uppercase font-black tracking-wider whitespace-nowrap">
                                    <th class="p-4 border-b border-gray-200">Matricule</th>
                                    <th class="p-4 border-b border-gray-200">Nom</th>
                                    <th class="p-4 border-b border-gray-200">Prénom</th>
                                    <th class="p-4 border-b border-gray-200 text-center">Sexe</th>
                                    <th class="p-4 border-b border-gray-200">Classe</th>
                                    <th class="p-4 border-b border-gray-200">Statut</th>
                                    <th class="p-4 border-b border-gray-200 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in filteredStudents" :key="item.id" class="hover:bg-indigo-50 transition-colors group whitespace-nowrap">
                                    <td class="p-4 text-gray-400 font-mono text-xs">{{ item.matricule || '-' }}</td>
                                    <td class="p-4 font-black text-gray-900 uppercase tracking-tight text-sm whitespace-nowrap">{{ item.nom }}</td>
                                    <td class="p-4 text-gray-600 font-medium text-sm whitespace-nowrap">{{ item.prenom }}</td>
                                    <td class="p-4 text-center">
                                        <span :class="[item.sexe === 'M' ? 'text-blue-600 bg-blue-50' : 'text-pink-600 bg-pink-50', 'px-2.5 py-1 rounded-lg text-[0.65rem] font-black uppercase tracking-widest']">
                                            {{ item.sexe === 'M' ? 'M' : 'F' }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <div v-if="item.enrollments && item.enrollments.length > 0">
                                            <span class="text-indigo-700 font-black text-sm tracking-tighter">{{ item.enrollments[item.enrollments.length - 1].classe.nom }}</span>
                                        </div>
                                        <span v-else class="text-gray-300 text-xs italic">Non inscrit</span>
                                    </td>
                                    <td class="p-4">
                                        <div v-if="item.enrollments && item.enrollments.length > 0">
                                           <span class="text-[0.65rem] font-black uppercase tracking-widest px-2 py-1 rounded-md" 
                                                 :class="item.enrollments[item.enrollments.length - 1].statut === 'Nouveau' ? 'bg-green-50 text-green-600' : 'bg-orange-50 text-orange-600'">
                                               {{ item.enrollments[item.enrollments.length - 1].statut }}
                                           </span>
                                        </div>
                                        <span v-else class="text-gray-300 text-xs italic">-</span>
                                    </td>
                                    <td class="p-4 text-right flex justify-end items-center gap-2">
                                        <a :href="route('admin.bulletin', {student_id: item.id})" 
                                           class="text-blue-600 hover:text-blue-800 font-black text-[0.65rem] uppercase tracking-widest bg-blue-50 px-2.5 py-1 rounded-lg transition-all hover:scale-105">
                                            Bulletin
                                        </a>
                                        <button @click="openEdit(item)" class="p-2 text-indigo-600 hover:bg-indigo-100 rounded-lg transition" title="Modifier">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button @click="deleteItem(item.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Supprimer">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredStudents.length === 0"><td colspan="7" class="p-12 text-center text-gray-300 font-bold italic">Aucun élève trouvé dans cette classe.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Statistics Summary Table (Only when class selected) -->
                    <div v-if="selectedClasseId" class="p-6 bg-gray-50 border-t border-gray-100">
                        <div class="max-w-2xl">
                            <h4 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                Statistiques de la classe
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                                    <p class="text-[0.6rem] font-black text-gray-400 uppercase tracking-tighter">Total</p>
                                    <p class="text-xl font-black text-gray-900">{{ stats.total }}</p>
                                </div>
                                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                                    <p class="text-[0.6rem] font-black text-rose-400 uppercase tracking-tighter">Filles</p>
                                    <p class="text-xl font-black text-rose-600">{{ stats.girls }}</p>
                                </div>
                                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                                    <p class="text-[0.6rem] font-black text-blue-400 uppercase tracking-tighter">Garçons</p>
                                    <p class="text-xl font-black text-blue-600">{{ stats.boys }}</p>
                                </div>
                                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                                    <p class="text-[0.6rem] font-black text-emerald-400 uppercase tracking-tighter">Nouveaux</p>
                                    <p class="text-xl font-black text-emerald-600">{{ stats.new }}</p>
                                </div>
                                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                                    <p class="text-[0.6rem] font-black text-amber-400 uppercase tracking-tighter">Redoublants</p>
                                    <p class="text-xl font-black text-amber-600">{{ stats.repeating }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Modal :show="creating || editing" @close="closeModal">
            <div class="p-8">
                <h2 class="text-2xl font-black text-gray-900 mb-8 border-b-4 border-indigo-600 inline-block">{{ form.id ? 'Modifier' : 'Ajouter' }} l'Élève</h2>
                
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <InputLabel value="Nom" />
                        <TextInput v-model="form.nom" type="text" class="mt-1 block w-full uppercase" />
                        <InputError :message="form.errors.nom" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Prénom" />
                        <TextInput v-model="form.prenom" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.prenom" class="mt-1" />
                    </div>
                </div>
                
                <div class="mt-6 grid grid-cols-2 gap-6">
                    <div>
                        <InputLabel value="Matricule (Optionnel)" />
                        <TextInput v-model="form.matricule" type="text" class="mt-1 block w-full font-mono text-sm" />
                        <InputError :message="form.errors.matricule" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Sexe" />
                        <select v-model="form.sexe" class="mt-1 block w-full border-gray-100 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                            <option value="M">Masculin</option>
                            <option value="F">Féminin</option>
                        </select>
                    </div>
                </div>

                <div class="mt-10 mb-6 flex items-center gap-4">
                    <div class="h-px flex-1 bg-gray-100"></div>
                    <span class="text-[0.65rem] font-black uppercase text-gray-400 tracking-widest">Scolarité (Année Active)</span>
                    <div class="h-px flex-1 bg-gray-100"></div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <InputLabel value="Classe" />
                        <select v-model="form.class_id" :disabled="selectedClasseId" class="mt-1 block w-full border-gray-100 bg-gray-50 rounded-xl font-bold disabled:opacity-50 disabled:cursor-not-allowed">
                            <option disabled value="">Choisir...</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nom }}</option>
                        </select>
                        <InputError :message="form.errors.class_id" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Statut" />
                        <select v-model="form.statut" class="mt-1 block w-full border-gray-100 bg-gray-50 rounded-xl font-black text-indigo-600">
                            <option value="Nouveau">Nouveau</option>
                            <option value="Redoublant">Redoublant</option>
                        </select>
                    </div>
                </div>

                <div class="mt-10 flex justify-end gap-3 pt-6 border-t border-gray-50">
                    <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="saveItem">
                        {{ form.id ? 'Mettre à jour' : 'Enregistrer et Inscrire' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Import Modal -->
        <Modal :show="importing" @close="importing = false">
            <div class="p-8">
               <h2 class="text-2xl font-black text-gray-900 mb-4 border-b-4 border-indigo-600 inline-block">Importation Massive (CSV / Excel)</h2>
               <p class="text-sm text-gray-500 mb-8 leading-relaxed">
                   Sélectionnez d'abord la classe de destination pour ces élèves, puis téléchargez votre fichier CSV ou Excel.<br>
                   <span class="font-bold text-gray-700 underline">Format attendu :</span> Matricule ; Nom ; Prénom ; Sexe (M/F) ; Statut
               </p>

               <div class="space-y-6">
                    <div>
                        <InputLabel value="Classe de destination" />
                        <select v-model="importForm.class_id" :disabled="selectedClasseId" class="mt-1 block w-full border-gray-100 bg-gray-50 rounded-xl font-black text-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed">
                            <option disabled value="">Sélectionner la classe...</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nom }}</option>
                        </select>
                        <InputError :message="importForm.errors.class_id" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Fichier CSV ou Excel (.xlsx)" />
                        <input type="file" @input="importForm.import_file = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-black file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer" accept=".csv, .xlsx, .xls" />
                        <InputError :message="importForm.errors.import_file" class="mt-1" />
                    </div>
               </div>

               <div class="mt-10 flex justify-end gap-3 pt-6 border-t border-gray-50">
                    <SecondaryButton @click="importing = false">Annuler</SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': importForm.processing }" :disabled="importForm.processing" @click="runImport">Lancer l'Importation</PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmationModal 
            :show="confirmingDeletion" 
            title="Supprimer l'Élève"
            message="Voulez-vous vraiment supprimer cet élève ? Toutes ses notes et son historique d'inscription seront définitivement effacés."
            :processing="deleteForm.processing"
            @close="confirmingDeletion = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
EOT;