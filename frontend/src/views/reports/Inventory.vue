<template>
  <div>
    <div class="flex flex-row justify-between">
      <h1 class="h1" v-text="`Inventory`" />
    </div>

    <template>
      <div class="bg-white shadow-lg rounded-md p-12">
        <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
          <div>
            <div class="rr-field rr-field--select">
              <label class="rr-field__label">
                Report Type
                <span
                  class="rr-field__label-required"
                  v-if="!$v.selectedReport.required"
                >
                  Required
                </span>
              </label>
              <select class="rr-field__input" v-model="selectedReport">
                <option
                  v-for="(value, index) in reportTypes"
                  :key="index"
                  :value="index"
                  v-text="value"
                ></option>
              </select>
            </div>

            <div class="rr-field__radio mr-4 mb-4 inline-block">
              <input
                type="checkbox"
                :id="`sort-by-station`"
                class="rr-field__radio-input"
                :checked="withEmpties"
                @click="withEmpties = !withEmpties"
              />
              <label
                :for="`sort-by-station`"
                class="rr-field__radio-label items-baseline"
              >
                With Empty Quantities
              </label>
            </div>
          </div>
        </div>

        <div class="flex flex-row mt-8">
          <button
            class="rr-button rr-button--lg rr-button--primary"
            :disabled="$v.$invalid"
            v-text="`Download Excel Report`"
            @click="downloadReport()"
          />
        </div>
      </div>
    </template>

    <!-- <template v-else>
      <loading-panel :timer="true">
        <template v-slot:title>
          Generating Report → {{ fileName }}.xlsx
        </template>
        <template v-slot:text>
          Note: Reports can take several minutes to generate.</template
        >
      </loading-panel>
    </template> -->
  </div>
</template>

<script>
import { required } from "vuelidate/lib/validators";
import { mapGetters } from "vuex";

import ReportsMixin from "@/mixins/ReportsMixin";

export default {
  name: "Inventory",

  mixins: [ReportsMixin],

  data() {
    return {
      reportTypes: {
        "all-inventory": "All Inventory"
      },
      selectedReport: "all-inventory",
      withEmpties: false
    };
  },

  computed: {
    ...mapGetters(["storesVisible", "loading_report"])
  },

  methods: {
    downloadReport() {
      this.$store.dispatch("getInventoryReport", {
        stores: this.storesVisible.map(s => s.id),
        with_empty_quantities: this.withEmpties
      });
    }
  },

  validations() {
    return {
      selectedReport: {
        required
      }
    };
  }
};
</script>
