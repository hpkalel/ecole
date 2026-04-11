<script setup>
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    student: Object,
    enrollment: Object,
    classCount: Number,
    activeYear: Object,
    periode: String,
    subjectsData: Array,
    maxInterros: Number,
    maxDevoirs: Number,
    globalTotal: Number,
    globalCoeff: Number,
    globalAverage: Number,
    sem1Average: Number,
    annualAverage: Number
});

const getBadgeClasses = (avg) => {
    if (avg === null) return 'text-gray-400';
    if (avg < 8) return 'bg-red-100 text-red-700 px-2 rounded';
    if (avg < 10) return 'bg-orange-100 text-orange-700 px-2 rounded';
    if (avg < 12) return 'bg-yellow-100 text-yellow-700 px-2 rounded';
    if (avg < 14) return 'bg-blue-100 text-blue-700 px-2 rounded';
    if (avg < 16) return 'bg-green-100 text-green-700 px-2 rounded';
    return 'bg-indigo-600 text-white px-2 rounded font-bold';
};

const getAppreciationText = (avg) => {
    if (avg === null) return '-';
    if (avg < 8) return 'Médiocre';
    if (avg < 10) return 'Insuffisant';
    if (avg < 12) return 'Passable';
    if (avg < 14) return 'Assez Bien';
    if (avg < 16) return 'Bien';
    return 'T. Bien';
};

const changePeriode = (e) => {
    router.visit(route('admin.bulletin', {
        student_id: props.student.id,
        school_year_id: props.activeYear.id,
        periode: e.target.value
    }));
};
</script>

