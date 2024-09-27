<template>
  <tr
    class="rr-table__tr--hover"
    :class="{ 'rr-table__tr--highlight': highlight }"
    @click="$emit('click')"
  >
    <slot></slot>
  </tr>
</template>

<script>
export default {
  props: {
    highlight: {
      type: Boolean,
      default: false,
    },
  },
};
</script>
