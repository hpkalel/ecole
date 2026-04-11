<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';

const props = defineProps({
    activeYear: Object,
    suggestedName: String,
    preview: Array
});

const form = useForm({
    current_year_id: props.activeYear ? props.activeYear.id : '',
    target_year_name: props.suggestedName
});

const confirmingPromotion = ref(false);

const runPromotion = () => {
    confirmingPromotion.value = true;
};

const executePromotion = () => {
    form.post(route('admin.promote.process'), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingPromotion.value = false;
        }
    });
};
</script>

<template>
    <Head title="Promotion des Élèves" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Promotion des Élèves</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl  space-y-6">
                <!-- Parameters Card -->
                <!-- Parameters Card Premium -->
                <div class="group relative bg-white rounded-2xl p-6 sm:p-8 border border-gray-100 shadow-sm hover:shadow-xl hover:border-indigo-100 transition-all duration-300 overflow-hidden">
                    <!-- Decorative background blob -->
                    <div class="absolute -right-6 -top-6 w-32 h-32 bg-indigo-50 rounded-full opacity-50 group-hover:scale-[2] group-hover:bg-indigo-100/50 transition-transform duration-500 ease-out z-0 pointer-events-none"></div>

                    <div class="relative z-10 w-full">
                        <div class="inline-flex items-center justify-center bg-indigo-600 text-white rounded-xl shadow-sm px-3.5 py-1.5 mb-4 group-hover:bg-indigo-700 transition-colors">
                            <span class="font-bold text-xs uppercase tracking-widest">Promotion globale</span>
                        </div>
                        <h3 class="font-black text-gray-900 text-xl tracking-tight leading-none mb-3">Lancer les promotions pour {{ activeYear ? activeYear.name : 'Unknown' }}</h3>
                        
                        <div class="text-sm text-gray-600 mt-2 leading-relaxed max-w-2xl mb-8">
                            Les élèves obtiendront automatiquement leurs affectations de la nouvelle année selon leur moyenne annuelle.<br>
                            <span class="inline-block mt-3 font-bold text-[0.75rem] bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">
                                <span class="text-indigo-900">Passage:</span> Moyenne ≥ 10 <span class="text-gray-300 mx-2">|</span> 
                                <span class="text-rose-700">Redoublement:</span> Moyenne &lt; 10
                            </span>
                        </div>
                        
                        <div class="pt-6 border-t border-gray-50 flex flex-col sm:flex-row items-end gap-4 max-w-[28rem]">
                            <div class="w-full">
                                <InputLabel value="Année Scolaire de destination" class="text-[0.7rem] uppercase tracking-widest font-black text-gray-400 mb-2" />
                                <TextInput v-model="form.target_year_name" type="text" class="block w-full px-4 border-gray-200 rounded-xl" placeholder="Ex: 2024-2025" />
                            </div>
                            <PrimaryButton @click="runPromotion" :disabled="form.processing" class="w-full sm:w-auto justify-center h-[42px] px-6 bg-gray-800 hover:bg-gray-900 font-black uppercase tracking-widest text-[0.7rem] border-none shadow-lg shadow-gray-900/20 hover:scale-[1.02] active:scale-95 transition-all">
                                Exécuter la Promotion
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- Preview Table -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Aperçu (Échantillon sur {{ preview.length }} élèves)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-400 text-xs font-bold uppercase tracking-wider">
                                    <th class="p-4 border-b border-gray-200 whitespace-nowrap">Élève</th>
                                    <th class="p-4 border-b border-gray-200 whitespace-nowrap">Classe Actuelle</th>
                                    <th class="p-4 border-b border-gray-200 whitespace-nowrap">Moy. Annuelle</th>
                                    <th class="p-4 border-b border-gray-200 text-right whitespace-nowrap">Décision Projetée</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in preview" :key="item.id" class="hover:bg-slate-50">
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="font-bold text-gray-800 uppercase">{{ item.nom }}</span> 
                                        <span class="font-bold text-gray-800 capitalize ml-1">{{ item.prenom }}</span>
                                    </td>
                                    <td class="p-4 text-gray-600 whitespace-nowrap">{{ item.classe }}</td>
                                    <td class="p-4 font-black bg-indigo-50 text-indigo-900 border-x border-gray-100 whitespace-nowrap">{{ item.avg }}</td>
                                    <td class="p-4 font-bold text-right text-sm whitespace-nowrap" :class="item.color">{{ item.decision }}</td>
                                </tr>
                                <tr v-if="preview.length === 0">
                                    <td colspan="4" class="p-8 text-center text-gray-400 font-bold">Aucune inscription dans l'année active.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="confirmingPromotion" @close="confirmingPromotion = false">
            <div class="p-8">
                <h2 class="text-2xl font-black text-gray-900 mb-4 border-b-4 border-indigo-600 inline-block">Confirmation de la Promotion</h2>
                <p class="text-sm text-gray-500 mb-8 leading-relaxed">
                    Vous êtes sur le point de clôturer l'année scolaire en cours et d'inscrire automatiquement tous les élèves dans leur nouvelle classe selon les moyennes annuelles.<br><br>
                    <span class="font-bold text-rose-600">Attention :</span> Cette opération d'envergure peut prendre quelques instants. Êtes-vous complètement sûr de vouloir continuer ?
                </p>

                <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-gray-50">
                    <SecondaryButton @click="confirmingPromotion = false">Annuler</SecondaryButton>
                    <PrimaryButton class="bg-gray-900 hover:bg-gray-800 shadow-md font-bold text-xs uppercase tracking-widest" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="executePromotion">
                        Confirmer et Exécuter
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>