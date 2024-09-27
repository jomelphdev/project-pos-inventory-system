<template>
  <div>
    <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
      <div class="rr-field">
        <label class="rr-field__label">
          Address

          <span
            class="rr-field__label-required"
            v-if="!$v.billingForm.address.required"
          >
            Required
          </span>
        </label>
        <input
          class="rr-field__input"
          type="text"
          v-model="billingForm.address"
          @input="$v.billingForm.address.$touch()"
        />
      </div>
      <div class="rr-field">
        <label class="rr-field__label">
          Phone

          <span
            class="rr-field__label-required"
            v-if="!$v.billingForm.phone.required"
          >
            Required
          </span>
        </label>
        <!-- TODO: v-mask from https://github.com/RetailRight/retail-right-ui-v3/pull/275 -->
        <input
          class="rr-field__input"
          type="text"
          v-model="billingForm.phone"
          @input="$v.billingForm.phone.$touch()"
        />
      </div>
    </div>
    <!-- City / State / Zip -->
    <div class="grid md:grid-cols-3 grid-cols-1 md:gap-x-8 md:gap-y-8">
      <div class="rr-field">
        <label class="rr-field__label">
          City

          <span
            class="rr-field__label-required"
            v-if="!$v.billingForm.city.required"
          >
            Required
          </span>
        </label>
        <input
          class="rr-field__input"
          type="text"
          v-model="billingForm.city"
          @input="$v.billingForm.city.$touch()"
        />
      </div>
      <div class="rr-field">
        <label class="rr-field__label">
          State

          <span
            class="rr-field__label-required"
            v-if="!$v.billingForm.state_id.required"
          >
            Required
          </span>
        </label>
        <select
          class="rr-field__input"
          type="text"
          v-model="billingForm.state_id"
        >
          <option v-for="state of states" :key="state.id" :value="state.id">
            {{ state.name }} | {{ state.abbreviation }}
          </option>
        </select>
      </div>
      <div class="rr-field">
        <label class="rr-field__label">
          Zip

          <span
            class="rr-field__label-required"
            v-if="!$v.billingForm.zip.required"
          >
            Required
          </span>
        </label>
        <input
          class="rr-field__input"
          type="number"
          v-model="billingForm.zip"
          @input="$v.billingForm.zip.$touch()"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { validationMixin } from "vuelidate";
import { required } from "vuelidate/lib/validators";
import { mapGetters } from "vuex";

export default {
  data() {
    return {
      billingForm: {
        address: "",
        city: "",
        state_id: "",
        zip: "",
        phone: "",
        invalid: true
      }
    };
  },

  computed: {
    ...mapGetters(["states"])
  },

  props: {
    form: {
      type: Object
    }
  },

  mixins: [validationMixin],

  methods: {
    hydrateBillingForm() {
      this.billingForm = this.form;
    }
  },

  mounted() {
    this.hydrateBillingForm();
  },

  validations() {
    return {
      billingForm: {
        address: { required },
        city: { required },
        state_id: { required },
        zip: { required },
        phone: { required }
      }
    };
  },

  watch: {
    "$v.$invalid": function() {
      this.billingForm.invalid = this.$v.$invalid;
    },
    billingForm: {
      handler: function() {
        this.$emit("input", this.billingForm);
      },
      deep: true
    }
  }
};
</script>
