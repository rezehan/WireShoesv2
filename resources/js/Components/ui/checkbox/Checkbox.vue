<script setup>
import { computed } from 'vue'
import { CheckboxIndicator, CheckboxRoot } from 'reka-ui'
import { CheckIcon } from '@/icons/layout'
import { cn } from '@/utils/cn'

const props = defineProps({
  defaultValue: {
    type: [Boolean, String],
    default: undefined,
  },
  modelValue: {
    type: [Boolean, String],
    default: undefined,
  },
  disabled: {
    type: Boolean,
    default: undefined,
  },
  value: {
    type: [String, Number, Object],
    default: undefined,
  },
  id: {
    type: String,
    default: undefined,
  },
  name: {
    type: String,
    default: undefined,
  },
  required: {
    type: Boolean,
    default: undefined,
  },
  as: {
    type: [String, Object],
    default: undefined,
  },
  asChild: {
    type: Boolean,
    default: false,
  },
  class: {
    type: [String, Array, Object],
    default: undefined,
  },
  className: {
    type: [String, Array, Object],
    default: undefined,
  },
  checked: {
    type: Boolean,
    default: undefined,
  },
  ariaLabel: {
    type: String,
    default: undefined,
  },
})

const emits = defineEmits(['update:modelValue', 'change'])

const forwarded = computed(() => {
  const { class: _class, className: _className, checked, ariaLabel, modelValue, ...rest } = props
  return {
    ...rest,
    modelValue: modelValue ?? checked,
    'aria-label': ariaLabel,
  }
})

const onUpdate = (value) => {
  emits('update:modelValue', value)
  emits('change', value === true)
}
</script>

<template>
  <CheckboxRoot
    data-slot="checkbox"
    v-bind="forwarded"
    :class="
      cn(
        `peer relative flex size-4 shrink-0 cursor-pointer items-center justify-center rounded-md border border-gray-300 bg-white shadow-theme-xs outline-hidden transition-colors group-has-focus-visible/field-label:ring-0 group-has-disabled/field:opacity-50 after:absolute after:-inset-x-3 after:-inset-y-2 hover:border-brand-500 focus-visible:border-brand-500 focus-visible:ring-3 focus-visible:ring-brand-500/20 disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-100/50 disabled:shadow-none aria-invalid:border-red-500 aria-invalid:ring-3 aria-invalid:ring-red-500/20 data-checked:border-brand-500 data-checked:bg-brand-500 data-checked:text-white data-checked:hover:border-brand-600 data-checked:hover:bg-brand-600 aria-invalid:data-checked:border-red-500 aria-invalid:data-checked:bg-red-500 data-disabled:data-checked:border-gray-200 data-disabled:data-checked:bg-transparent data-disabled:data-checked:text-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-brand-400 dark:focus-visible:border-brand-400 dark:focus-visible:ring-brand-500/30 dark:disabled:border-gray-800 dark:disabled:bg-gray-800/50 dark:aria-invalid:border-red-500/50 dark:aria-invalid:ring-red-500/40 dark:data-checked:border-brand-500 dark:data-checked:bg-brand-500 dark:data-checked:hover:border-brand-600 dark:data-checked:hover:bg-brand-600 dark:data-disabled:data-checked:border-gray-800 dark:data-disabled:data-checked:text-gray-600`,
        props.class,
        props.className,
      )
    "
    @update:model-value="onUpdate"
  >
    <CheckboxIndicator
      data-slot="checkbox-indicator"
      class="grid place-content-center text-current transition-none [&>svg]:size-3"
    >
      <CheckIcon />
    </CheckboxIndicator>
  </CheckboxRoot>
</template>