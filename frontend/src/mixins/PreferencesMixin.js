import { required, decimal } from "vuelidate/lib/validators";
import { mapGetters } from "vuex";

import { validationMixin } from "vuelidate";
import completedMixin from "@/mixins/completedMixin.js";

export default {
  mixins: [validationMixin, completedMixin],

  data() {
    return {
      preference: {
        singular: this.$options.name.slice(0, -1),
        plural: this.$options.name
      }
    };
  },

  computed: {
    ...mapGetters(["stores"]),

    preferenceUpdate() {
      return {
        type: this.preference.plural.replace(" ", "_").toLowerCase(),
        update: this.preferenceForm.dirtyData
      };
    }
  },

  methods: {
    // Used to update and create Classification, Condition, Discounts, and Stores
    updatePreference() {
      if (!this.preferenceForm.isDirty) {
        return this.closeForm();
      }

      const payload = this.preferenceUpdate;
      payload.update.id = this.preferenceForm.id;

      this.$store.dispatch("updatePreference", payload).then(() => {
        this.closeForm();

        try {
          this.setDefaultOptions();
        } catch (err) {
          return;
        }
      });
    },

    createDefaultPreferences(type) {
      this.$store.dispatch("seedDefaultPreferences", type).then(() => {
        this.closeForm();
      });
    },

    createPreference() {
      this.editMode = false;
      this.$refs.PreferenceForm.openModal();
    },

    editPreference(preference) {
      this.editMode = true;
      this.$refs.PreferenceForm.openModal();

      let preferenceCopy = { ...preference };
      preferenceCopy.discount *= 100;
      this.preferenceForm.update(preferenceCopy);
    },

    closeForm() {
      this.editMode = false;
      this.$refs.PreferenceForm.closeModal();
      this.preferenceForm.initialState();
    },

    statusText(hidden) {
      return hidden ? "Hidden" : "Active";
    },

    modalTitle() {
      return this.editMode
        ? `Edit ${this.preference.singular}`
        : `Create ${this.preference.singular}`;
    },

    modalButton() {
      return this.editMode
        ? `Save Changes`
        : `Create ${this.preference.singular}`;
    }
  },

  validations() {
    return {
      preferenceForm: {
        name: { required },
        discount: { required, decimal }
      }
    };
  }
};
