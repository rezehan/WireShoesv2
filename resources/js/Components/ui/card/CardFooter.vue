<script setup>
import { useAttrs } from 'vue'
import { cn } from '@/utils/cn'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()
</script>

<template>
  <div
    data-slot="card-footer"
    v-bind="{ ...attrs, class: undefined }"
    :class="
      cn('flex items-center border-t border-gray-100 pt-4 dark:border-gray-800', attrs.class)
    "
  >
    <slot />
  </div>
</template>