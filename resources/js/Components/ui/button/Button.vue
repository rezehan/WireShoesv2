<script setup>
import { computed, useAttrs } from 'vue'
import { Primitive } from 'reka-ui'
import { buttonVariants } from './variants'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  as: {
    type: [String, Object],
    default: 'button',
  },
  asChild: {
    type: Boolean,
    default: false,
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'outline', 'ghost', 'destructive', 'link'].includes(value),
  },
  size: {
    type: String,
    default: 'default',
    validator: (value) =>
      ['default', 'xs', 'sm', 'lg', 'icon', 'icon-xs', 'icon-sm', 'icon-lg'].includes(value),
  },
  type: {
    type: String,
    default: 'button',
    validator: (value) => ['button', 'submit', 'reset'].includes(value),
  },
})

const attrs = useAttrs()

const classes = computed(() =>
  buttonVariants({
    variant: props.variant,
    size: props.size,
    class: attrs.class,
  }),
)
</script>

<template>
  <Primitive
    data-slot="button"
    v-bind="{ ...attrs, class: undefined }"
    :as="as"
    :as-child="asChild"
    :type="as === 'button' ? type : undefined"
    :class="classes"
  >
    <slot />
  </Primitive>
</template>