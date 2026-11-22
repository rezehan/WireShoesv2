<script setup>
import { computed } from 'vue'
import { SelectGroup, useForwardProps } from 'reka-ui'
import { cn } from '@/utils/cn'

const props = defineProps({
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
  class: { type: [String, Array, Object], default: undefined },
})

const delegated = computed(() => {
  const { class: _class, ...rest } = props
  return rest
})
const forwarded = useForwardProps(delegated)
</script>

<template>
  <SelectGroup
    data-slot="select-group"
    v-bind="forwarded"
    :class="cn(`flex flex-col gap-1`, props.class)"
  >
    <slot />
  </SelectGroup>
</template>