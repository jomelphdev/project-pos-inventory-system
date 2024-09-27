<template>
  <div class="number-tile">
    <div class="number-tile__label" v-if="label">
      {{ label }}
    </div>

    <div class="number-tile__value" v-if="value">
      {{ value }}
    </div>

    <div class="number-tile__body" v-if="$slots.body">
      <slot name="body" />
    </div>
  </div>
</template>

<script>
export default {
  props: {
    label: {
      type: String,
      default: null
    },
    value: {
      type: String,
      default: null
    }
  }
};
</script>

<style lang="scss">
@import "@/assets/scss/components/number-tile.scss";
</style>
