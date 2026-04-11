<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({ evaluation: Object, students: Array, existingGrades: Object });

// Helper to check for invalid notes (> 20)
const isInvalid = (val) => val !== null && val !== '' && (parseFloat(val) > 20 || parseFloat(val) < 0);
const hasLocalErrors = computed(() => Object.values(form.notes).some(isInvalid));

// Initialize form dynamically
const form = useForm({
    evaluation_id: props.evaluation.id,
    notes: {},
    comments: {}
});

onMounted(() => {
    props.students.forEach(student => {
        if (props.existingGrades[student.id]) {
            form.notes[student.id] = props.existingGrades[student.id].valeur;
            form.comments[student.id] = props.existingGrades[student.id].appreciation || '';
        } else {
            form.notes[student.id] = '';
            form.comments[student.id] = '';
        }
    });
});

const saveGrades = () => {
    if (hasLocalErrors.value) return;
    form.post(route('prof.grades.store'), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Saisie des Notes" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Saisie des Notes : {{ evaluation.nom }}
                </h2>
                <Link :href="route('prof.evaluations')" class="text-indigo-600 hover:text-indigo-900 font-medium">&larr; Retour</Link>
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

            <div class="mx-auto max-w-7xl">
                <div class="bg-white rounded-xl shadow-md overflow-hidden" :class="{ 'ring-2 ring-red-100': hasLocalErrors }">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center" :class="hasLocalErrors ? 'bg-red-50' : 'bg-indigo-50'">
                        <div>
                            <h3 class="text-lg font-bold" :class="hasLocalErrors ? 'text-red-900' : 'text-indigo-900'">{{ evaluation.assignment.classe.nom }} - {{ evaluation.assignment.subject.nom }}</h3>
                            <p class="text-sm mt-1 capitalize" :class="hasLocalErrors ? 'text-red-700' : 'text-indigo-700'">
                                <span class="hidden sm:inline">Type : </span>{{ evaluation.type }} 
                                | 
                                <span class="hidden sm:inline">Période : </span>{{ evaluation.periode }}
                            </p>
                        </div>
                        <PrimaryButton @click="saveGrades" :disabled="form.processing || hasLocalErrors" :class="{ 'opacity-50 cursor-not-allowed bg-gray-400 shadow-none': hasLocalErrors }">
                            <span v-if="hasLocalErrors">Notes Invalides</span>
                            <span v-else>Enregistrer <span class="hidden sm:inline">&nbsp;les Notes</span></span>
                        </PrimaryButton>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-gray-200" style="width: 30%">Élève</th>
                                    <th class="p-4 font-semibold border-b border-gray-200 whitespace-nowrap w-40 text-center">Note / 20</th>
                                    <th class="p-4 font-semibold border-b border-gray-200">Appréciation</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="student in students" :key="student.id" class="hover:bg-gray-50 transition">
                                    <td class="p-4 font-medium text-gray-900">
                                        <span class="uppercase font-bold">{{ student.nom }}</span> 
                                        <span class="capitalize ml-1">{{ student.prenom }}</span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <TextInput 
                                            type="number" 
                                            step="0.25" 
                                            min="0" 
                                            max="20"
                                            v-model="form.notes[student.id]" 
                                            class="w-28 text-center"
                                            :is-error="isInvalid(form.notes[student.id])"
                                            placeholder=""
                                        />
                                    </td>
                                    <td class="p-4">
                                        <TextInput 
                                            type="text" 
                                            v-model="form.comments[student.id]" 
                                            class="w-full"
                                            placeholder=""
                                        />
                                    </td>
                                </tr>
                                <tr v-if="students.length === 0">
                                    <td colspan="3" class="p-8 text-center text-gray-500">Aucun élève inscrit dans cette classe.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>