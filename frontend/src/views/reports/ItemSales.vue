<template>
  <div>
    <div class="flex flex-row justify-between">
      <h1 class="h1" v-text="`Item Sales Report`" />
    </div>

    <div class="bg-white shadow-lg rounded-md p-12">
      <DateRange v-model="selectedDate" :extraRanges="extraRange" />

      <div class="flex flex-row mt-8">
        <button
          class="rr-button rr-button--lg rr-button--primary"
          :disabled="$v.$invalid"
          v-text="`Download Excel Report`"
          @click="downloadReport()"
        />
      </div>
    </div>

    <ReportDirectory :report_type="`item-sales`" />
  </div>
</template>

<script>
import DateRange from "@/components/DateRange";

import { required } from "vuelidate/lib/validators";

import ReportsMixin from "@/mixins/ReportsMixin";
import moment from "moment";

export default {
  components: { DateRange },

  mixins: [ReportsMixin],

  data() {
    return {
      selectedDate: null,
      extraRange: [
        {
          label: "Today",
          start: moment().format(),
          end: null
        }
      ]
    };
  },

  methods: {
    downloadReport() {
      this.$store.dispatch("getItemSalesReport", {
        start_date: this.selectedDate.start,
        end_date: this.selectedDate.end
      });
    }
  },

  validations() {
    return {
      selectedDate: {
        required
      }
    };
  }
};
</script>

<style></style>
