<template>
  <div class="rr-field rr-field--select relative">
    <label class="rr-field__label">
      {{ label }}

      <span class="rr-field__label-required" v-if="required"> Required </span>
    </label>
    <div
      type="text"
      class="rr-field__input cursor-pointer hover:outline-none hover:shadow-outline"
      @click="togglePicker"
    >
      {{ date | dateFormat }}
    </div>
    <div v-show="visible" :class="[pickerContainerClasses, 'z-50']">
      <v-date-picker
        v-model="date"
        :min-date="minDate"
        :max-date="maxDate"
        :mode="mode"
        @dayclick="selectDate"
        :minute-increment="5"
      />
    </div>
  </div>
</template>

<script>
import moment from "moment";

export default {
  props: {
    label: {
      type: String,
      default: "Date",
    },
    default: Date,
    minDate: {
      type: Date,
      default: null,
    },
    maxDate: {
      type: Date,
      default: () => new Date(),
    },
    mode: {
      type: String,
      default: "date",
    },
    pickerContainerClasses: {
      type: String,
      default: "absolute top-0 right-0",
    },
    immediate: {
      type: Boolean,
      default: true,
    },
    required: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      today: new Date(),
      date: null,
      visible: false,
    };
  },

  watch: {
    date: {
      handler(date) {
        if (!date) {
          return this.$emit("date-selected", null);
        } else if (this.mode === "date") {
          date.setHours(this.today.getHours(), this.today.getMinutes());
        }

        if (this.immediate) this.$emit("date-selected", date);
      },
      immediate: true,
    },
  },

  mounted() {
    if (this.default) this.date = this.default;
  },

  methods: {
    togglePicker() {
      this.visible = !this.visible;
    },

    selectDate(date) {
      if (!date.isDisabled && this.mode === "date") {
        this.togglePicker();
      } else if (!date.isDisabled && this.mode === "dateTime") {
        // this.togglePicker();
      }
    },
  },

  filters: {
    dateFormat(date) {
      if (!date) return "Select a Date";

      return moment(date).startOf("day").format("MMMM Do YYYY");
    },
  },
};
</script>

<style lang="scss">
@import "@/assets/scss/components/date-picker.scss";
</style>
