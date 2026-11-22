<script setup>
import { ComboboxRoot, useForwardPropsEmits } from 'reka-ui'

const props = defineProps({
  modelValue: { type: null, default: undefined },
  defaultValue: { type: null, default: undefined },
  open: { type: Boolean, default: undefined },
  defaultOpen: { type: Boolean, default: undefined },
  searchTerm: { type: String, default: undefined },
  multiple: { type: Boolean, default: undefined },
  disabled: { type: Boolean, default: undefined },
  name: { type: String, default: undefined },
  required: { type: Boolean, default: undefined },
  dir: { type: String, default: undefined },
  filterFunction: { type: Function, default: undefined },
  displayValue: { type: Function, default: undefined },
  resetSearchTermOnBlur: { type: Boolean, default: undefined },
  resetSearchTermOnSelect: { type: Boolean, default: undefined },
  openOnClick: { type: Boolean, default: undefined },
  openOnFocus: { type: Boolean, default: undefined },
  by: { type: [String, Function], default: undefined },
  ignoreFilter: { type: Boolean, default: undefined },
  highlightOnHover: { type: Boolean, default: undefined },
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
})

const emits = defineEmits(['update:modelValue', 'update:open', 'update:searchTerm', 'highlight'])

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <ComboboxRoot data-slot="combobox" v-bind="forwarded">
    <slot />
  </ComboboxRoot>
</template>