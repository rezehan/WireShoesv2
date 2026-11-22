<script setup>
import { useAttrs } from 'vue'
import { cn } from '@/utils/cn'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()
</script>

<template>
  <div
    data-slot="card-header"
    v-bind="{ ...attrs, class: undefined }"
    :class="cn('flex flex-col gap-2 group-data-[size=sm]/card:gap-1', attrs.class)"
  >
    <slot />
  </div>
</template>