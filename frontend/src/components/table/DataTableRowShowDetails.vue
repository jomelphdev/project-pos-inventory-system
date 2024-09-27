<template>
  <tr class="rr-table__tr cursor-pointer">
    <td class="rr-table__td" :colspan="colspan">
      <div class="flex flex-col">
        <div
          class="text-sm leading-5 font-medium text-blue-700 hover:text-gray-900 flex justify-center"
        >
          <DocumentReportIcon size="20" class="mr-1 self-center" />
          <slot></slot>
        </div>
      </div>
    </td>
  </tr>
</template>

<script>
import { DocumentReportIcon } from "@vue-hero-icons/outline";

export default {
  props: {
    colspan: {
      type: Number,
      default: 8
    }
  },

  components: {
    DocumentReportIcon
  }
};
</script>
