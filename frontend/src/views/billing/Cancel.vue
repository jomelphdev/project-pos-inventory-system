<template>
  <div v-if="subscription">
    <h2 class="text-lg font-bold mb-4">Cancel Subscription</h2>

    <div class="mb-4 bg-white rounded-md shadow-md p-6">
      <button
        class="rr-button rr-button--lg flex justify-center"
        :disabled="subscription.ends_at"
        @click="confirmCancel"
      >
        Cancel Subscription
      </button>
    </div>

    <modal ref="confirmCancelModal" class="centered">
      <template v-slot:header>
        Cancel Subscription
      </template>
      <template v-slot:body>
        <div class="max-w-lg">
          <strong class="text-red-700">Warning:</strong>
          Your current plan will be canceled at the end of this billing period.
          Your organization and employees will lose all access to this account
          and related data.
          <br /><br />
          <strong>Are you sure about canceling this subscription?</strong>
        </div>
      </template>
      <template v-slot:footer>
        <div class="flex">
          <div class="rr-field__radio mr-4">
            <input
              type="checkbox"
              v-model="killSwitch"
              :id="'inputHidden'"
              class="rr-field__radio-input"
            />
            <label
              :for="'inputHidden'"
              class="rr-field__radio-label items-baseline"
            >
              I’m sure
            </label>
          </div>
        </div>

        <button
          class="rr-button rr-button--lg rr-button--danger"
          :disabled="!killSwitch"
          @click="cancelSubscription"
        >
          Cancel Subscription Immediately
        </button>
        <button
          class="rr-button rr-button--lg ml-4"
          @click="$refs.confirmCancelModal.closeModal()"
        >
          Cancel
        </button>
      </template>
    </modal>
  </div>
</template>

<script>
import Modal from "@/components/Modal";
import { mapGetters } from "vuex";

export default {
  name: "Cancel",

  components: {
    Modal
  },

  computed: {
    ...mapGetters(["subscription"])
  },

  data() {
    return {
      killSwitch: false
    };
  },

  methods: {
    confirmCancel() {
      this.$refs.confirmCancelModal.openModal();
    },
    cancelSubscription() {
      this.$toasted.show("Canceling subscription...", { type: "error" });
      this.$store.dispatch("cancelSubscription").then(() => {
        this.$refs.confirmCancelModal.closeModal();
        this.killSwitch = false;
      });
    }
  }
};
</script>
