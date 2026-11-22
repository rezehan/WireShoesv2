<script setup>
import { SelectPortal, useForwardProps } from 'reka-ui'

const props = defineProps({
  to: { type: [String, Object], default: undefined },
  disabled: { type: Boolean, default: undefined },
  defer: { type: Boolean, default: undefined },
  forceMount: { type: Boolean, default: undefined },
})

const forwarded = useForwardProps(props)
</script>

<template>
  <SelectPortal data-slot="select-portal" v-bind="forwarded">
    <slot />
  </SelectPortal>
</template>