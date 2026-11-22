<script setup>
import { useAttrs } from 'vue'
import { cn } from '@/utils/cn'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()
</script>

<template>
  <div
    data-slot="alert-action"
    v-bind="{ ...attrs, class: undefined }"
    :class="cn('absolute end-2 top-2', attrs.class)"
  >
    <slot />
  </div>
</template>