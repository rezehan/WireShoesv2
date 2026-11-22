<script setup>
import { CollapsibleRoot, useForwardPropsEmits } from 'reka-ui'

const props = defineProps({
  defaultOpen: {
    type: Boolean,
    default: undefined,
  },
  open: {
    type: Boolean,
    default: undefined,
  },
  disabled: {
    type: Boolean,
    default: undefined,
  },
  unmountOnHide: {
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
})

const emits = defineEmits(['update:open'])

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <CollapsibleRoot data-slot="collapsible" v-bind="forwarded">
    <slot />
  </CollapsibleRoot>
</template>