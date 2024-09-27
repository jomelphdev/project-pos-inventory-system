<template>
  <div>
    <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
      <!-- Date Range -->
      <div class="rr-field rr-field--select">
        <label class="rr-field__label"> Date Range </label>
        <select class="rr-field__input" v-model="selectedDate">
          <option
            v-for="range in dates"
            :key="range.label"
            :value="range"
            v-text="range.label"
          ></option>
        </select>
      </div>
    </div>

    <div
      class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8"
      v-if="selectedDate && selectedDate.label === 'Custom'"
    >
      <DatePicker
        @date-selected="date => (selectedDate.start = date)"
        :label="`Start Date`"
      />
      <DatePicker
        @date-selected="date => (selectedDate.end = date)"
        :label="`End Date`"
      />
    </div>

    <div class="mb-8 text-sm" v-if="dateRangeLength > 15">
      <strong>Note:</strong> Report periods more than 15 days may not generate
      due to browser wait-time limits.
    </div>
  </div>
</template>

<script>
import moment from "moment";

import DatePicker from "@/components/DatePicker.vue";

export default {
  name: "DateRange",

  props: {
    value: Object,
    /*
    
    extraRanges: Array[
      {
        label: String,
        start: String,
        end: String,
        priority: Number|null
      }
    ]

    */
    extraRanges: Array
  },

  components: { DatePicker },

  mounted() {
    this.dates = this.generateDateRanges();

    if (this.extraRanges && this.extraRanges.length > 0) {
      for (let date of this.extraRanges) {
        let priority = date.priority ? date.priority : 0;
        this.dates.splice(priority, 0, date);
      }
    }

    this.selectedDate = this.dates[0];
  },

  data() {
    return {
      selectedDate: null,
      dates: []
    };
  },

  computed: {
    dateRangeLength() {
      if (!this.selectedDate) return 0;

      return moment(this.selectedDate.end).diff(
        this.selectedDate.start,
        "days"
      );
    }
  },

  watch: {
    selectedDate: {
      handler(range) {
        this.$emit("input", range);
      },
      deep: true
    }
  },

  methods: {
    generateDateRanges() {
      const days = [];
      const now = moment().format();
      const lastMonth = moment()
        .subtract(1, "month")
        .startOf("month");
      const lastMonthLabel = `Last Month (${lastMonth.format("MMMM")})`;
      const startOfMonth = moment().startOf("month");
      const daysSinceStartOfMonth = moment().diff(startOfMonth, "days");
      const monthToDateLabel = `Month to Date (${daysSinceStartOfMonth} days)`;

      days.push(
        {
          label: "Last 7 days",
          start: moment()
            .subtract(7, "days")
            .startOf("day")
            .format(),
          end: now
        },
        {
          label: "Last 14 days",
          start: moment()
            .subtract(14, "days")
            .startOf("day")
            .format(),
          end: now
        },
        {
          label: "Last 30 days",
          start: moment()
            .subtract(30, "days")
            .startOf("day")
            .format(),
          end: now
        },
        {
          label: monthToDateLabel,
          start: moment()
            .startOf("month")
            .format(),
          end: now
        },
        {
          label: lastMonthLabel,
          start: lastMonth.format(),
          end: moment()
            .subtract(1, "month")
            .endOf("month")
            .format()
        },
        {
          label: "Custom",
          start: null,
          end: null
        }
      );

      return days;
    }
  }
};
</script>
