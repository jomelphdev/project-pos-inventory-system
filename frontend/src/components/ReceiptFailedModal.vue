<template>
  <modal-wall
    ref="receiptFailed"
    data-test="receipt-failed-modal-main"
    class="centered"
  >
    <template v-slot:header>
      Transaction created, but receipt failed to print
    </template>
    <template v-slot:body>
      <div class="grid grid-cols-2 gap-2">
        <button
          class="rr-button rr-button--lg rr-button--primary-solid mb-4 justify-center"
          @click="printReceipt"
        >
          Try Again
        </button>
        <button
          class="rr-button rr-button--lg rr-button--primary mb-4 justify-center"
          @click="reload"
        >
          Start New
        </button>
      </div>
    </template>
  </modal-wall>
</template>

<script>
import ModalWall from "@/components/ModalWall";

export default {
  name: "ReceiptFailedModal",

  components: { ModalWall },

  methods: {
    closeModal() {
      this.$refs.receiptFailed.closeModal();
    },

    openModal() {
      this.$refs.receiptFailed.openModal();
    },

    printReceipt() {
      this.$emit("print-receipt");
    },

    reload() {
      this.$emit("reload");
    }
  }
};
</script>

<style></style>
