<script setup>
defineProps({
    // error | success
    variant: {
        type: String,
        default: 'error',
        validator: (value) => ['error', 'success'].includes(value),
    },
});
</script>

<template>
    <div
        role="alert"
        class="rounded-xl border px-4 py-3 text-sm font-medium"
        :class="
            variant === 'success'
                ? 'border-green-200 bg-green-50 text-green-800'
                : 'border-red-200 bg-red-50 text-red-800'
        "
    >
        <slot />
    </div>
</template>