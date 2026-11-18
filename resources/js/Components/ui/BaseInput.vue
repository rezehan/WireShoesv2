<script setup>
import { computed } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    // text | email | password | textarea | ...
    type: { type: String, default: 'text' },
    name: { type: String, default: '' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const fieldId = computed(() => (props.name ? `field-${props.name}` : undefined));

const fieldClass = computed(() => [
    'block w-full rounded-xl border bg-white px-4 py-3 text-slate-800 placeholder:text-slate-400',
    'transition focus:outline-none focus:ring-2',
    props.error
        ? 'border-red-500 focus:border-red-600 focus:ring-red-200'
        : 'border-slate-300 focus:border-red-700 focus:ring-red-200',
]);

const onInput = (event) => emit('update:modelValue', event.target.value);
</script>

<template>
    <div>
        <label
            v-if="label"
            :for="fieldId"
            class="mb-1.5 block text-sm font-semibold text-slate-700"
        >
            {{ label }}
        </label>

        <textarea
            v-if="type === 'textarea'"
            v-bind="$attrs"
            :id="fieldId"
            :name="name"
            :value="modelValue"
            :class="fieldClass"
            :aria-invalid="!!error"
            rows="3"
            @input="onInput"
        />
        <input
            v-else
            v-bind="$attrs"
            :id="fieldId"
            :name="name"
            :type="type"
            :value="modelValue"
            :class="fieldClass"
            :aria-invalid="!!error"
            @input="onInput"
        />

        <p v-if="hint && !error" class="mt-1.5 text-sm text-slate-500">{{ hint }}</p>
        <p v-if="error" class="mt-1.5 text-sm font-medium text-red-600" role="alert">
            {{ error }}
        </p>
    </div>
</template>