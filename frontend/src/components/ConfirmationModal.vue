<template>
  <modal
    ref="confirmationModal"
    class="centered"
    data-test="confirmation-modal"
  >
    <template v-slot:header>
      {{ header }}
    </template>
    <template v-slot:body>
      {{ body }}
    </template>
    <template v-slot:footer>
      <div class="flex items-center space-x-4">
        <button
          class="rr-button rr-button--lg rr-button--primary-solid"
          :data-test="'confirmation-yes-button'"
          @click="emitResponse(true)"
        >
          Yes
        </button>
        <button
          class="rr-button rr-button--danger rr-button--lg"
          :data-test="'confirmation-no-button'"
          @click="emitResponse(false)"
        >
          No
        </button>
      </div>
    </template>
  </modal>
</template>

<script>
import Modal from "./Modal.vue";

export default {
  props: {
    header: {
      type: String,
      default: "Are you sure you want to do this?",
    },
    body: {
      type: String,
      default: "",
    },
  },

  emits: ["response"],

  components: { Modal },

  methods: {
    emitResponse(response) {
      this.$emit("response", response);
      this.closeModal();
    },

    closeModal() {
      this.$refs.confirmationModal.closeModal();
    },

    openModal() {
      this.$refs.confirmationModal.openModal();
    },
  },
};
</script>

<style></style>
