<script setup>
import { computed } from 'vue';

const props = defineProps({
    password: {
        type: String,
        default: '',
    },
});

const requirements = [
    { label: 'Au moins 8 caractères', regex: /.{8,}/ },
    { label: 'Une lettre majuscule', regex: /[A-Z]/ },
    { label: 'Une lettre minuscule', regex: /[a-z]/ },
    { label: 'Un chiffre', regex: /[0-9]/ },
    { label: 'Un caractère spécial', regex: /[^A-Za-z0-9]/ },
];

const checks = computed(() => {
    return requirements.map((req) => ({
        label: req.label,
        met: req.regex.test(props.password),
    }));
});
</script>

<template>
    <div class="mt-4 p-4 bg-gray-50/50 rounded-2xl border border-gray-100 shadow-sm">
        <h3 class="text-sm font-black text-gray-900 mb-3 uppercase tracking-tight">Le mot de passe doit contenir :</h3>
        <ul class="space-y-2">
            <li v-for="check in checks" :key="check.label" class="flex items-center gap-3 transition-all duration-300">
                <div 
                    class="h-5 w-5 rounded-full flex items-center justify-center border-2 transition-all duration-300"
                    :class="check.met ? 'bg-green-100 border-green-500 text-green-600' : 'bg-white border-gray-200 text-gray-300'"
                >
                    <svg v-if="check.met" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7" />
                    </svg>
                    <div v-else class="h-1.5 w-1.5 rounded-full bg-gray-200"></div>
                </div>
                <span 
                    class="text-sm font-medium transition-colors duration-300"
                    :class="check.met ? 'text-green-700' : 'text-gray-500 line-through decoration-gray-200'"
                >
                    {{ check.label }}
                </span>
            </li>
        </ul>
    </div>
</template>
