<script setup>
import { DropdownMenuRoot, useForwardPropsEmits } from 'reka-ui'

const props = defineProps({
  defaultOpen: { type: Boolean, default: undefined },
  open: { type: Boolean, default: undefined },
  dir: { type: String, default: undefined },
  modal: { type: Boolean, default: false },
})

const emits = defineEmits(['update:open'])

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <DropdownMenuRoot data-slot="dropdown-menu" v-bind="forwarded">
    <slot />
  </DropdownMenuRoot>
</template>