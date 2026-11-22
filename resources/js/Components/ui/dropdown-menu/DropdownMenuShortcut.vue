<script setup>
import { cn } from '@/utils/cn'

const props = defineProps({
  class: { type: [String, Array, Object], default: undefined },
})
</script>

<template>
  <span
    data-slot="dropdown-menu-shortcut"
    :class="
      cn(
        'ms-auto text-xs tracking-widest text-gray-400 rtl:tracking-normal dark:text-gray-500',
        props.class,
      )
    "
  >
    <slot />
  </span>
</template>