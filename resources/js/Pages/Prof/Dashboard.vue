<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';

defineProps({
    assignments: Array,
    managedClasses: Array
});

const goToEvaluations = (id) => {
    router.visit(route('prof.evaluations', { assignment_id: id }));
};
</script>

<template>
    <Head title="Tableau de bord Professeur" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 tracking-tight">
                Espace Professeur
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl">
                <!-- Section Responsabilité de Professeur Principal -->
                <div v-if="managedClasses && managedClasses.length > 0" class="mb-12">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-indigo-600 text-white rounded-xl shadow-lg shadow-indigo-100">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 tracking-tight">Responsabilités de Professeur Principal</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Link v-for="classe in managedClasses" :key="classe.id" :href="route('prof.conduite', { classe_id: classe.id })" class="group relative bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-xl hover:border-indigo-100 hover:-translate-y-1 transition-all duration-300 cursor-pointer overflow-hidden">
                            <!-- Decorative background blob -->
                            <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-50 rounded-full opacity-50 group-hover:scale-[2] group-hover:bg-indigo-100/50 transition-transform duration-500 ease-out z-0"></div>

                            <div class="relative z-10 flex justify-between items-start mb-6">
                                <div>
                                    <div class="inline-flex items-center justify-center bg-indigo-600 text-white rounded-xl shadow-sm px-3.5 py-1.5 mb-3 group-hover:bg-indigo-700 transition-colors">
                                        <span class="font-black text-[0.6rem] uppercase tracking-widest">PRINCIPAL</span>
                                    </div>
                                    <h4 class="font-black text-gray-900 text-2xl tracking-tight leading-none group-hover:text-indigo-700 transition-colors">{{ classe.nom }}</h4>
                                </div>
                                <div class="p-2 bg-indigo-50 text-indigo-700 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                </div>
                            </div>
                            
                            <div class="relative z-10 pt-4 border-t border-gray-50 flex items-center justify-between text-[0.7rem] font-bold text-gray-400 uppercase tracking-widest">
                                <div class="flex items-center text-gray-400 group-hover:text-gray-500 transition-colors">
                                    GESTION DE LA CONDUITE
                                </div>
                                <div class="inline-flex items-center text-indigo-600 opacity-0 group-hover:opacity-100 transition-all translate-x-3 group-hover:translate-x-0 duration-300">
                                    Saisir
                                    <svg class="w-3.5 h-3.5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 mb-1">Mes attributions</h3>
                            <p class="text-gray-600 text-sm">Retrouvez ci-dessous les classes et matières qui vous ont été assignées.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="assignment in assignments" :key="assignment.id" @click="goToEvaluations(assignment.id)" class="group relative bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-xl hover:border-indigo-100 hover:-translate-y-1 transition-all duration-300 cursor-pointer overflow-hidden">
                        <!-- Decorative background blob -->
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-50 rounded-full opacity-50 group-hover:scale-[2] group-hover:bg-indigo-100/50 transition-transform duration-500 ease-out z-0"></div>

                        <div class="relative z-10 flex justify-between items-start mb-6">
                            <div>
                                <div class="inline-flex items-center justify-center bg-indigo-600 text-white rounded-xl shadow-sm px-3.5 py-1.5 mb-3 group-hover:bg-indigo-700 transition-colors">
                                    <span class="font-black text-xs uppercase tracking-widest">{{ assignment.subject.nom }}</span>
                                </div>
                                <h4 class="font-black text-gray-900 text-2xl tracking-tight leading-none group-hover:text-indigo-700 transition-colors">{{ assignment.classe.nom }}</h4>
                            </div>
                            
                            <div class="flex flex-col items-end gap-2">
                                <span class="bg-indigo-50/80 text-indigo-700 text-[0.65rem] px-3 py-1.5 rounded-full font-black tracking-widest shadow-sm ring-1 ring-indigo-100/50 backdrop-blur-sm uppercase">
                                    Coef {{ assignment.coefficient }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="relative z-10 pt-4 border-t border-gray-50 flex items-center justify-between text-[0.7rem] font-bold text-gray-400 uppercase tracking-widest">
                            <div class="flex items-center text-gray-400 group-hover:text-gray-500 transition-colors">
                                <svg class="w-4 h-4 mr-1.5 text-gray-300 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                {{ assignment.school_year.name }}
                            </div>
                            <div class="inline-flex items-center text-indigo-600 opacity-0 group-hover:opacity-100 transition-all translate-x-3 group-hover:translate-x-0 duration-300">
                                Gérer
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
                            </div>
                        </div>
                    </div>

                    <div v-if="assignments.length === 0" class="col-span-3 bg-gray-50 rounded-lg p-8 text-center text-gray-500 border border-gray-100">
                        <p class="text-lg">Aucune classe ne vous a été attribuée pour le moment.</p>
                        <p class="text-sm mt-2">Veuillez contacter l'administrateur.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
