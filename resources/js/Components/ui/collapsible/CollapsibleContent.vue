<script setup>
import { computed } from 'vue'
import { CollapsibleContent, useForwardProps } from 'reka-ui'
import { cn } from '@/utils/cn'

const props = defineProps({
  forceMount: {
    type: Boolean,
    default: undefined,
  },
  as: {
    type: [String, Object],
    default: undefined,
  },
  asChild: {
    type: Boolean,
    default: false,
  },
  class: {
    type: [String, Array, Object],
    default: undefined,
  },
})

const delegated = computed(() => {
  const { class: _class, ...rest } = props
  return rest
})
const forwarded = useForwardProps(delegated)
</script>

<template>
  <CollapsibleContent
    data-slot="collapsible-content"
    v-bind="forwarded"
    :class="
      cn(
        `overflow-hidden data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down`,
        props.class,
      )
    "
  >
    <slot />
  </CollapsibleContent>
</template>