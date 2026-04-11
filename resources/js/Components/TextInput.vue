<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const props = defineProps({
    isError: {
        type: Boolean,
        default: false
    }
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ 
    focus: () => input.value.focus(),
    input
});
</script>

<template>
    <input
        class="rounded-md border-gray-300 shadow-sm transition-all duration-200"
        :class="[
            isError 
                ? 'border-red-500 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
        ]"
        v-model="model"
        ref="input"
        v-bind="$attrs"
    />
</template>
