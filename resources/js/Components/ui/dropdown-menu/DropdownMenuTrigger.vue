<script setup>
import { DropdownMenuTrigger, useForwardProps } from 'reka-ui'

const props = defineProps({
  disabled: { type: Boolean, default: undefined },
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
})

const forwarded = useForwardProps(props)
</script>

<template>
  <DropdownMenuTrigger data-slot="dropdown-menu-trigger" v-bind="forwarded">
    <slot />
  </DropdownMenuTrigger>
</template>