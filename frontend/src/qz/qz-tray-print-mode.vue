<template>
  <div class="qz-tray--container qz-tray--print-mode">
    <div class="qz-tray--print-mode--header--wrapper">
      <slot name="label">
        <h3 class="rr-title-h3">Print Mode</h3>
      </slot>
    </div>

    <div>
      <div
        v-for="(mode, index) in qzPrintModes"
        :key="mode"
        class="rr-field__radio"
      >
        <input
          type="radio"
          :id="`print-mode-${index}`"
          :value="index"
          v-model="selectedMode"
          class="rr-field__radio-input"
        /><label :for="`print-mode-${index}`" class="rr-field__radio-label">{{
          mode
        }}</label>
      </div>
    </div>

    <p class="text-sm mt-4">
      Note: Please ensure a compatible {{ selectedMode }} printer is selected.
    </p>
  </div>
</template>

<script>
import { mapGetters } from "vuex";

export default {
  name: "QzTrayPrintMode",

  data() {
    return {
      selectedMode: ""
    };
  },

  computed: {
    ...mapGetters(["qzPrintMode", "qzPrintModes"])
  },

  created() {
    this.selectedMode = this.qzPrintMode;
  },

  watch: {
    selectedMode(newValue) {
      this.$store.dispatch("updateQzPrintMode", newValue);
    }
  }
};
</script>
