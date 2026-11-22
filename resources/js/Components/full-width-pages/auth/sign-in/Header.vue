<script setup>
import { cn } from '@/utils'

defineProps({
  title: {
    type: String,
    default: 'Sign In',
  },
  description: {
    type: String,
    default: 'Enter your email and password to sign in!',
  },
  class: {
    type: String,
    default: undefined,
  },
})
</script>

<template>
  <div :class="cn('mb-5 sm:mb-8', $props.class)">
    <h1 class="mb-2 text-title-sm font-semibold text-gray-800 dark:text-white/90">
      {{ title }}
    </h1>
    <p class="text-sm text-gray-500 dark:text-gray-400">
      {{ description }}
    </p>
  </div>
</template>