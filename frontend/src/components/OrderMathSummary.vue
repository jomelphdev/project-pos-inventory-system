<template>
  <table class="rr-ruled-table">
    <tr>
      <td>
        <span class="rr-tiny-label">
          Sub-Total
        </span>
      </td>
      <td data-test="order-math-subTotal">
        {{ formatCurrency(order.taxable_sub_total) }}
      </td>
    </tr>
    <tr v-if="order.ebt_sub_total > 0">
      <td>
        <span class="rr-tiny-label">
          EBT
        </span>
      </td>
      <td data-test="order-math-ebtSubTotal">
        {{ formatCurrency(order.ebt_sub_total) }}
      </td>
    </tr>
    <tr v-if="order.non_taxed_sub_total > 0">
      <td>
        <span class="rr-tiny-label">
          Non Taxed
        </span>
      </td>
      <td data-test="order-math-nonTaxedSubTotal">
        {{ formatCurrency(order.non_taxed_sub_total) }}
      </td>
    </tr>
    <tr>
      <td>
        <span class="rr-tiny-label">
          Tax
        </span>
      </td>
      <td data-test="order-math-tax">{{ formatCurrency(order.tax) }}</td>
    </tr>
    <tr>
      <td></td>
      <td>
        <strong class="block mt-3 leading-none">Total</strong>
        <span class="font-bold text-3xl" data-test="order-math-total">
          {{ formatCurrency(order.total) }}
        </span>
      </td>
    </tr>
  </table>
</template>

<script>
import { formatCurrency } from "@/helpers.js";

export default {
  props: {
    order: Object
  },

  methods: {
    formatCurrency
  }
};
</script>
