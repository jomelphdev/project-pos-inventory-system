import { mapGetters } from "vuex";

export default {
  computed: {
    ...mapGetters(["qzReadyToPrint", "qzReceiptPrinter", "posStore"]),

    readyToPrintReceipt() {
      if (this.qzReadyToPrint && this.qzReceiptPrinter) {
        return true;
      }

      return false;
    },

    receiptData() {
      let receiptData = {
        receiptOptions: Object.assign(
          {
            address: this.posStore.address,
            city: this.posStore.city,
            state: this.posStore.state.name,
            zipcode: this.posStore.zip,
            phone: this.posStore.phone
          },
          this.posStore.receipt_option
        )
      };

      receiptData.items = this.validItems.reverse();

      return receiptData;
    }
  },

  methods: {
    getReadyToPrint() {
      const msg = !this.qzReadyToPrint
        ? "QZ Tray is not connected."
        : "You do not currently have a receipt printer selected, select one and try again.";

      this.$toasted.show(msg, { type: "error" });
      this.$store.dispatch("updateQzPanel", true);
    }
  }
};
