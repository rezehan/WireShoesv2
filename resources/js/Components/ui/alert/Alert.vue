<script setup>
import { computed, useAttrs } from 'vue'
import { alertVariants } from './variants'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'success', 'warning', 'destructive', 'info'].includes(value),
  },
})

const attrs = useAttrs()

const classes = computed(() => alertVariants({ variant: props.variant, class: attrs.class }))
</script>

<template>
  <div data-slot="alert" role="alert" v-bind="{ ...attrs, class: undefined }" :class="classes">
    <slot />
  </div>
</template>