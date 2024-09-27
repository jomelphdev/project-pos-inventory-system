/**
 * Methods currently picked from src/views/auth/Create.vue:
 * https://github.com/RetailRight/retail-right-ui-v3/pull/241/files
 * TODO: Combine/refactor when merged together.
 */
import { mapGetters } from "vuex";

export default {
  data() {
    return {
      paymentMethod: null,
      updatingPaymentMethod: false
    };
  },

  computed: {
    ...mapGetters(["session_id", "payment_method"])
  },

  mounted() {
    this.includeStripe(
      "js.stripe.com/v3/",
      function() {
        this.stripe = window.Stripe(process.env.VUE_APP_STRIPE_PUBLISHABLE_KEY);
        this.configureStripe();
      }.bind(this)
    );

    if (!this.session_id) {
      this.$store.dispatch("createCheckoutSession");
    }
  },

  watch: {
    paymentMethod(paymentMethod) {
      this.$refs.paymentModal.closeModal();
      this.clearCardDetails();

      if (this.planRequest) {
        this.$root.$emit("payment-method-created", paymentMethod);
      }
    }
  },

  methods: {
    includeStripe(URL, callback) {
      let documentTag = document,
        tag = "script",
        object = documentTag.createElement(tag),
        scriptTag = documentTag.getElementsByTagName(tag)[0];
      object.src = "//" + URL;
      if (callback) {
        object.addEventListener(
          "load",
          function(e) {
            callback(null, e);
          },
          false
        );
      }
      scriptTag.parentNode.insertBefore(object, scriptTag);
    },

    configureStripe() {
      this.elements = this.stripe.elements();
      this.card = this.elements.create("card", {
        style: {
          base: {
            "::placeholder": {
              color: "#718096"
            },
            fontFamily:
              'system-ui, -apple-system, BlinkMacSystemFont,"Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji","Segoe UI Emoji", "Segoe UI Symbol"',
            color: "#000000",
            fontSize: "18px",
            fontWeight: "bold"
          }
        }
      });
      this.card.mount("#card");
    },

    async createPaymentMethod() {
      this.updatingPaymentMethod = true;

      const { setupIntent, error } = await this.stripe.confirmCardSetup(
        this.session_id,
        {
          payment_method: {
            card: this.card,
            billing_details: { name: this.cardholderName }
          }
        }
      );

      if (error) {
        this.updatingPaymentMethod = false;
        this.$toasted.show("There was an error with the payment method.", {
          type: "error"
        });

        return null;
      }

      this.$store.dispatch("createCheckoutSession");
      this.updatePaymentMethod(setupIntent.payment_method);

      return setupIntent.payment_method;
    },

    updatePaymentMethod(paymentMethod) {
      this.updatingPaymentMethod = true;

      this.$store
        .dispatch("updatePaymentMethod", paymentMethod)
        .then(newPaymentMethod => {
          this.paymentMethod = newPaymentMethod;
        })
        .finally(() => {
          this.updatingPaymentMethod = false;
        });
    },

    clearCardDetails() {
      this.cardholderName = null;
      this.card.clear();
    }
  }
};
