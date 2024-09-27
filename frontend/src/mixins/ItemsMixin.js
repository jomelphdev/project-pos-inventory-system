import ItemLabelMixin from "@/mixins/ItemLabelMixin.js";
import { validationMixin } from "vuelidate";

import Modal from "@/components/Modal";

export default {
  mixins: [ItemLabelMixin, validationMixin],

  components: { Modal },

  methods: {
    handleSavedItem(printLabels, toPage) {
      if (!printLabels) {
        return this.$router.push({ name: toPage });
      }

      this.printQuantityAdjustmentsLabels();

      this.$root.$on("printed", () => {
        this.$root.$off("printed");

        this.$router.push({ name: toPage });
      });
    }
  }
};
