import { mapGetters } from "vuex";
import { getConditionName, getStoreName } from "../helpers";

export default {
  computed: {
    ...mapGetters(["qzReadyToPrint", "qzLabelPrinter"]),

    positiveQuantities() {
      return this.itemForm.quantities.filter(q => {
        return q.quantity_received > 0;
      });
    }
  },

  methods: {
    printQuantityAdjustmentsLabels() {
      for (let quantity of this.positiveQuantities) {
        this.printLabel(quantity.store_id, quantity.quantity_received);
      }
    },

    printLabel(storeId, amount) {
      const labelData = {
        sku: this.itemForm.sku,
        title: this.itemForm.title,
        price: this.itemForm.price,
        conditionName: getConditionName(this.itemForm.condition_id),
        store: getStoreName(storeId),
        quantity: amount
      };

      this.$root.$emit("print-label", labelData);
    },

    readyToPrintLabel() {
      if (this.qzReadyToPrint && this.qzLabelPrinter) {
        return true;
      }

      const msg = this.qzReadyToPrint
        ? "No label printer selected."
        : "Connect QZ Tray and try again.";
      this.$toasted.show(msg, { type: "error" });
      this.$store.dispatch("updateQzPanel", true);

      return false;
    }
  }
};
