<script setup>
import { ComboboxAnchor, useForwardProps } from 'reka-ui'

const props = defineProps({
  reference: { type: Object, default: undefined },
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
})

const forwarded = useForwardProps(props)
</script>

<template>
  <ComboboxAnchor data-slot="combobox-anchor" v-bind="forwarded">
    <slot />
  </ComboboxAnchor>
</template>