<script setup>
import { useAttrs } from 'vue'
import { ChevronRightIcon } from '@/icons/layout'
import PaginationLink from './PaginationLink.vue'

defineOptions({ inheritAttrs: false })

defineProps({
  text: {
    type: String,
    default: 'Next',
  },
  size: {
    type: String,
    default: 'default',
    validator: (value) =>
      ['default', 'xs', 'sm', 'lg', 'icon', 'icon-xs', 'icon-sm', 'icon-lg'].includes(value),
  },
  variant: {
    type: String,
    default: undefined,
    validator: (value) => ['default', 'outline', 'ghost', 'destructive', 'link'].includes(value),
  },
  disabled: {
    type: Boolean,
    default: undefined,
  },
})

const attrs = useAttrs()
</script>

<template>
  <PaginationLink
    aria-label="Go to next page"
    :size="size"
    :variant="variant"
    :disabled="disabled"
    v-bind="attrs"
  >
    <slot>
      <span v-if="size !== 'icon'" class="hidden sm:block">{{ text }}</span>
      <ChevronRightIcon data-icon="inline-end" class="rtl:rotate-180" />
    </slot>
  </PaginationLink>
</template>