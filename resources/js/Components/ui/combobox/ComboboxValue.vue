<script setup>
defineProps({
  placeholder: { type: String, default: undefined },
})
</script>

<template>
  <span data-slot="combobox-value">
    <slot>{{ placeholder }}</slot>
  </span>
</template>