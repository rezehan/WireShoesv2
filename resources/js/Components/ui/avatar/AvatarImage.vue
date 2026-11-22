<script setup>
import { computed } from 'vue'
import { AvatarImage, useForwardPropsEmits } from 'reka-ui'
import { cn } from '@/utils/cn'

const props = defineProps({
  src: {
    type: String,
    required: true,
  },
  referrerPolicy: {
    type: String,
    default: undefined,
  },
  crossOrigin: {
    type: String,
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
})

const emits = defineEmits(['loadingStatusChange'])

const delegated = computed(() => {
  const { class: _class, ...rest } = props
  return rest
})
const forwarded = useForwardPropsEmits(delegated, emits)
</script>

<template>
  <AvatarImage
    data-slot="avatar-image"
    v-bind="forwarded"
    :class="cn(`aspect-square size-full rounded-full object-cover`, props.class)"
  >
  </AvatarImage>
</template>