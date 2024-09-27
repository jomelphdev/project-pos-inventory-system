<template>
  <div class="mt-8">
    <h3 class="rr-title-h3">
      Printing options
    </h3>
    <div>
      <div class="rr-field__radio">
        <input
          type="checkbox"
          v-model="wantsLabelsConfirmationLocal"
          :id="'labelsConfirmation'"
          class="rr-field__radio-input"
          @click="wantsLabelsConfirmationLocal = !wantsLabelsConfirmationLocal"
        />
        <label :for="`labelsConfirmation`" class="rr-field__radio-label"
          >Ask for label confirmation on item create.</label
        >
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
export default {
  computed: {
    ...mapGetters(["wantsLabels", "wantsLabelsConfirmation"]),
    wantsLabelsLocal: {
      get: function() {
        return this.wantsLabels;
      },
      set: function(bool) {
        this.$store.dispatch("setWantsLabels", bool);
      }
    },
    wantsLabelsConfirmationLocal: {
      get: function() {
        return this.wantsLabelsConfirmation;
      },
      set: function(bool) {
        this.$store.dispatch("setWantsLabelsConfirmation", bool);
      }
    }
  }
};
</script>