<template>
    <Head :title="'Bulletin - ' + student.nom" />

    <div class="min-h-screen bg-gray-50 flex flex-col p-4 sm:p-8 relative">
        <div class="max-w-[1100px] mx-auto w-full print:hidden mb-6 flex justify-between items-center">
            <Link :href="route('admin.students')" class="bg-white border shadow-sm px-4 py-2 rounded-md font-medium text-gray-700 hover:bg-gray-50">&larr; Retour</Link>
            <button onclick="window.print()" class="bg-indigo-600 text-white shadow px-6 py-2 rounded-md font-bold hover:bg-indigo-700">🖨️ Imprimer le bulletin</button>
        </div>

        <div class="max-w-[1100px] mx-auto w-full bg-white rounded-xl shadow-xl overflow-hidden print:shadow-none print:w-full print:max-w-none print:border-none print:rounded-none border border-gray-200">
            <!-- Header -->
            <div class="p-6 sm:p-8 border-b-2 border-indigo-600 flex flex-row items-start justify-between gap-2 sm:gap-6 print:p-0 print:pb-4">
                <div class="text-left w-1/2 sm:w-auto">
                    <h1 class="text-lg sm:text-2xl font-black text-gray-900 uppercase leading-none">GROUPE SCOLAIRE ECOLE 2</h1>
                    <p class="text-[0.6rem] sm:text-sm font-bold text-gray-400 tracking-widest mt-1 sm:mt-2 uppercase">RÉPUBLIQUE DU BÉNIN</p>
                    <h2 class="text-[0.6rem] sm:text-lg font-black text-gray-600 mt-1 uppercase tracking-tight">FRATERNITÉ - JUSTICE - TRAVAIL</h2>
                </div>
                <div class="text-right w-1/2 sm:w-auto">
                    <h2 class="text-xl sm:text-3xl font-black text-indigo-700 tracking-tight uppercase">BULLETIN DE NOTES</h2>
                    <p class="text-xs sm:text-base font-bold text-gray-600 mt-0.5 sm:mt-1">ANNÉE SCOLAIRE : {{ activeYear.name }}</p>
                    
                    <select :value="periode" @change="changePeriode" class="print:hidden mt-2 border-gray-300 rounded font-semibold text-indigo-700">
                        <option value="Semestre 1">Semestre 1</option>
                        <option value="Semestre 2">Semestre 2</option>
                    </select>
                    <p class="hidden print:block text-sm sm:text-lg font-bold text-gray-500 mt-1">{{ periode }}</p>
                </div>
            </div>

            <!-- Thick Blue Divider -->
            <div class="h-1 bg-[#1e40af] w-full"></div>

            <div class="p-4 sm:p-6">
                <div class="bg-[#f8fafc] border border-slate-200 rounded-2xl p-4 sm:p-6 grid grid-cols-2 gap-y-3 gap-x-4 sm:gap-x-12 print:p-4 print:bg-transparent print:border-slate-300">
                    <!-- Left Col -->
                    <div class="space-y-4">
                        <div class="grid grid-cols-[85px_1fr] sm:grid-cols-[100px_1fr] items-center">
                            <span class="text-[0.6rem] sm:text-[0.65rem] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Élève :</span>
                            <span class="text-sm sm:text-lg md:text-xl font-black text-slate-900 uppercase truncate">{{ student.nom }} {{ student.prenom }}</span>
                        </div>
                        <div class="grid grid-cols-[85px_1fr] sm:grid-cols-[100px_1fr] items-center">
                            <span class="text-[0.6rem] sm:text-[0.65rem] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Matricule :</span>
                            <span class="text-sm sm:text-lg font-black text-slate-700 tracking-tighter truncate">{{ student.matricule || student.id.toString().padStart(6, '0') }}</span>
                        </div>
                        <div class="grid grid-cols-[85px_1fr] sm:grid-cols-[100px_1fr] items-center">
                            <span class="text-[0.6rem] sm:text-[0.65rem] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Sexe :</span>
                            <span class="text-sm sm:text-lg font-bold text-slate-700 truncate">{{ student.sexe === 'M' ? 'Masculin' : 'Féminin' }}</span>
                        </div>
                    </div>
                    <!-- Right Col -->
                    <div class="space-y-4">
                        <div class="grid grid-cols-[85px_1fr] sm:grid-cols-[100px_1fr] items-center">
                            <span class="text-[0.6rem] sm:text-[0.65rem] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Classe :</span>
                            <span class="text-sm sm:text-lg md:text-xl font-black text-indigo-700 truncate">{{ enrollment ? enrollment.classe.nom : 'Non assigné' }}</span>
                        </div>
                        <div class="grid grid-cols-[85px_1fr] sm:grid-cols-[100px_1fr] items-center">
                            <span class="text-[0.6rem] sm:text-[0.65rem] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Statut :</span>
                            <span class="text-sm sm:text-lg font-bold text-slate-700 truncate">{{ enrollment ? enrollment.statut : '-' }}</span>
                        </div>
                        <div class="grid grid-cols-[85px_1fr] sm:grid-cols-[100px_1fr] items-center">
                            <span class="text-[0.6rem] sm:text-[0.65rem] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Effectif :</span>
                            <span class="text-sm sm:text-lg font-bold text-slate-700 truncate">{{ classCount }} élèves</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="p-2 sm:p-6 overflow-x-auto invisible-scrollbar print:p-0 print:overflow-visible">
                <table class="w-full min-w-[850px] text-[0.8rem] text-center border-collapse print:text-[0.65rem] print:min-w-0">
                    <thead>
                        <tr class="bg-gray-100 text-gray-500 font-bold tracking-wider border border-gray-300">
                            <th class="py-2 px-3 text-left border-r border-gray-300 uppercase">Matière</th>
                            <th class="py-2 px-1 border-r border-gray-300">Coeff</th>
                            <th v-for="i in maxInterros" :key="'hi'+i" class="py-2 px-1 border-r border-gray-200 text-gray-400">Int. {{i}}</th>
                            <th class="py-2 px-1 border-r border-gray-300 bg-gray-200">Moy. Int.</th>
                            <th v-for="i in maxDevoirs" :key="'hd'+i" class="py-2 px-1 border-r border-gray-200 text-gray-400">Dev. {{i}}</th>
                            <th class="py-2 px-2 border-r border-gray-300 bg-gray-200">Moy. Gén.</th>
                            <th class="py-2 px-2 border-r border-gray-300 bg-slate-200 text-indigo-900 border-l-2 border-l-gray-400">Moy. Coef.</th>
                            <th class="py-2 px-3 text-left border-r border-gray-300">Appréciation</th>
                            <th class="py-2 px-2 border-gray-300 text-xs">Professeur</th>
                        </tr>
                    </thead>
                    <tbody class="border border-gray-300">
                        <tr v-for="sub in subjectsData" :key="sub.subject_name" class="border-b border-gray-200 hover:bg-slate-50">
                            <td class="py-3 px-3 text-left font-bold text-gray-800 border-r border-gray-300 print:py-1">{{ sub.subject_name }}</td>
                            <td class="py-3 px-1 font-bold text-gray-400 border-r border-gray-300 print:py-1">{{ sub.coeff }}</td>
                            
                            <td v-for="i in maxInterros" :key="'di'+i" class="py-3 px-1 border-r border-gray-200 print:py-1">
                                {{ sub.interros[i-1] ? sub.interros[i-1].valeur : '-' }}
                            </td>
                            <td class="py-3 px-1 font-semibold text-gray-500 bg-slate-50 border-r border-gray-300 print:py-1">
                                {{ sub.avg_interro !== null ? sub.avg_interro : '-' }}
                            </td>
                            
                            <td v-for="i in maxDevoirs" :key="'dd'+i" class="py-3 px-1 border-r border-gray-200 print:py-1">
                                {{ sub.devoirs[i-1] ? sub.devoirs[i-1].valeur : '-' }}
                            </td>
                            <td class="py-3 px-2 font-black text-gray-700 bg-slate-50 border-r border-gray-300 print:py-1">
                                {{ sub.average !== null ? sub.average : '-' }}
                            </td>
                            <td class="py-3 px-2 font-black text-indigo-900 bg-slate-100 border-r border-gray-300 border-l-2 border-l-gray-300 print:py-1">
                                {{ sub.weighted_score !== null ? sub.weighted_score : '-' }}
                            </td>
                            <td class="py-3 px-3 text-left font-bold uppercase tracking-tight text-[0.7rem] border-r border-gray-300 print:py-1">
                                <span :class="getBadgeClasses(sub.average)">{{ getAppreciationText(sub.average) }}</span>
                            </td>
                            <td class="py-3 px-2 text-[0.65rem] text-gray-400 font-bold uppercase print:py-1">
                                {{ sub.prof }}
                            </td>
                        </tr>
                        <tr v-if="subjectsData.length === 0">
                            <td :colspan="6 + maxInterros + maxDevoirs" class="p-8 text-gray-400 font-bold">Aucune note enregistrée pour le {{ periode }}.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="subjectsData.length > 0">
                        <tr class="bg-gray-100 font-bold text-gray-800 border-l border-r border-gray-300">
                            <td class="py-3 px-3 text-left border-r border-gray-300">TOTAL</td>
                            <td class="py-3 px-1 border-r border-gray-300">{{ globalCoeff }}</td>
                            <td :colspan="maxInterros + maxDevoirs + 2" class="border-r border-gray-300 bg-slate-50"></td>
                            <td class="py-3 px-2 border-r border-gray-300 text-[0.95rem] bg-slate-200 border-l-2 border-l-gray-400 text-indigo-900">{{ globalTotal }}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr class="bg-slate-200 border-l border-r border-b border-gray-300">
                            <td :colspan="2 + maxInterros + maxDevoirs + 2" class="py-4 px-4 text-right font-black uppercase text-gray-600 tracking-wider">Moyenne Générale {{ periode }}</td>
                            <td class="py-4 px-2 font-black text-xl text-white bg-indigo-600 border-x border-indigo-700 shadow-inner align-middle">
                                {{ globalAverage !== null ? globalAverage : 'N/A' }}
                            </td>
                            <td colspan="2" class="bg-slate-100"></td>
                        </tr>
                        
                        <!-- Lignes Annuelles (Semestre 2 uniquement) -->
                        <tr v-if="periode === 'Semestre 2' && sem1Average !== null" class="bg-white border border-t-2 border-t-gray-400">
                            <td :colspan="2 + maxInterros + maxDevoirs + 2" class="py-3 px-4 text-right font-bold uppercase text-gray-400 text-xs">Moyenne Semestre 1 Rappel</td>
                            <td class="py-3 px-2 font-bold text-[0.95rem] text-gray-500 bg-gray-50 align-middle border-x border-gray-300">{{ sem1Average }}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr v-if="periode === 'Semestre 2' && sem1Average !== null" class="bg-green-50 border border-green-200">
                            <td :colspan="2 + maxInterros + maxDevoirs + 2" class="py-4 px-4 text-right font-black uppercase text-green-800 tracking-wider">MOYENNE ANNUELLE (S1×2 + S2) / 3</td>
                            <td class="py-4 px-2 font-black text-2xl text-green-900 bg-green-200 border-x border-green-300 shadow-inner align-middle">
                                {{ annualAverage }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Signatures -->
            <div class="mt-8 mb-16 px-4 sm:px-16 grid grid-cols-2 gap-4 sm:gap-20 print:mt-4 print:mb-8 text-[0.6rem] sm:text-xs font-bold text-gray-400 uppercase tracking-widest">
                <div class="text-center flex flex-col items-center">
                    <p class="mb-20 sm:mb-24">SIGNATURES DES PARENTS</p>
                    <div class="border-b-2 border-gray-300 w-full max-w-[150px]"></div>
                </div>
                <div class="text-center flex flex-col items-center">
                    <p class="mb-20 sm:mb-24">SIGNATURE DU DIRECTEUR</p>
                    <div class="border-b-2 border-gray-300 w-full max-w-[150px]"></div>
                </div>
            </div>
            
            <!-- Watermark -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.02] overflow-hidden -z-10">
                <span class="text-[15rem] font-black tracking-tighter -rotate-12 whitespace-nowrap">ECOLE 2</span>
            </div>
        </div>
    </div>
</template>
<style>
@media print {
    @page { 
        size: A4 portrait; 
        margin: 0; 
    }
    body { 
        background: white !important; 
        margin: 0 !important; 
    }
    .min-h-screen {
        padding: 15mm !important;
        background: white !important;
    }
}
</style>