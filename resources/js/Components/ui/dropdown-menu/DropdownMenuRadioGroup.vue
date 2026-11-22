<script setup>
import { DropdownMenuRadioGroup, useForwardPropsEmits } from 'reka-ui'

const props = defineProps({
  modelValue: { type: String, default: undefined },
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
})

const emits = defineEmits(['update:modelValue'])

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <DropdownMenuRadioGroup data-slot="dropdown-menu-radio-group" v-bind="forwarded">
    <slot />
  </DropdownMenuRadioGroup>
</template>