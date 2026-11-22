<script setup>
import { useAttrs } from 'vue'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()
</script>

<template>
  <table v-bind="{ ...attrs, class: undefined }" :class="['min-w-full', attrs.class]">
    <slot />
  </table>
</template>