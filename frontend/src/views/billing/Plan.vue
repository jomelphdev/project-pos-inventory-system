<template>
  <div>
    <!-- Plan -->
    <h2 class="text-lg font-bold mb-4">Current Plan</h2>

    <div
      class="mb-4 bg-white rounded-md shadow-md p-6"
      data-test="subscription-plans"
      v-if="subscriptionPlans"
    >
      <div
        v-for="(subscription, index) in plansForCycle"
        :key="subscription.id"
        class="rr-field__radio"
        :data-test="`subscription-plans-${index}`"
      >
        <input
          type="radio"
          v-model="selectedPlan"
          :id="`subscription_${subscription.price.id}`"
          :value="subscription.price.id"
          class="rr-field__radio-input"
        />
        <label
          :for="`subscription_${subscription.price.id}`"
          class="rr-field__radio-label items-center"
          >{{ subscription.name || "" }}
          <div class="flex flex-col ml-2">
            <span class="rr-pill rr-pill--default">
              {{ formatCurrency(subscription.price.unit_amount) }}
              /{{ billingCycle }}
            </span>
            <span
              class="rr-pill rr-pill--green mt-2 mr-auto"
              v-if="billingCycle != 'monthly'"
              >{{
                `Save ${formatCurrency(
                  subscription.price.savings
                )}/${billingCycle}`
              }}</span
            >
          </div>
        </label>
      </div>
      <div class="rr-field__radio" :key="'free_trial'" v-if="!subscription">
        <input
          type="radio"
          v-model="selectedPlan"
          id="subscription_free_trial"
          data-test="subscription-plans-freeTrial"
          :value="null"
          class="rr-field__radio-input"
        />
        <label for="subscription_free_trial" class="rr-field__radio-label"
          >Free Trial</label
        >
      </div>

      <div class="flex mt-6">
        <div class="self-end">
          <button
            class="rr-button rr-button--lg rr-button--primary flex justify-center"
            data-test="subscription-plans-selectPlan-button"
            :disabled="(samePlan && !subscription.ends_at) || subscribing"
            @click.stop="confirmPlan"
          >
            Select Plan
          </button>
        </div>

        <div class="ml-auto">
          <h2 class="text-lg font-bold mb-4">Billing Cycle</h2>

          <div class="grid grid-cols-3">
            <div class="rr-field__radio">
              <input
                type="radio"
                v-model="billingCycle"
                id="subscription_cycle-monthly"
                data-test="subscription-plans-monthly"
                value="monthly"
                class="rr-field__radio-input"
              />
              <label
                for="subscription_cycle-monthly"
                class="rr-field__radio-label h-full items-center"
                >Monthly</label
              >
            </div>
            <div class="rr-field__radio">
              <input
                type="radio"
                v-model="billingCycle"
                id="subscription_cycle-quarterly"
                data-test="subscription-plans-quarterly"
                value="quarterly"
                class="rr-field__radio-input"
              />
              <label
                for="subscription_cycle-quarterly"
                class="rr-field__radio-label"
              >
                <div class="flex flex-col">
                  Quarterly
                  <span class="rr-pill rr-pill--green mt-2 mr-auto"
                    >Save 5%</span
                  >
                </div>
              </label>
            </div>
            <div class="rr-field__radio">
              <input
                type="radio"
                v-model="billingCycle"
                id="subscription_cycle-annually"
                data-test="subscription-plans-annually"
                value="annually"
                class="rr-field__radio-input"
              />
              <label
                for="subscription_cycle-annually"
                class="rr-field__radio-label"
              >
                <div class="flex flex-col">
                  Annually
                  <span class="rr-pill rr-pill--green mt-2 mr-auto"
                    >Save 10%</span
                  >
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <modal ref="confirmPlanModal" class="centered">
      <template v-slot:header>
        Select Plan
      </template>
      <template v-slot:body>
        <div class="max-w-lg">
          <div class="mb-1">
            <span class="text-black text-lg font-bold">
              {{ planData.name }}
            </span>

            <span
              class="rr-pill rr-pill--default ml-1 relative"
              style="top:-2px;"
            >
              {{ formatCurrency(planData.price.unit_amount) }}
              /{{ billingCycle }}
            </span>
          </div>

          <br />
          <span
            class="text-sm text-red-600"
            v-if="subscription && subscription.ends_at"
          >
            Your subscription will no longer be canceled if you change plans.
          </span>
        </div>
      </template>
      <template v-slot:footer>
        <button
          class="rr-button rr-button--lg rr-button--primary"
          @click.stop="changePlan()"
          :disabled="subscribing"
        >
          Select Plan
        </button>
        <button
          class="rr-button rr-button--lg ml-4"
          @click="$refs.confirmPlanModal.closeModal()"
        >
          Cancel
        </button>
      </template>
    </modal>
  </div>
