<template>
  <div>
    {{ clock }}
  </div>
</template>

<script>
import moment from "moment";
export default {
  data() {
    return {
      timer: moment(60 * 0 * 1000)
    };
  },

  computed: {
    clock() {
      return this.timer.format("mm:ss");
    }
  },

  mounted: function() {
    setInterval(() => {
      this.timer = moment(this.timer.add(1, "seconds"));
    }, 1000);
  }
};
</script>
