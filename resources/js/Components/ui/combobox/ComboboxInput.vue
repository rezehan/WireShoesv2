<script setup>
import { computed } from 'vue'
import { ComboboxAnchor, ComboboxInput, useForwardPropsEmits } from 'reka-ui'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import { cn } from '@/utils/cn'
import ComboboxClear from './ComboboxClear.vue'
import ComboboxTrigger from './ComboboxTrigger.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: { type: String, default: undefined },
  displayValue: { type: Function, default: undefined },
  autoFocus: { type: Boolean, default: undefined },
  disabled: { type: Boolean, default: undefined },
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
  class: { type: [String, Array, Object], default: undefined },
  showTrigger: { type: Boolean, default: true },
  showClear: { type: Boolean, default: false },
})

const emits = defineEmits(['update:modelValue'])

const delegated = computed(() => {
  const { class: _class, showTrigger: _showTrigger, showClear: _showClear, ...rest } = props
  return rest
})
const forwarded = useForwardPropsEmits(delegated, emits)
</script>

<template>
  <ComboboxAnchor as-child>
    <InputGroup :class="cn('w-full', props.class)">
      <ComboboxInput v-bind="forwarded" as-child>
        <InputGroupInput v-bind="$attrs" :disabled="props.disabled" />
      </ComboboxInput>
      <InputGroupAddon align="inline-end">
        <ComboboxTrigger
          v-if="showTrigger"
          data-slot="input-group-button"
          class="group/combobox-trigger group-has-data-[slot=combobox-clear]/input-group:hidden"
        />
        <ComboboxClear v-if="showClear" />
      </InputGroupAddon>
      <slot />
    </InputGroup>
  </ComboboxAnchor>
</template>