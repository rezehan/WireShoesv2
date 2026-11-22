<script setup>
import { computed, useAttrs, useSlots } from 'vue'
import { cn } from '@/utils/cn'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  errors: {
    type: Array,
    default: undefined,
  },
})

const attrs = useAttrs()
const slots = useSlots()

const uniqueMessages = computed(() => [
  ...new Set((props.errors ?? []).map((error) => error?.message).filter(Boolean)),
])

const hasContent = computed(() => !!slots.default || uniqueMessages.value.length > 0)
</script>

<template>
  <div
    v-if="hasContent"
    role="alert"
    data-slot="field-error"
    v-bind="{ ...attrs, class: undefined }"
    :class="cn('text-sm font-normal text-red-500 dark:text-red-400', attrs.class)"
  >
    <slot v-if="$slots.default" />
    <template v-else-if="uniqueMessages.length === 1">{{ uniqueMessages[0] }}</template>
    <ul v-else class="ms-4 flex list-disc flex-col gap-1">
      <li v-for="(message, index) in uniqueMessages" :key="index">{{ message }}</li>
    </ul>
  </div>
</template>