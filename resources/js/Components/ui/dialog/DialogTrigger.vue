<script setup>
import { DialogTrigger, useForwardProps } from 'reka-ui'

const props = defineProps({
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
})

const forwarded = useForwardProps(props)
</script>

<template>
  <DialogTrigger data-slot="dialog-trigger" v-bind="forwarded">
    <slot />
  </DialogTrigger>
</template>