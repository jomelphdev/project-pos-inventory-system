<template>
  <div class="rr-field mb-0 flex-1">
    <form @submit.prevent="queryOrder">
      <input
        class="rr-field__input"
        type="text"
        v-model="query"
        placeholder="Scan or Enter Order ID (Bottom of Receipt)"
        ref="orderSearchInput"
        data-test="order-search-input"
      />
    </form>
  </div>
</template>

<script>
import { mapOrderItems } from "@/helpers";

export default {
  props: {
    getDataForReturn: Boolean
  },

  data() {
    return {
      query: "",
      ready: true
    };
  },

  methods: {
    queryOrder() {
      // Checks if enter was pressed or if the query is length of a SKU
      // TODO: Add vuelidate validation
      if (this.ready && this.query != "") {
        this.ready = false;
        let route = this.getDataForReturn ? "getOrderForReturn" : "getOrder";

        this.$store
          .dispatch(route, this.query)
          .then(order => {
            this.$emit("order-found", order);
          })
          .finally(() => {
            this.query = "";
            this.ready = true;
          });
      }
    },

    focusInput() {
      if (this.$refs.orderSearchInput) {
        this.$refs.orderSearchInput.focus();
      }
    },

    mapOrderItems
  },

  mounted() {
    this.focusInput();
    if (this.$route.query.orderId) {
      this.query = this.$route.query.orderId;
      this.queryOrder();
    }

    this.$root.$on("search-pos-orders", () => {
      this.queryOrder();
    });
  }
};
</script>

<style></style>
