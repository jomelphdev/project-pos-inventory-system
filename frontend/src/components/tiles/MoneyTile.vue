<template>
  <div>
    <number-tile :label="label">
      <template v-slot:body>
        <div class="number-tile__value" ref="countTarget">
          0
        </div>
      </template>
    </number-tile>
  </div>
</template>

<script>
import NumberTile from "@/components/tiles/NumberTile.vue";
import { CountUp } from "countup.js";

export default {
  components: {
    NumberTile
  },

  props: {
    amount: {
      type: Number,
      default: 0
    },
    label: {
      type: String,
      default: "Total"
    }
  },

  data() {
    return {
      count: {
        start: 0,
        end: 0
      },
      counter: null
    };
  },

  watch: {
    amount: {
      immediate: true,
      handler: function(newValue) {
        this.count.end = newValue;
        this.refreshCounter();
      }
    }
  },

  methods: {
    refreshCounter() {
      const options = {
        decimalPlaces: 2,
        prefix: "$"
      };
      this.counter = new CountUp(
        this.$refs.countTarget,
        this.count.end,
        options
      );
      this.counter.start();
    }
  },

  mounted() {
    this.refreshCounter();
  },

  beforeDestroy() {
    this.counter = null;
  }
};
</script>
