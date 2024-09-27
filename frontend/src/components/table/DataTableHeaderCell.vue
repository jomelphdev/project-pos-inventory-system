<template>
  <th class="rr-table__th" :class="{ 'text-right': right }" :colspan="colspan">
    <slot></slot>
  </th>
</template>

<script>
export default {
  props: {
    right: {
      type: Boolean,
      default: false
    },
    colspan: {
      type: String,
      default: "1"
    }
  }
};
</script>
