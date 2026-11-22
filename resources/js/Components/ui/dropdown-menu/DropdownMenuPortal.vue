<script setup>
import { DropdownMenuPortal, useForwardProps } from 'reka-ui'

const props = defineProps({
  to: { type: [String, Object], default: undefined },
  disabled: { type: Boolean, default: undefined },
  defer: { type: Boolean, default: undefined },
  forceMount: { type: Boolean, default: undefined },
})

const forwarded = useForwardProps(props)
</script>

<template>
  <DropdownMenuPortal data-slot="dropdown-menu-portal" v-bind="forwarded">
    <slot />
  </DropdownMenuPortal>
</template>