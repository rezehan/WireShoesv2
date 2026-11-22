<script setup>
import { useAttrs } from 'vue'
import { cn } from '@/utils/cn'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()
</script>

<template>
  <div
    data-slot="sidebar-group-content"
    data-sidebar="group-content"
    v-bind="{ ...attrs, class: undefined }"
    :class="cn('w-full text-sm', attrs.class)"
  >
    <slot />
  </div>
</template>