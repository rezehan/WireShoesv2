<script setup>
import { DialogClose, useForwardProps } from 'reka-ui'

const props = defineProps({
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
})

const forwarded = useForwardProps(props)
</script>

<template>
  <DialogClose data-slot="dialog-close" v-bind="forwarded">
    <slot />
  </DialogClose>
</template>