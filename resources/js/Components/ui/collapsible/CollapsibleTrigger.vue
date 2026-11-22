<script setup>
import { CollapsibleTrigger, useForwardProps } from 'reka-ui'

const props = defineProps({
  as: {
    type: [String, Object],
    default: undefined,
  },
  asChild: {
    type: Boolean,
    default: false,
  },
})

const forwarded = useForwardProps(props)
</script>

<template>
  <CollapsibleTrigger data-slot="collapsible-trigger" v-bind="forwarded">
    <slot />
  </CollapsibleTrigger>
</template>