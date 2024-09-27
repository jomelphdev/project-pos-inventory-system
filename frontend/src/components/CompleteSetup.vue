<template>
  <div class="max-w-2xl mx-auto" v-if="!allCompleted">
    <blank-state v-if="true">
      <template v-slot:body>
        <div class="max-w-xl mx-auto text-center">
          <CubeTransparentIcon size="36" class="text-blue-600 mb-2 mx-auto" />
          <h2 class="h2">
            Complete Setup
          </h2>
          <p>
            Please complete the following steps before processing/selling items.
          </p>
          <div
            class="mt-8 flex flex-col space-y-2 justify-center max-w-xs mx-auto"
          >
            <router-link
              :to="{ name: 'preferences.stores' }"
              :class="setupButtonClass(completed.stores)"
              >1. Create Store(s)</router-link
            >
            <router-link
              :to="getRedirectObjectFor('classifications')"
              :class="setupButtonClass(completed.classifications)"
              >2. Create Classifications</router-link
            >
            <router-link
              :to="getRedirectObjectFor('conditions')"
              :class="setupButtonClass(completed.conditions)"
              >3. Create Conditions</router-link
            >
            <a
              href="https://help.retailright.app/#/printer-setup"
              :class="setupButtonClass(completed.printing)"
              target="_blank"
              >4. Setup & Connect Printer(s) (Optional/Recommened)</a
            >
            <router-link
              :to="getRedirectObjectFor('discounts')"
              :class="setupButtonClass(completed.discounts)"
              >5. Create Discounts (Optional)</router-link
            >
            <router-link
              :to="{ name: 'preferences.employees' }"
              :class="setupButtonClass(completed.employees)"
              >6. Create Employees (Optional)</router-link
            >
          </div>
          <div
            class="mt-8 flex justify-center"
            v-if="
              completed.stores &&
                completed.classifications &&
                completed.conditions &&
                completed.printing
            "
          >
            <button class="rr-button rr-button--lg rr-button--primary">
              I have completed these steps
            </button>
          </div>
        </div>
      </template>
    </blank-state>
  </div>
</template>

<script>
import BlankState from "@/components/BlankState";
import { CubeTransparentIcon } from "@vue-hero-icons/outline";
import completedMixin from "@/mixins/completedMixin.js";

export default {
  mixins: [completedMixin],

  components: {
    BlankState,
    CubeTransparentIcon
  },

  methods: {
    setupButtonClass(status) {
      return status
        ? "rr-button opacity-50 cursor-not-allowed line-through"
        : "rr-button rr-button--primary";
    },
    getRedirectObjectFor(subject) {
      if (this.stores.length == 0) {
        return { name: "preferences.stores" };
      }

      return {
        name: `preferences.${subject}`,
        query: { store_id: this.stores.length > 0 ? this.stores[0].id : null }
      };
    }
  }
};
</script>
