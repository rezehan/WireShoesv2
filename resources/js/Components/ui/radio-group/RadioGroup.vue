<script setup>
import { computed } from 'vue'
import { RadioGroupRoot, useForwardPropsEmits } from 'reka-ui'
import { cn } from '@/utils/cn'

const props = defineProps({
  modelValue: { type: null, default: undefined },
  defaultValue: { type: null, default: undefined },
  disabled: { type: Boolean, default: undefined },
  orientation: {
    type: String,
    default: undefined,
    validator: (value) => ['horizontal', 'vertical'].includes(value),
  },
  dir: {
    type: String,
    default: undefined,
    validator: (value) => ['ltr', 'rtl'].includes(value),
  },
  loop: { type: Boolean, default: undefined },
  name: { type: String, default: undefined },
  required: { type: Boolean, default: undefined },
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
  class: { type: [String, Array, Object], default: undefined },
})

const emits = defineEmits(['update:modelValue'])

const delegated = computed(() => {
  const { class: _class, ...rest } = props
  return rest
})
const forwarded = useForwardPropsEmits(delegated, emits)
</script>

<template>
  <RadioGroupRoot
    data-slot="radio-group"
    v-bind="forwarded"
    :class="cn(`grid w-full gap-2`, props.class)"
  >
    <slot />
  </RadioGroupRoot>
</template>