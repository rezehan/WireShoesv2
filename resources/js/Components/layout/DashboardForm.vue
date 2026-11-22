<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    fields: { type: Array, required: true },
    url: { type: String, required: true },
    method: { type: String, default: 'post' },
    label: { type: String, default: 'Simpan' },
});

const form = useForm(Object.fromEntries(props.fields.map((field) => [field.name, field.value ?? ''])));
const controlClass = 'mt-1.5 block w-full rounded-lg border-slate-200 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 aria-invalid:border-red-500 disabled:bg-slate-50';

function submit() {
    if (form.processing) return;

    form.submit(props.method, props.url, {
        preserveScroll: props.method === 'post',
        preserveState: props.method === 'get' ? 'errors' : true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="submit">
        <template v-for="field in fields" :key="field.name">
            <label v-if="field.type !== 'hidden'" class="block min-w-0 text-sm font-medium text-slate-700">
                <span>{{ field.label }}</span>

                <select v-if="field.type === 'select'" v-model="form[field.name]" :disabled="field.locked"
                    :aria-invalid="!!form.errors[field.name]" :class="controlClass">
                    <option v-for="(text, value) in field.options" :key="value" :value="value">
                        {{ text }}
                    </option>
                </select>

                <input v-else v-model="form[field.name]" :type="field.type"
                    :step="field.type === 'number' ? 'any' : undefined" :readonly="field.locked"
                    :aria-invalid="!!form.errors[field.name]" :class="controlClass" />

                <span v-if="form.errors[field.name]" class="mt-1 block text-red-700" role="alert">
                    {{ form.errors[field.name] }}
                </span>
            </label>

            <p v-else-if="form.errors[field.name]" class="text-sm text-red-700" role="alert">
                {{ form.errors[field.name] }}
            </p>
        </template>

        <div class="flex items-end">
            <button type="submit" :disabled="form.processing"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700 disabled:opacity-50">
                {{ form.processing ? 'Memproses…' : label }}
            </button>
        </div>
    </form>
</template>