<template>
  <div class="rr-field mb-0 ml-4 mr-2">
    <form @submit.prevent.stop="queryOrder">
      <input
        class="rr-field__input"
        type="text"
        v-model="query"
        placeholder="or Enter a v2.0 Order"
      />
    </form>
  </div>
</template>

<script>
import { EventBus } from "@/event-bus.js";

export default {
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

        this.$store
          .dispatch("getOrderByMongoId", this.query)
          .then(order => {
            this.$toasted.show(
              `V2 Order: ${this.query} was found on the new system as ${order.id}.`,
              { type: "success" }
            );
            EventBus.$emit("order-found", order);
          })
          .finally(() => {
            this.ready = false;
            this.query = "";
          });
      }
    }
  },

  mounted() {
    EventBus.$on("search-pos-orders", () => {
      this.queryOrder();
    });
  }
};
</script>

<style></style>
