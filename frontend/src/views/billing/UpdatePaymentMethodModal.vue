<template>
  <!-- Modal - Update Payment Method -->
  <modal ref="paymentModal" class="centered" data-test="payment-method-modal">
    <template v-slot:header> Update Payment Method </template>
    <template v-slot:body>
      <div class="w-full max-w-md">
        <div class="rr-field mb-4">
          <label class="rr-field__label text-left">
            Cardholder name
          </label>
          <input
            type="text"
            class="rr-field__input"
            data-test="payment-method-modal-name-input"
            v-model="cardholderName"
          />
        </div>
        <div class="rr-field mb-4">
          <label class="rr-field__label text-left">
            Credit Card
          </label>
          <div
            class="rr-field__input"
            id="card"
            data-test="payment-method-modal-cardElement"
          ></div>
        </div>
        <div class="text-sm text-left ml-1">
          All future payments will apply to this credit card.
          <br /><br />
          Your credit card number is transmitted securely to
          <a class="underline" href="https://stripe.com/privacy" target="_blank"
            >stripe.com</a
          >
          for verification &amp; processing. RetailRight does not store your
          credit card number on its servers.
        </div>
      </div>
    </template>
    <template v-slot:footer>
      <button
        class="rr-button rr-button--lg rr-button--primary"
        data-test="payment-method-modal-updatePaymentMethod-button"
        @click.stop="changePayment"
        :disabled="updatingPaymentMethod"
      >
        Update Card
      </button>
      <button
        class="rr-button rr-button--lg ml-4"
        data-test="payment-method-modal-cancel-button"
        @click="closeModal"
      >
        Cancel
      </button>
    </template>
  </modal>
</template>

<script>
import Modal from "@/components/ModalPersist";
import StripeMixin from "@/mixins/StripeMixin";

export default {
  mixins: [StripeMixin],

  components: {
    Modal
  },

  data() {
    return {
      cardholderName: null,
      planRequest: false
    };
  },

  mounted() {
    this.$root.$on("open-payment", () => {
      this.planRequest = true;
      this.openModal();
    });
  },

  methods: {
    openModal() {
      this.$refs.paymentModal.openModal();
    },

    closeModal() {
      this.$refs.paymentModal.closeModal();
    },

    async changePayment() {
      this.$toasted.show("Validating card...", { type: "success" });
      this.createPaymentMethod();
    }
  }
};
</script>
