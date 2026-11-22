<script setup>
defineOptions({ inheritAttrs: true })

defineProps({
  isHeader: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <component :is="isHeader ? 'th' : 'td'">
    <slot />
  </component>
</template>