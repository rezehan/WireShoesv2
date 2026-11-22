<script setup>
import { computed } from 'vue'
import { ComboboxInput, TagsInputInput, useForwardPropsEmits } from 'reka-ui'
import { cn } from '@/utils/cn'

const props = defineProps({
  modelValue: { type: String, default: undefined },
  displayValue: { type: Function, default: undefined },
  autoFocus: { type: Boolean, default: undefined },
  disabled: { type: Boolean, default: undefined },
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
  <ComboboxInput v-bind="forwarded" as-child>
    <TagsInputInput
      data-slot="combobox-chip-input"
      :class="
        cn(
          `min-w-16 flex-1 bg-transparent py-1 text-sm font-normal text-gray-800 outline-none placeholder:text-gray-400 dark:text-white/90 dark:placeholder:text-gray-500`,
          props.class,
        )
      "
    />
  </ComboboxInput>
</template>