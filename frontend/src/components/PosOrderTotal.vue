<template>
  <table class="rr-ruled-table w-full">
    <tr>
      <td class="text-left">
        <span class="rr-tiny-label">
          Sub-Total
        </span>
      </td>
      <td>{{ formatCurrency(subTotal) }}</td>
    </tr>
    <tr v-if="discount">
      <td class="text-left">
        <span class="rr-tiny-label">
          Discount
        </span>
      </td>
      <td>- {{ formatCurrency(discount) }}</td>
    </tr>
    <tr>
      <td class="text-left">
        <span class="rr-tiny-label">
          Tax
        </span>
      </td>
      <td>{{ formatCurrency(tax) }}</td>
    </tr>
    <tr>
      <td></td>
      <td>
        <strong class="block mt-3 leading-none">Total</strong>
        <span class="font-bold text-3xl">
          {{ formatCurrency(total) }}
        </span>
      </td>
    </tr>
  </table>
</template>

<script>
import { formatCurrency } from "@/helpers";
export default {
  props: ["subTotal", "tax", "discount", "total"],

  methods: {
    formatCurrency
  }
};
</script>
