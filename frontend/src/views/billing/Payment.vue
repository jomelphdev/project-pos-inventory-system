<template>
  <div>
    <h2 class="text-lg font-bold mb-4">Payment Method</h2>

    <!-- Details -->
    <div class="mb-4 bg-white rounded-md shadow-md p-6">
      <div class="rr-field__label">Card Details</div>
      <div class="mt-2 mb-6" v-if="hasPaymentMethod">
        Your current payment method is a
        <strong>
          {{ organization.pm_type | capitalize }}
        </strong>
        card ending in
        <strong>
          {{ organization.pm_last_four }}
        </strong>
      </div>
      <div class="mt-2 mb-6" v-if="hasFreeTrial">
        You are currently in a
        <strong>
          Free Trial
        </strong>
        period ending
        <strong>
          {{ organization.trial_ends_at | moment }} ({{
            organization.trial_ends_at | date
          }})
        </strong>
      </div>
      <button
        class="rr-button--primary rr-button--lg flex justify-center"
        @click.stop="confirmPayment"
      >
        Update Payment Method
      </button>

      <!-- <div class="mt-8 mb-6">
        <div class="rr-field__label">Billing Details</div>
        <div>
          {{ defaultPaymentMethod.billing_details.address.line1 }} <br />
          {{ defaultPaymentMethod.billing_details.address.city }},
          {{ defaultPaymentMethod.billing_details.address.state }}
          {{ defaultPaymentMethod.billing_details.address.postal_code }} <br />
          {{ defaultPaymentMethod.billing_details.phone }}
        </div>
      </div>
      <button
        class="rr-button rr-button--lg flex justify-center"
        @click.stop="updateBillingDetails"
      >
        Update Billing Details
      </button> -->
    </div>

    <UpdatePaymentMethodModal :ref="'confirmPaymentModal'" />

    <!-- Modal - Update Billing Details -->
    <ModalWall ref="BillingDetailsModal">
      <template v-slot:header>
        <span class="block" v-text="`Billing Details`" />
      </template>
      <template v-slot:body>
        <!-- Billing Details Form -->
        <BillingDetailsForm :form.sync="billingForm" />
      </template>
      <template v-slot:footer>
        <div class="flex flex-row">
          <button
            class="rr-button rr-button--lg rr-button--primary"
            :disabled="billingForm.invalid"
            @click.stop="submitBillingDetails()"
            v-text="`Save Changes`"
          />
          <button
            class="rr-button rr-button--lg ml-4"
            @click="$refs.BillingDetailsModal.closeModal()"
          >
            Cancel
          </button>
        </div>
      </template>
    </ModalWall>
  </div>
</template>

<script>
import ModalWall from "@/components/ModalWall";
import BillingDetailsForm from "@/components/BillingDetailsForm";
import UpdatePaymentMethodModal from "./UpdatePaymentMethodModal";
import { mapGetters } from "vuex";
import moment from "moment";

// TODO: Replace with api data
// const defaultPaymentMethod = {
//   id: "cus_JFomDUmBJ1TGgK",
//   object: "customer",
//   address: null,
//   currency: "usd",
//   default_payment_method: {
//     id: "pm_1IdJMcAlqvKpxJCuhwdNwZDR",
//     object: "payment_method",
//     billing_details: {
//       address: {
//         city: "City",
//         country: null,
//         line1: "Address Line 1",
//         line2: null,
//         postal_code: "55555",
//         state: "State"
//       },
//       email: null,
//       name: null,
//       phone: "555-555-5555"
//     },
//     card: {
//       brand: "visa",
//       country: "US",
//       exp_month: 10,
//       exp_year: 2022,
//       funding: "credit",
//       generated_from: null,
//       last4: "4242",
//       wallet: null
//     },
//     created: 1617732658,
//     customer: "cus_JFomDUmBJ1TGgK",
//     livemode: false,
//     type: "card"
//   }
// };

export default {
  name: "Payment",

  components: {
    ModalWall,
    BillingDetailsForm,
    UpdatePaymentMethodModal
  },

  computed: {
    ...mapGetters(["organization", "subscription"]),
    hasPaymentMethod() {
      return !!this.organization.pm_last_four;
    },
    hasFreeTrial() {
      if (moment(this.organization.trial_ends_at).diff(moment.now()) > 0) {
        return true;
      }

      return false;
    }
  },

  data() {
    return {
      defaultPaymentMethod: null,
      billingForm: {},
      cardholderName: null,
      freeTrialRequest: false
    };
  },

  methods: {
    confirmPayment() {
      this.$refs.confirmPaymentModal.openModal();
    }

    // getDefaultPaymentMethod() {
    //   this.defaultPaymentMethod = defaultPaymentMethod.default_payment_method;
    //   this.billingForm = {
    //     address: this.defaultPaymentMethod.billing_details.address.line1,
    //     city: this.defaultPaymentMethod.billing_details.address.city,
    //     state_id: this.defaultPaymentMethod.billing_details.address.state, // TODO: Match/convert state for stripe
    //     zip: this.defaultPaymentMethod.billing_details.address.postal_code,
    //     phone: this.defaultPaymentMethod.billing_details.phone,
    //     invalid: false
    //   };
    // },

    // updateBillingDetails() {
    //   this.$refs.BillingDetailsModal.openModal();
    // },

    // submitBillingDetails() {
    //   // TODO: Make it work
    //   this.$refs.BillingDetailsModal.closeModal();
    // }
  },

  filters: {
    moment(date) {
      return moment(date).fromNow();
    },
    date(date) {
      return moment(date).format("MM/DD");
    }
  }
};
</script>
