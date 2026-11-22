<script setup>
import { useAttrs } from 'vue'
import { Badge } from '@/components/ui/badge'
import { ArrowDownIcon, ArrowUpIcon } from '@/icons/layout'
import { cn } from '@/utils/cn'

defineOptions({ inheritAttrs: false })

defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  change: {
    type: String,
    default: undefined,
  },
  direction: {
    type: String,
    default: 'up',
    validator: (value) => ['up', 'down'].includes(value),
  },
})

const attrs = useAttrs()
</script>

<template>
  <div
    v-bind="{ ...attrs, class: undefined }"
    :class="
      cn(
        'rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/3',
        attrs.class,
      )
    "
  >
    <div
      class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-white/90 [&>svg]:size-6"
    >
      <slot name="icon" />
    </div>

    <div class="mt-5 flex items-end justify-between">
      <div>
        <span class="text-sm text-gray-500 dark:text-gray-400">
          {{ title }}
        </span>
        <h4 class="mt-2 text-lg font-bold text-gray-800 dark:text-white/90">
          {{ value }}
        </h4>
      </div>

      <slot name="badge">
        <Badge v-if="change" :color="direction === 'down' ? 'error' : 'success'">
          <ArrowDownIcon v-if="direction === 'down'" class="text-error-500" />
          <ArrowUpIcon v-else />
          {{ change }}
        </Badge>
      </slot>
    </div>

    <slot />
  </div>
</template>