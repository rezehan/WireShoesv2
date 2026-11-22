<script setup>
import { computed, useAttrs } from 'vue'
import { buttonVariants } from '@/components/ui/button'
import { cn } from '@/utils/cn'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  isActive: {
    type: Boolean,
    default: undefined,
  },
  disabled: {
    type: Boolean,
    default: undefined,
  },
  size: {
    type: String,
    default: 'icon',
    validator: (value) =>
      ['default', 'xs', 'sm', 'lg', 'icon', 'icon-xs', 'icon-sm', 'icon-lg'].includes(value),
  },
  variant: {
    type: String,
    default: undefined,
    validator: (value) => ['default', 'outline', 'ghost', 'destructive', 'link'].includes(value),
  },
})

const attrs = useAttrs()

const classes = computed(() =>
  buttonVariants({
    variant: props.isActive ? 'default' : (props.variant ?? 'ghost'),
    size: props.size,
    class: cn(
      props.disabled && 'pointer-events-none cursor-not-allowed opacity-50',
      attrs.class,
    ),
  }),
)

const onClick = (event) => {
  if (props.disabled) {
    event.preventDefault()
    return
  }
  attrs.onClick?.(event)
}
</script>

<template>
  <a
    :aria-current="isActive ? 'page' : undefined"
    :aria-disabled="disabled"
    :tabindex="disabled ? -1 : undefined"
    data-slot="pagination-link"
    :data-active="isActive"
    v-bind="{ ...attrs, class: undefined, onClick: undefined }"
    :class="classes"
    @click="onClick"
  >
    <slot />
  </a>
</template>