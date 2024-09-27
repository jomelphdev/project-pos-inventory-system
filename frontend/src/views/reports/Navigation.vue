<template>
  <div>
    <div class="mb-6 text-xs leading-4 font-medium text-gray-500 uppercase">
      Reports
    </div>
    <router-link :to="{ name: 'reports.daily-sales' }" class="block mb-4"
      >Daily Sales</router-link
    >
    <router-link :to="{ name: 'reports.sales-report' }" class="block mb-4"
      >Sales Report</router-link
    >
    <router-link :to="{ name: 'reports.item-sales' }" class="block mb-4"
      >Item Sales</router-link
    >
    <router-link :to="{ name: 'reports.inventory' }" class="block mb-4"
      >Inventory</router-link
    >
    <router-link
      :to="{ name: 'reports.drawers' }"
      class="block mb-4"
      v-if="stationsVisible.length > 0"
      >Cash Drawers</router-link
    >
    <router-link
      :to="{ name: 'reports.quickbooks' }"
      class="block mb-4"
      v-if="isUsingQuickBooks"
      >QuickBooks</router-link
    >
    <router-link
      :to="{ name: 'reports.consignment' }"
      class="block mb-2"
      v-if="consignors.length > 0"
      >Consignment</router-link
    >
    <router-link
      :to="{ name: 'reports.consignment-invoices' }"
      class="block mb-4 ml-4"
      v-if="consignors.length > 0"
      >- Invoices</router-link
    >
    <router-link :to="{ name: 'reports.gift-card' }" class="block mb-4"
      >Gift Card</router-link
    >
  </div>
</template>

<script>
import { mapGetters } from "vuex";
export default {
  computed: {
    ...mapGetters(["isUsingQuickBooks", "consignors", "stationsVisible"]),
  },
};
</script>
