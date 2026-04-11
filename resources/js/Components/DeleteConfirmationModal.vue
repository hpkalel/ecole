<script setup>
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirmer la suppression',
    },
    message: {
        type: String,
        default: 'Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.',
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close', 'confirm']);
</script>

<template>
    <Modal :show="show" @close="$emit('close')" maxWidth="md">
        <div class="p-8">
            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-rose-100 rounded-2xl mb-6 ring-8 ring-rose-50">
                <svg class="w-8 h-8 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.34c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            
            <div class="text-center">
                <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight mb-2">{{ title }}</h3>
                <p class="text-sm text-gray-500 font-medium leading-relaxed">
                    {{ message }}
                </p>
            </div>

            <div class="mt-8 flex flex-col sm:flex-row gap-3 sm:justify-center">
                <SecondaryButton @click="$emit('close')" class="w-full sm:w-auto justify-center py-3 rounded-xl border-gray-200">
                    Annuler
                </SecondaryButton>
                <DangerButton 
                    @click="$emit('confirm')" 
                    :class="{ 'opacity-25': processing }" 
                    :disabled="processing"
                    class="w-full sm:w-auto justify-center py-3 rounded-xl shadow-lg shadow-rose-100"
                >
                    Supprimer l'élément
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
