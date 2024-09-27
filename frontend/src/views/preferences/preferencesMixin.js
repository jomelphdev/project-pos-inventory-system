import API from "@/api";

export default {
  methods: {
    // Used to update Classification, Condition, and Discounts
    updatePreference() {
      API.updatePreferences(
        this.preference.plural.toLowerCase(),
        this.preferenceForm
      )
        .then((response) => {
          const data = response.data;
          if (data.success) {
            this.$toasted.show(`${this.preference.singular} Updated`, {
              type: "success",
            });
            this.$store.dispatch("getPreferences"); // Refresh the preference table
          } else {
            throw Error;
          }
        })
        .catch(() => {
          this.$toasted.show(
            `There was an error updating this ${this.preference.singular}.`,
            { type: "error" }
          );
        })
        .finally(() => {
          this.closeForm();
        });
    },
  },
};
