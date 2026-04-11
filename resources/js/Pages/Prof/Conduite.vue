<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    classe: Object,
    students: Array,
    periode: String
});

// Helper to check for invalid grades (> 20 or < 0)
const isInvalid = (val) => val !== null && val !== '' && (parseFloat(val) > 20 || parseFloat(val) < 0);
const hasLocalErrors = computed(() => Object.values(form.grades).some(isInvalid));

const form = useForm({
    classe_id: props.classe.id,
    periode: props.periode,
    grades: props.students.reduce((acc, student) => {
        acc[student.id] = student.current_behavior_grade ? student.current_behavior_grade.valeur : '';
        return acc;
    }, {})
});

const submit = () => {
    if (hasLocalErrors.value) return;
    form.post(route('prof.conduite.store'), {
        preserveScroll: true,
    });
};

const changePeriode = (newPeriode) => {
    window.location.href = route('prof.conduite', { 
        classe_id: props.classe.id, 
        periode: newPeriode 
    });
};
</script>

<template>
    <Head title="Saisie de la Conduite" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center gap-4">
                <h2 class="text-xl font-black leading-tight text-gray-800 tracking-tight">
                    Saisie Conduite : <span class="text-indigo-600">{{ classe.nom }}</span>
                </h2>
                <div class="flex bg-gray-100 p-1 rounded-xl shadow-inner">
                    <button 
                        @click="changePeriode('Semestre 1')"
                        :class="[periode === 'Semestre 1' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-400 hover:text-gray-600']"
                        class="px-4 py-1.5 text-[0.65rem] font-black uppercase tracking-widest rounded-lg transition"
                    >
                        S1
                    </button>
                    <button 
                        @click="changePeriode('Semestre 2')"
                        :class="[periode === 'Semestre 2' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-400 hover:text-gray-600']"
                        class="px-4 py-1.5 text-[0.65rem] font-black uppercase tracking-widest rounded-lg transition ml-1"
                    >
                        S2
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12 relative overflow-hidden">
            <!-- Fixed Toast Notification for Local Errors -->
            <Transition
                enter-active-class="transform transition ease-out duration-300"
                enter-from-class="translate-y-[-20px] opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transform transition ease-in duration-200"
                leave-to-class="translate-y-[-20px] opacity-0"
            >
                <div v-if="hasLocalErrors" class="fixed top-24 left-1/2 -translate-x-1/2 z-[110] w-full max-w-sm px-4">
                    <div class="bg-rose-600 text-white p-4 rounded-2xl shadow-2xl flex items-center gap-3 border-2 border-rose-400">
                        <div class="bg-rose-700 rounded-lg p-1">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <p class="font-black text-xs uppercase tracking-tight">Erreur : Les notes doivent être comprises entre 0 et 20.</p>
                    </div>
                </div>
            </Transition>

            <div class="mx-auto max-w-4xl ">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" :class="{ 'ring-2 ring-red-100': hasLocalErrors }">
                    <!-- Header Section Style Grades.vue -->
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center" :class="hasLocalErrors ? 'bg-red-50' : 'bg-indigo-50/50'">
                        <div>
                            <h3 class="text-lg font-black uppercase tracking-tight" :class="hasLocalErrors ? 'text-red-900' : 'text-indigo-900'">Note de Conduite</h3>
                            <p class="text-[0.65rem] font-black uppercase tracking-widest mt-1" :class="hasLocalErrors ? 'text-red-400' : 'text-indigo-400'">{{ periode }}</p>
                        </div>
                        <PrimaryButton @click="submit" :disabled="form.processing || hasLocalErrors" class="shadow-lg" :class="hasLocalErrors ? 'bg-gray-400 shadow-none border-gray-300' : 'shadow-indigo-200'">
                            <span v-if="hasLocalErrors">Notes Invalides</span>
                            <span v-else>Enregistrer <span class="hidden sm:inline">&nbsp;la Conduite</span></span>
                        </PrimaryButton>
                    </div>

                    <!-- Table View -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50 text-gray-400 text-[0.65rem] uppercase font-black tracking-widest uppercase">
                                    <th class="p-6 border-b border-gray-100 italic">Élève</th>
                                    <th class="p-6 border-b border-gray-100 text-center w-48">Note / 20</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="student in students" :key="student.id" class="hover:bg-indigo-50/30 transition-colors group">
                                    <td class="p-6">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-black uppercase tracking-tight group-hover:text-indigo-700 transition-colors" :class="{ 'text-red-900': isInvalid(form.grades[student.id]) }">{{ student.nom }}</span>
                                            <span class="text-xs font-bold text-gray-400 group-hover:text-indigo-400 transition-colors capitalize">{{ student.prenom }}</span>
                                        </div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <TextInput 
                                            type="number" 
                                            step="0.5" 
                                            min="0" 
                                            max="20"
                                            v-model="form.grades[student.id]" 
                                            class="w-28 text-center font-black rounded-xl focus:ring-indigo-600 focus:border-indigo-600"
                                            :is-error="isInvalid(form.grades[student.id])"
                                        />
                                    </td>
                                </tr>
                                <tr v-if="students.length === 0">
                                    <td colspan="2" class="p-12 text-center text-gray-300 font-bold italic">Aucun élève trouvé dans cette classe.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Actions -->
                    <div class="p-6 bg-gray-50/50 border-t border-gray-100 flex justify-between items-center">
                        <Link :href="route('prof.dashboard')" class="text-[0.65rem] font-black text-gray-400 uppercase tracking-widest hover:text-gray-600 transition-colors">
                            &larr; Revenir au tableau de bord
                        </Link>
                        <div class="text-[0.65rem] font-black text-indigo-300 uppercase tracking-widest">
                            {{ students.length }} élèves au total
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
