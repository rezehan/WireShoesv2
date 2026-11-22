<script setup>
import { useAttrs } from 'vue'
import { cn } from '@/utils/cn'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()
</script>

<template>
  <div
    data-slot="card-content"
    v-bind="{ ...attrs, class: undefined }"
    :class="cn(attrs.class)"
  >
    <slot />
  </div>
</template>