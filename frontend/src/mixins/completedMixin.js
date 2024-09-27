import { mapGetters } from "vuex";

export default {
  computed: {
    ...mapGetters([
      "stores",
      "classifications",
      "conditions",
      "classifications_disabled",
      "conditions_disabled",
      "discounts",
      "employees",
      "qzReadyToPrint"
    ]),

    completed() {
      return {
        stores: this.stores.length > 0,
        classifications:
          this.classifications_disabled || this.classifications.length > 0,
        conditions: this.conditions_disabled || this.conditions.length > 0
      };
    },

    allCompleted() {
      return Object.values(this.completed).every(Boolean);
    }
  }
};
