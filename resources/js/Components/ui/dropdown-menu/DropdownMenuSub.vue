<script setup>
import { DropdownMenuSub, useForwardPropsEmits } from 'reka-ui'

const props = defineProps({
  defaultOpen: { type: Boolean, default: undefined },
  open: { type: Boolean, default: undefined },
})

const emits = defineEmits(['update:open'])

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <DropdownMenuSub data-slot="dropdown-menu-sub" v-bind="forwarded">
    <slot />
  </DropdownMenuSub>
</template>