</template>

<script>
import { formatCurrency } from "@/helpers";
import { mapGetters } from "vuex";

import Modal from "@/components/Modal";

export default {
  name: "Plan",

  components: {
    Modal
  },

  data() {
    return {
      subscriptionPlans: null,
      selectedPlan: null,
      planData: null,
      currentPlan: null,
      subscribing: false,
      billingCycle: "monthly"
    };
  },

  computed: {
    ...mapGetters(["subscription", "organization"]),

    samePlan() {
      return this.selectedPlan && this.selectedPlan === this.currentPlan;
    },

    hasPaymentMethod() {
      return this.organization && !!this.organization.pm_last_four;
    },

    plansForCycle() {
      return this.subscriptionPlans
        .map(plan => {
          return {
            ...plan,
            price: plan.prices.find(price => {
              switch (this.billingCycle) {
                case "monthly":
                  return price.cycle == "monthly";
                case "quarterly":
                  return price.cycle == "quarterly";
                case "annually":
                  return price.cycle == "annually";
              }
            })
          };
        })
        .filter(plan => !!plan.price);
    }
  },

  mounted() {
    if (this.$route.query.plan) {
      this.selectedPlan = this.$route.query.plan;
    }

    if (this.$route.query.cycle) {
      this.billingCycle = this.$route.query.cycle;
    }

    this.getSubscriptionPlans();

    this.$root.$on("payment-method-created", paymentMethod =>
      this.changePlan(paymentMethod)
    );
  },

  methods: {
    getSubscriptionPlans() {
      this.$store.dispatch("fetchSubscriptionPlans").then(plans => {
        this.subscriptionPlans = plans;
        this.assignSubscription();
      });
    },

    confirmPlan() {
      this.planData = this.plansForCycle.find(
        p => p.price.id == this.selectedPlan
      );

      if (!this.selectedPlan) {
        return this.$emit("free-trial-selected");
      } else if (!this.hasPaymentMethod) {
        return this.$root.$emit("open-payment");
      }

      this.$refs.confirmPlanModal.openModal();
    },

    changePlan() {
      if (this.selectedPlan) {
        this.subscribing = true;
        this.$toasted.show("Subscribing to plan...", { type: "success" });

        this.$store
          .dispatch("changeSubscriptionPlan", {
            planId: this.selectedPlan
          })
          .then(() => {
            this.$toasted.show(
              `Subscription plan successfully set plan to ${this.planData.name}!`,
              { type: "success" }
            );
            this.assignSubscription();
          })
          .finally(() => {
            this.subscribing = false;
          });
      }
    },

    assignSubscription() {
      if (this.subscription) {
        const planId = this.subscription.stripe_price;
        this.selectedPlan = planId;
        this.currentPlan = planId;
        this.subscribing = false;

        const price = this.subscriptionPlans
          .find(plan => {
            for (let price of plan.prices) {
              if (price.id == this.currentPlan) {
                return true;
              }
            }
          })
          .prices.find(price => price.id == this.currentPlan);

        this.billingCycle = price.cycle;

        this.$refs.confirmPlanModal.closeModal();
      }
    },

    formatCurrency
  }
};
</script>
