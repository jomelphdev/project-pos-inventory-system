<template>
  <div>
    <number-tile :label="`Idle Timer`">
      <template v-slot:body>
        <div class="number-tile__value">
          <stop-watch />
        </div>
      </template>
    </number-tile>
  </div>
</template>

<script>
import StopWatch from "@/components/StopWatch.vue";
import NumberTile from "@/components/tiles/NumberTile.vue";

export default {
  components: {
    StopWatch,
    NumberTile
  }
};
</script>
