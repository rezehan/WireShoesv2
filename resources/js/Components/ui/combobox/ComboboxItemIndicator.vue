<script setup>
import { computed } from 'vue'
import { ComboboxItemIndicator, useForwardProps } from 'reka-ui'
import { CheckIcon } from '@/icons/layout'
import { cn } from '@/utils/cn'

const props = defineProps({
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
  class: { type: [String, Array, Object], default: undefined },
})

const delegated = computed(() => {
  const { class: _class, ...rest } = props
  return rest
})
const forwarded = useForwardProps(delegated)
</script>

<template>
  <ComboboxItemIndicator
    data-slot="combobox-item-indicator"
    v-bind="forwarded"
    :class="cn('size-4 text-brand-500 dark:text-brand-400', props.class)"
  >
    <slot>
      <CheckIcon class="size-4" />
    </slot>
  </ComboboxItemIndicator>
</template>