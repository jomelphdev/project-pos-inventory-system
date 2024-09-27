<template>
  <modal
    ref="labelsConfirmationModal"
    class="centered"
    data-test="labels-confirmation"
  >
    <template v-slot:header>
      Print New Labels
    </template>
    <template v-slot:body>
      <div class="max-w-lg">
        <strong>Note:</strong>
        We noticed a change in store quantities. <br />
        Would you like to print those labels?
      </div>
    </template>
    <template v-slot:footer>
      <div class="flex flex-col items-center">
        <button
          class="rr-button rr-button--lg rr-button--primary-solid mb-4"
          :data-test="'labels-confirmation-printLabels-button'"
          @click="emitAnswer(true)"
        >
          Yes, Print New Labels
        </button>
        <button
          class="rr-button rr-button--lg"
          :data-test="'labels-confirmation-noLabels-button'"
          @click="emitAnswer(false)"
        >
          No Labels Needed
        </button>

        <div class="rr-field__radio mt-4" v-if="showDontAskAgain">
          <input
            type="checkbox"
            class="rr-field__radio-input"
            v-model="dontAskAgain"
            :id="'Confirmation'"
            :data-test="'labels-confirmation-dontAskAgain'"
            @click="dontAskAgain = !dontAskAgain"
          />
          <label :for="`Confirmation`" class="rr-field__radio-label"
            >Dont ask again</label
          >
        </div>
      </div>
    </template>
  </modal>
</template>

<script>
import { mapGetters } from "vuex";
import Modal from "./Modal";

export default {
  components: {
    Modal
  },

  props: {
    showDontAskAgain: {
      type: Boolean,
      default: true
    },
    saveAnswer: {
      type: Boolean,
      default: true
    }
  },

  computed: {
    ...mapGetters(["wantsLabelsConfirmation"]),
    dontAskAgain: {
      get: function() {
        return !this.wantsLabelsConfirmation;
      },
      set: function(bool) {
        this.$store.dispatch("setWantsLabelsConfirmation", !bool);
      }
    }
  },

  methods: {
    openModal() {
      this.$refs.labelsConfirmationModal.openModal();
    },
    closeModal() {
      this.$refs.labelsConfirmationModal.closeModal();
    },
    emitAnswer(ans) {
      if (this.showDontAskAgain && this.saveAnswer) {
        this.$store.dispatch("setWantsLabels", ans);
      }

      this.$emit("labels-wanted", ans);
      this.closeModal();
    }
  }
};
</script>
