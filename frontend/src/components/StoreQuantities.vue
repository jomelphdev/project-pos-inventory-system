<template>
  <div v-if="!hide">
    <table class="rr-store-qty w-full">
      <thead>
        <tr class="rr-store-qty__tr">
          <th class="rr-store-qty__th text-left">
            Store
            <span class="rr-field__label-required" v-if="invalid">
              Required
            </span>
          </th>
          <th class="rr-store-qty__th text-right">Quantity</th>
          <th class="rr-store-qty__th text-right">Sales</th>
        </tr>
      </thead>
      <tbody data-test="storeQuantities">
        <tr
          class="rr-store-qty__tr cursor-pointer"
          v-for="(quantity, index) in quantities"
          :key="quantity.id"
          @click.stop="adjustQuantity(quantity)"
          :data-test="`storeQuantity-${index}`"
        >
          <td class="rr-store-qty__td flex items-center">
            <i
              class="rr-store-qty__icon svg svg-update-qty mr-4 shadow rounded-md overflow-hidden"
            ></i>
            <span>
              {{ quantity.store_name }}
            </span>
          </td>
          <td class="rr-store-qty__td text-right">
            <span :class="quantityClass(quantity.quantity)">
              {{ quantity.quantity }}
            </span>
            <template v-if="quantity.quantity_received != 0">
              <span
                class="ml-2"
                :class="adjustQuantityClass(quantity.quantity_received)"
                data-test="quantityReceived-indicator"
              >
                {{ quantity.quantity_received | plusMinus }}
              </span>
            </template>
          </td>
          <td
            class="rr-store-qty__td text-right"
            :class="quantityClass(quantity.quantity_sold)"
            v-text="quantity.quantity_sold"
          />
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { mapGetters } from "vuex";

export default {
  name: "StoreQuantities",

  props: {
    existingQuantities: {
      type: Array,
      default: () => []
    },
    hide: {
      type: Boolean,
      default: false
    },
    invalid: {
      type: Boolean,
      default: false
    }
  },

  data() {
    return {
      quantities: []
    };
  },

  computed: {
    ...mapGetters(["stores", "currentUser"]),
    validQuantities() {
      return this.quantities
        .filter(q => q.quantity_received != 0)
        .map(q => {
          let message = q.message;

          if (q.quantity_received > 0 && !q.existBefore) {
            message = "Quantity Created";
          } else if (q.quantity_received > 0) {
            message = "Quantity Added";
          }

          if (message == "") {
            return this.$toasted.show(
              "Message required to create a quantity.",
              { type: "error" }
            );
          }

          return {
            store_id: q.store_id,
            created_by: this.currentUser.id,
            quantity_received: q.quantity_received,
            message: message
          };
        });
    }
  },

  watch: {
    validQuantities() {
      this.assignCypress();
    }
  },

  mounted() {
    this.initializeQuantities();

    this.$root.$on("quantity-adjustment", payload => {
      let quantity = this.quantities.find(q => q.store_id === payload.id);
      const existingQuantity = this.existingQuantities.find(
        q => q.store_id === payload.id
      );

      quantity.quantity_received = payload.amount;
      quantity.message = payload.message ? payload.message : "";
      quantity.existBefore = existingQuantity ? true : false;

      this.$emit("set-quantity-changes", this.validQuantities);
    });

    this.$root.$on("refresh-item", () => {
      this.initializeQuantities();
    });

    this.assignCypress();
  },

  methods: {
    initializeQuantities() {
      this.quantities = this.stores.map(s => {
        const eq = this.existingQuantities
          ? this.existingQuantities.find(q => q.store_id == s.id)
          : null;
        const existingQuantity = eq ? eq.quantity : 0;
        const existingSales = eq ? eq.quantity_sold : 0;

        return {
          store_id: s.id,
          store_name: s.name,
          quantity: existingQuantity,
          quantity_received: 0,
          quantity_sold: existingSales
        };
      });
    },

    quantityClass(quantity) {
      let theQuantity = parseInt(quantity);

      if (theQuantity === 0) {
        return "rr-store-qty__zero";
      }
    },

    adjustQuantityClass(quantity) {
      let theQuantity = parseInt(quantity);

      if (theQuantity === 0) {
        return "rr-store-qty__zero";
      }

      return theQuantity > 0
        ? "rr-store-qty__positive"
        : "rr-store-qty__negative";
    },

    adjustQuantity(quantity) {
      this.$root.$emit("adjust-quantity", quantity, quantity.quantity_received);
    },

    assignCypress() {
      if (window.Cypress) {
        window.StoreQuantities = {
          stores: this.stores,
          quantities: this.validQuantities
        };
      }
    }
  },

  filters: {
    plusMinus(value) {
      let valueString = value.toString();
      if (value > 0) {
        valueString = "+" + valueString;
      }
      return valueString;
    }
  }
};
</script>

<style lang="scss">
@import "@/assets/scss/components/store-qty.scss";
</style>
