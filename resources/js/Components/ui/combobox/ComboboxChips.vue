<script setup>
import { computed } from 'vue'
import { TagsInputRoot, useForwardPropsEmits } from 'reka-ui'
import { cn } from '@/utils/cn'

const props = defineProps({
  modelValue: { type: Array, default: undefined },
  defaultValue: { type: Array, default: undefined },
  addOnPaste: { type: Boolean, default: undefined },
  addOnTab: { type: Boolean, default: undefined },
  addOnBlur: { type: Boolean, default: undefined },
  duplicate: { type: Boolean, default: undefined },
  disabled: { type: Boolean, default: undefined },
  delimiter: { type: [String, RegExp], default: undefined },
  dir: { type: String, default: undefined },
  max: { type: Number, default: undefined },
  id: { type: String, default: undefined },
  convertValue: { type: Function, default: undefined },
  displayValue: { type: Function, default: undefined },
  name: { type: String, default: undefined },
  required: { type: Boolean, default: undefined },
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
  class: { type: [String, Array, Object], default: undefined },
})

const emits = defineEmits(['update:modelValue', 'invalid', 'addTag', 'removeTag'])

const delegated = computed(() => {
  const { class: _class, ...rest } = props
  return rest
})
const forwarded = useForwardPropsEmits(delegated, emits)
</script>

<template>
  <TagsInputRoot
    data-slot="combobox-chips"
    v-bind="forwarded"
    :class="
      cn(
        `flex min-h-11 w-full min-w-0 flex-wrap items-center gap-1.5 rounded-[10px] border border-gray-300 bg-white py-1.5 ps-3 pe-3 text-sm font-normal text-gray-800 shadow-theme-xs outline-hidden transition-colors focus-within:border-brand-300 focus-within:ring-3 focus-within:ring-brand-500/20 hover:border-gray-400 has-disabled:cursor-not-allowed has-disabled:border-gray-200 has-disabled:bg-gray-100/50 has-disabled:text-gray-400 has-disabled:shadow-none has-aria-invalid:border-red-500 has-aria-invalid:ring-3 has-aria-invalid:ring-red-500/20 focus-within:has-aria-invalid:border-red-500 focus-within:has-aria-invalid:ring-red-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus-within:border-brand-500/40 dark:focus-within:ring-brand-500/20 dark:hover:border-gray-600 dark:has-disabled:border-gray-800 dark:has-disabled:bg-gray-800/50 dark:has-disabled:text-gray-500 dark:has-aria-invalid:border-red-500/50 dark:has-aria-invalid:ring-red-500/40 dark:focus-within:has-aria-invalid:border-red-500/50 dark:focus-within:has-aria-invalid:ring-red-500/40`,
        props.class,
      )
    "
  >
    <slot />
  </TagsInputRoot>
</template>