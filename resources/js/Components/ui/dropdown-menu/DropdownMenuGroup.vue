<script setup>
import { DropdownMenuGroup, useForwardProps } from 'reka-ui'

const props = defineProps({
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
})

const forwarded = useForwardProps(props)
</script>

<template>
  <DropdownMenuGroup data-slot="dropdown-menu-group" v-bind="forwarded">
    <slot />
  </DropdownMenuGroup>
</template>