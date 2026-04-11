<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DeleteConfirmationModal from '@/Components/DeleteConfirmationModal.vue';

const props = defineProps({ enrollments: Array, students: Array, classes: Array, years: Array, activeYearId: Number });

const selectedClasseId = ref(null);
const selectedClasse = computed(() => props.classes.find(c => c.id === selectedClasseId.value));

const filteredEnrollments = computed(() => {
    if (!selectedClasseId.value) return [];
    return props.enrollments.filter(e => 
        e.class_id === selectedClasseId.value && e.school_year_id === props.activeYearId
    ).sort((a, b) => {
        const nomA = (a.student.nom || '').toString().trim().toUpperCase();
        const nomB = (b.student.nom || '').toString().trim().toUpperCase();
        if (nomA !== nomB) return nomA.localeCompare(nomB);
        return (a.student.prenom || '').localeCompare(b.student.prenom || '');
    });
});

// Students without any enrollment in the active year
const unenrolledStudents = computed(() => {
    const enrolledIds = props.enrollments
        .filter(e => e.school_year_id === props.activeYearId)
        .map(e => e.student_id);
    return props.students.filter(s => !enrolledIds.includes(s.id))
        .sort((a, b) => {
            const nomA = (a.nom || '').toString().trim().toUpperCase();
            const nomB = (b.nom || '').toString().trim().toUpperCase();
            if (nomA !== nomB) return nomA.localeCompare(nomB);
            return (a.prenom || '').localeCompare(b.prenom || '');
        });
});

const quickEnroll = (student) => {
    form.reset();
    form.student_id = student.id;
    form.school_year_id = props.activeYearId || '';
    creating.value = true;
};

const stats = computed(() => {
    const list = filteredEnrollments.value;
    return {
        total: list.length,
        girls: list.filter(e => e.student.sexe === 'F').length,
        boys: list.filter(e => e.student.sexe === 'M').length,
        new: list.filter(e => e.statut === 'Nouveau').length,
        repeating: list.filter(e => e.statut === 'Redoublant').length,
    };
});

const creating = ref(false);
const form = useForm({ student_id: '', class_id: '', school_year_id: '' });

