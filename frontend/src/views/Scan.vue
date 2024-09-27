<template>
  <div class="container" @click="focusUpcInput">
    <CompleteSetup />

    <div
      class="rr-card rr-card--shadow px-6 py-8 max-w-2xl mx-auto my-12"
      v-if="allCompleted"
    >
      <form @submit.prevent.stop="lookupUpc">
        <div class="rr-field mb-2">
          <label class="rr-field__label">
            Scan or Enter UPC

            <span
              class="rr-field__label-required"
              v-if="!$v.upc.decimal || !$v.upc.minLength || !$v.upc.maxLength"
            >
              Enter a valid UPC code
            </span>
          </label>
          <input
            class="rr-field__input rr-scan__input"
            type="text"
            v-model="upc"
            ref="upc"
            data-test="scanUpc-input"
          />
          <div class="flex justify-between">
            <button
              class="rr-button rr-button--primary-solid mt-4 inline-flex cursor-pointer"
              @click.prevent.stop="lookupUpc"
              :disabled="$v.$invalid"
              data-test="addFromUpc-button"
            >
              Add From UPC
            </button>
            <button
              @click.prevent.stop="$router.push({ name: 'manifest.index' })"
              class="rr-button rr-button--primary mt-4 inline-flex cursor-pointer ml-auto mr-4"
            >
              Add From Manifest
            </button>
            <button
              @click.prevent.stop="addFromScratch"
              class="rr-button rr-button--primary mt-4 inline-flex cursor-pointer"
              data-test="addFromScratch-button"
            >
              Add From Scratch
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { validationMixin } from "vuelidate";
import {
  required,
  decimal,
  minLength,
  maxLength
} from "vuelidate/lib/validators";

import CompleteSetup from "@/components/CompleteSetup";

import completedMixin from "@/mixins/completedMixin.js";

export default {
  name: "Scan",

  data() {
    return {
      upc: null
    };
  },

  components: {
    CompleteSetup
  },

  methods: {
    lookupUpc() {
      this.$router.push({
        name: "items.create",
        query: {
          upc: this.upc
        }
      });
    },
    addFromScratch() {
      this.$router.push({
        name: "items.create"
      });
    },
    focusUpcInput() {
      if (this.$refs.upc) {
        this.$refs.upc.focus();
      }
    }
  },

  mixins: [validationMixin, completedMixin],

  mounted() {
    this.focusUpcInput();
  },

  validations() {
    return {
      upc: {
        required,
        decimal,
        minLength: minLength(12),
        maxLength: maxLength(13)
      }
    };
  },

  metaInfo: {
    title: "Scan / "
  }
};
</script>

<style scoped>
.rr-scan__input {
  font-size: 72px;
}
</style>
