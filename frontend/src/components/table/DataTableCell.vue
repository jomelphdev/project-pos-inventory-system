<template>
  <td :class="{ 'rr-table__td': !disableDefaultClass }" :colspan="colspan">
    <div class="flex flex-col">
      <div
        class="text-sm leading-5 font-medium text-gray-900"
        :class="{ 'text-right': right }"
      >
        <slot></slot>
      </div>
    </div>
  </td>
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
    },
    disableDefaultClass: {
      type: Boolean,
      default: false
    }
  }
};
</script>
