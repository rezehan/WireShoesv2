<script setup>
import { DialogPortal, useForwardProps } from 'reka-ui'

const props = defineProps({
  to: { type: [String, Object], default: undefined },
  disabled: { type: Boolean, default: undefined },
  defer: { type: Boolean, default: undefined },
  forceMount: { type: Boolean, default: undefined },
})

const forwarded = useForwardProps(props)
</script>

<template>
  <DialogPortal data-slot="dialog-portal" v-bind="forwarded">
    <slot />
  </DialogPortal>
</template>