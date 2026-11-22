<script setup>
import { computed } from 'vue'

const props = defineProps({
  videoId: {
    type: String,
    required: true,
  },
  aspectRatio: {
    type: String,
    default: '16:9',
    validator: (value) => ['16:9', '4:3', '21:9', '1:1'].includes(value),
  },
  title: {
    type: String,
    default: 'YouTube video',
  },
  class: {
    type: String,
    default: '',
  },
})

const aspectRatioClass = computed(
  () =>
    ({
      '16:9': 'aspect-video',
      '4:3': 'aspect-4/3',
      '21:9': 'aspect-21/9',
      '1:1': 'aspect-square',
    })[props.aspectRatio],
)
</script>

<template>
  <div :class="`overflow-hidden rounded-lg ${aspectRatioClass} ${props.class}`">
    <iframe
      :src="`https://www.youtube.com/embed/${videoId}`"
      :title="title"
      frameborder="0"
      allow="
        accelerometer;
        autoplay;
        clipboard-write;
        encrypted-media;
        gyroscope;
        picture-in-picture;
      "
      allowfullscreen
      class="h-full w-full"
    ></iframe>
  </div>
</template>