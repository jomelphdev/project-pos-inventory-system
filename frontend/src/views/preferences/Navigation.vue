<template>
  <div>
    <div
      class="mb-6 text-xs leading-4 font-medium text-gray-500 uppercase"
      data-test="nav-title"
    >
      Settings
    </div>

    <span>
      <router-link
        :to="{ name: 'preferences.stores' }"
        class="block mb-4"
        data-test="stores-nav"
        >Stores</router-link
      >
      <div class="ml-2">
        <router-link
          :to="{ name: 'preferences.checkoutStations' }"
          class="block mb-4"
          data-test="checkoutStations-nav"
          >- Checkout Stations</router-link
        >
        <router-link
          :to="{ name: 'preferences.classifications' }"
          class="block mb-4"
          data-test="classifications-nav"
          >- Classifications</router-link
        >
        <router-link
          :to="{ name: 'preferences.conditions' }"
          class="block mb-4"
          data-test="conditions-nav"
          >- Conditions</router-link
        >
      </div>
      <router-link
        :to="{ name: 'preferences.employees' }"
        class="block mb-4"
        data-test="employees-nav"
        >Employees</router-link
      >
      <router-link
        :to="{ name: 'preferences.consignors' }"
        class="block mb-4"
        data-test="consignors-nav"
        >Consignors</router-link
      >
      <router-link
        :to="{ name: 'preferences.quickbooks' }"
        class="block mb-4"
        data-test="quickBooks-nav"
        >QuickBooks</router-link
      >
      <router-link
        :to="{ name: 'preferences.site' }"
        class="block mb-4"
        data-test="onlineSite-nav"
        v-if="!organization.slug"
        >Online site</router-link
      >
      <router-link
        :to="{ name: 'preferences.pos' }"
        class="block mb-4"
        data-test="pos-nav"
        >POS</router-link
      >
      <router-link
        :to="{ name: 'preferences.discounts' }"
        class="block mb-4 ml-2"
        data-test="discounts-nav"
        >- Discounts</router-link
      >
      <router-link
        :to="{ name: 'preferences.billing' }"
        class="block mb-4"
        data-test="billing-nav"
        >Billing</router-link
      >
    </span>
  </div>
</template>

<script>
import { mapGetters } from "vuex";

export default {
  computed: {
    ...mapGetters([
      "organization",
      "paymentPartner",
      "classifications_disabled",
      "conditions_disabled",
    ]),
  },
};
</script>
