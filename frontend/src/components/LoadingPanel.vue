<template>
  <div class="loading-panel">
    <div class="loading-panel__title" v-if="$slots.title">
      <slot name="title" />
    </div>

    <div class="loading-panel__text" v-if="$slots.text">
      <slot name="text" />

      <div v-if="timer" class="mt-2 text-lg font-bold text-center">
        <stop-watch />
      </div>
    </div>
  </div>
</template>

<script>
import StopWatch from "@/components/StopWatch";

export default {
  name: "LoadingPanel",

  props: {
    timer: {
      type: Boolean,
      default: false
    }
  },

  components: {
    StopWatch
  }
};
</script>

<style lang="scss">
@import "@/assets/scss/components/loading-panel.scss";
</style>
