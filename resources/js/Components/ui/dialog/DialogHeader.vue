<script setup>
import { cn } from '@/utils/cn'

const props = defineProps({
  class: { type: [String, Array, Object], default: undefined },
})
</script>

<template>
  <div data-slot="dialog-header" :class="cn('flex flex-col gap-2', props.class)">
    <slot />
  </div>
</template>