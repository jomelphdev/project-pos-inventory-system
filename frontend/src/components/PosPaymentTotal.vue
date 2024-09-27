<template>
  <table class="rr-ruled-table w-full">
    <tr v-if="cash > 0">
      <td class="text-left">
        <span class="rr-tiny-label">
          Cash
        </span>
      </td>
      <td>{{ formatCurrency(cash) }}</td>
    </tr>
    <tr v-if="card > 0">
      <td class="text-left">
        <span class="rr-tiny-label">
          Card
        </span>
      </td>
      <td>{{ formatCurrency(card) }}</td>
    </tr>
    <tr v-if="ebt > 0">
      <td class="text-left">
        <span class="rr-tiny-label">
          EBT
        </span>
      </td>
      <td>{{ formatCurrency(ebt) }}</td>
    </tr>
    <tr>
      <td></td>
      <td>
        <strong class="block mt-3 leading-none">Change</strong>
        <span class="font-bold text-3xl">
          {{ formatCurrency(change) }}
        </span>
      </td>
    </tr>
  </table>
</template>

<script>
import { formatCurrency } from "@/helpers";
export default {
  props: ["cash", "card", "ebt", "change"],

  methods: {
    formatCurrency
  }
};
</script>
