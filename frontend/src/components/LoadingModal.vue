<template>
  <modal-wall class="centered" ref="loadingModal" data-test="loading-modal">
    <template v-slot:header>
      <a
        href="#"
        class="modal__close"
        data-test="modalClose-button"
        @click.prevent.stop="closeModal"
        v-if="!hideClose"
      >
        Close
      </a>

      {{ loadingText }}
    </template>
    <template v-slot:body>
      <LoadingSpinner size="xl" />
    </template>
  </modal-wall>
</template>

<script>
import ModalWall from "./ModalWall.vue";
import LoadingSpinner from "./LoadingSpinner.vue";

export default {
  name: "LoadingModal",

  props: {
    loadingText: String,
    hideClose: {
      type: Boolean,
      default: false,
    },
  },

  components: { ModalWall, LoadingSpinner },

  methods: {
    closeModal() {
      this.$refs.loadingModal.closeModal();
    },

    openModal() {
      this.$refs.loadingModal.openModal();
    },
  },
};
</script>

<style></style>