const openCreate = () => {
    form.reset();
    form.school_year_id = props.activeYearId || '';
    if (selectedClasseId.value) {
        form.class_id = selectedClasseId.value;
    }
    creating.value = true;
};
const createItem = () => {
    form.post(route('admin.enrollments.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};
const closeModal = () => {
    creating.value = false; transferring.value = false; form.reset(); form.clearErrors(); transferForm.reset(); transferForm.clearErrors();
};

const transferring = ref(false);
const enrollmentToTransfer = ref(null);
const transferForm = useForm({ class_id: '' });

const openTransfer = (enrollment) => {
    enrollmentToTransfer.value = enrollment;
    transferForm.class_id = enrollment.class_id;
    transferring.value = true;
};

const confirmTransfer = () => {
    transferForm.patch(route('admin.enrollments.update', enrollmentToTransfer.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const deleteForm = useForm({});
const confirmingDeletion = ref(false);
const itemToDelete = ref(null);

const deleteItem = (id) => {
    itemToDelete.value = id;
    confirmingDeletion.value = true;
};

const confirmDelete = () => {
    deleteForm.delete(route('admin.enrollments.destroy', itemToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeletion.value = false;
            itemToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Inscriptions Élèves" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Inscriptions dans les Classes</h2>
        </template>
        <div class="py-6">
            <div class="mx-auto max-w-7xl">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex items-center gap-2 sm:gap-6 flex-nowrap overflow-x-auto bg-gray-50 invisible-scrollbar">
                        <button v-if="selectedClasseId" @click="selectedClasseId = null" class="p-2 bg-indigo-50 text-indigo-600 rounded-full hover:bg-indigo-100 transition shadow-sm flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        <h3 class="text-lg sm:text-xl font-bold text-gray-700 tracking-tight whitespace-nowrap flex-shrink-0">
                            {{ selectedClasseId ? selectedClasse.nom : 'Dossiers d\'inscriptions' }}
                        </h3>
                        <PrimaryButton @click="openCreate" class="ml-auto transform hover:-translate-y-0.5 transition whitespace-nowrap flex-shrink-0 px-3 sm:px-4">
                            <span class="hidden sm:inline">{{ selectedClasseId ? 'Inscrire dans cette classe' : '+ Inscrire un élève' }}</span>
                            <span class="sm:hidden">{{ selectedClasseId ? 'Inscrire' : '+ Inscrire' }}</span>
                        </PrimaryButton>
                    </div>
                    <!-- View 1: Class Summary -->
                    <div v-if="!selectedClasseId" class="overflow-x-auto">
                         <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-[0.7rem] uppercase font-black tracking-wider">
                                    <th class="p-6 border-b border-gray-100 italic whitespace-nowrap">Classe</th>
                                    <th class="p-6 border-b border-gray-100 text-center whitespace-nowrap">Effectif Actuel</th>
                                    <th class="p-6 border-b border-gray-100 text-right whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="c in classes" :key="c.id" class="hover:bg-indigo-50 transition-all group cursor-pointer" @click="selectedClasseId = c.id">
                                    <td class="p-6">
                                         <div class="inline-flex h-10 px-4 rounded-xl bg-indigo-100 items-center justify-center text-indigo-700 font-black text-sm shadow-sm group-hover:scale-105 transition-transform tracking-tight whitespace-nowrap">
                                            {{ c.nom }}
                                        </div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <span class="inline-flex items-center px-4 py-1.5 bg-indigo-50 text-indigo-700 text-sm font-black rounded-xl">
                                            {{ c.enrollments_count }} Élèves
                                        </span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <button class="bg-gray-900 text-white text-[0.65rem] font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow-lg hover:bg-gray-800 transition active:scale-95 flex items-center gap-2 float-right ml-auto whitespace-nowrap">
                                            Gérer les dossiers
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                         </table>
                    </div>

                    <!-- Unenrolled Students Section -->
                    <div v-if="!selectedClasseId && unenrolledStudents.length > 0" class="border-t border-dashed border-amber-200 bg-amber-50">
                        <div class="p-4 sm:p-6 flex items-center gap-3">
                            <div class="p-2 bg-amber-100 text-amber-600 rounded-lg flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-amber-800 uppercase tracking-tight">Élèves non inscrits</p>
                                <p class="text-xs font-medium text-amber-600">{{ unenrolledStudents.length }} élève(s) sans affectation pour l'année en cours</p>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-amber-100 text-amber-700 text-[0.7rem] uppercase font-black tracking-wider whitespace-nowrap">
                                        <th class="px-6 py-3 border-b border-amber-200 whitespace-nowrap">Matricule</th>
                                        <th class="px-6 py-3 border-b border-amber-200 whitespace-nowrap">Nom</th>
                                        <th class="px-6 py-3 border-b border-amber-200 whitespace-nowrap">Prénom</th>
                                        <th class="px-6 py-3 border-b border-amber-200 text-center whitespace-nowrap">Sexe</th>
                                        <th class="px-6 py-3 border-b border-amber-200 text-right whitespace-nowrap">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-amber-100">
                                    <tr v-for="s in unenrolledStudents" :key="s.id" class="hover:bg-amber-100 transition group whitespace-nowrap">
                                        <td class="px-6 py-3 text-gray-400 font-mono text-xs">{{ s.matricule || '-' }}</td>
                                        <td class="px-6 py-3 font-black text-gray-800 uppercase tracking-tight text-sm">{{ s.nom }}</td>
                                        <td class="px-6 py-3 text-gray-600 text-sm">{{ s.prenom }}</td>
                                        <td class="px-6 py-3 text-center">
                                            <span :class="s.sexe === 'M' ? 'text-blue-600 bg-blue-50' : 'text-pink-600 bg-pink-50'" class="px-2 py-0.5 rounded text-[0.65rem] font-black uppercase">
                                                {{ s.sexe }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <button @click="quickEnroll(s)" class="text-amber-700 hover:text-amber-900 font-black text-[0.65rem] uppercase tracking-widest bg-amber-200 hover:bg-amber-300 px-2.5 py-1 rounded-lg transition-all hover:scale-105">
                                                + Inscrire
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- View 2: Detailed Enrollments -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-[0.7rem] uppercase font-black tracking-wider whitespace-nowrap">
                                    <th class="p-4 font-semibold border-b border-gray-200 uppercase tracking-wider whitespace-nowrap text-xs">Nom</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 uppercase tracking-wider whitespace-nowrap text-xs">Prénom</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 uppercase tracking-wider whitespace-nowrap text-xs">Classe</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 uppercase tracking-wider whitespace-nowrap text-xs">Année Scolaire</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 text-right uppercase tracking-wider whitespace-nowrap text-xs">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in filteredEnrollments" :key="item.id" class="hover:bg-indigo-50 transition whitespace-nowrap">
                                    <td class="p-4 font-black text-gray-900 uppercase tracking-tight text-sm whitespace-nowrap">{{ item.student.nom }}</td>
                                    <td class="p-4 text-gray-600 font-medium text-sm whitespace-nowrap">{{ item.student.prenom }}</td>
                                    <td class="p-4 text-indigo-700 font-black text-sm tracking-tighter whitespace-nowrap">{{ item.classe.nom }}</td>
                                    <td class="p-4 text-gray-500 font-mono text-sm whitespace-nowrap underline decoration-indigo-100 decoration-2 underline-offset-4">{{ item.school_year.name }}</td>
                                    <td class="p-4 text-right whitespace-nowrap">
                                        <div class="flex justify-end items-center gap-2">
                                            <button @click="openTransfer(item)" 
                                                    class="text-indigo-600 hover:text-indigo-800 font-black text-[0.65rem] uppercase tracking-widest bg-indigo-50 px-2.5 py-1 rounded-lg transition-all hover:scale-105">
                                                Transfert
                                            </button>
                                            <button @click="deleteItem(item.id)" 
                                                    class="text-rose-600 hover:text-rose-800 font-black text-[0.65rem] uppercase tracking-widest bg-rose-50 px-2.5 py-1 rounded-lg transition-all hover:scale-105">
                                                Désinscrire
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredEnrollments.length === 0"><td colspan="5" class="p-12 text-center text-gray-300 font-bold italic border-b border-gray-50">Aucune inscription trouvée pour cette classe.</td></tr>
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
        <Modal :show="creating" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-black text-gray-800 uppercase tracking-tight mb-6">Inscrire un élève</h3>
                <form @submit.prevent="createItem">
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="student_id" value="Élève" />
                            <select id="student_id" v-model="form.student_id" 
                                    class="mt-1 block w-full border-gray-100 bg-gray-50 rounded-xl font-bold text-gray-700 focus:ring-0" required>
                                <option disabled value="">Sélectionner un élève...</option>
                                <option v-for="s in students" :key="s.id" :value="s.id">{{ s.nom }} {{ s.prenom }}</option>
                            </select>
                            <InputError :message="form.errors.student_id" class="mt-1" />
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Classe" />
                                <select v-model="form.class_id" :disabled="selectedClasseId" class="mt-1 block w-full border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed">
                                    <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nom }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Année Scolaire" />
                                <select v-model="form.school_year_id" class="mt-1 block w-full border-gray-300 rounded-md">
                                    <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3">
                        <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">Inscrire maintenant</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal Transfert -->
        <Modal :show="transferring" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-3 bg-indigo-100 text-indigo-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-gray-800 uppercase tracking-tight">Transférer l'élève</h3>
                        <p class="text-xs font-bold text-gray-400">Modifier la classe d'affectation</p>
                    </div>
                </div>

                <div v-if="enrollmentToTransfer" class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <p class="text-[0.6rem] font-black text-gray-400 uppercase tracking-widest mb-1">Élève à transférer</p>
                    <p class="text-sm font-black text-gray-900 uppercase">{{ enrollmentToTransfer.student.nom }} {{ enrollmentToTransfer.student.prenom }}</p>
                    <p class="text-xs font-bold text-indigo-600">Classe actuelle : {{ enrollmentToTransfer.classe.nom }}</p>
                </div>

                <form @submit.prevent="confirmTransfer">
                    <div>
                        <InputLabel value="Nouvelle Classe" />
                        <select v-model="transferForm.class_id" class="mt-1 block w-full border-gray-100 bg-gray-50 rounded-xl font-black text-indigo-600 focus:ring-0" required>
                            <option disabled value="">Sélectionner la nouvelle classe...</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id" :disabled="c.id === enrollmentToTransfer?.class_id">
                                {{ c.nom }} {{ c.id === enrollmentToTransfer?.class_id ? '(Déjà inscrit ici)' : '' }}
                            </option>
                        </select>
                        <InputError :message="transferForm.errors.class_id" class="mt-1" />
                    </div>

                    <div class="mt-8 flex justify-end gap-3">
                        <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
                        <PrimaryButton class="bg-indigo-600 hover:bg-indigo-700" :class="{ 'opacity-25': transferForm.processing }" :disabled="transferForm.processing">
                            Confirmer le transfert
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <DeleteConfirmationModal 
            :show="confirmingDeletion" 
            title="Supprimer l'Inscription"
            message="Voulez-vous vraiment désinscrire cet élève de cette classe ? Cette action peut avoir un impact sur ses notes."
            :processing="deleteForm.processing"
            @close="confirmingDeletion = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>