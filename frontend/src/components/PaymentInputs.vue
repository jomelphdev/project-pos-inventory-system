<template>
  <div data-test="payment-inputs">
    <div class="rr-field">
      <label class="rr-field__label"> Cash </label>
      <currency-input
        id="cashInput"
        class="rr-field__input"
        data-test="payment-input-cash"
        v-model="cashLocal"
      />
      <button
        class="rr-button rr-add-remaining"
        data-test="payment-input-remainingToCash-button"
        @click.stop="remainingToCash"
        v-if="amountRemaining > 0"
      >
        Add&nbsp;Remaining
      </button>
    </div>
    <div
      class="rr-field"
      v-if="!forReturns || (forReturns && order && order.card_left > 0)"
    >
      <label class="rr-field__label"> Card </label>
      <currency-input
        id="cardInput"
        class="rr-field__input"
        data-test="payment-input-card"
        v-model="cardLocal"
      />
      <button
        class="rr-button rr-add-remaining"
        data-test="payment-input-remainingToCard-button"
        @click.stop="remainingToCard"
        v-if="amountRemaining > 0"
      >
        Add&nbsp;Remaining
      </button>
    </div>
    <div class="rr-field" v-if="ebtSubTotal > 0">
      <label class="rr-field__label"> EBT </label>
      <currency-input
        id="ebtInput"
        class="rr-field__input"
        data-test="payment-input-ebt"
        v-model="ebtLocal"
      />
      <button
        class="rr-button rr-add-remaining"
        data-test="payment-input-remainingToEbt-button"
        @click.stop="remainingToEbt"
        v-if="ebt != ebtSubTotal"
      >
        Add&nbsp;Eligible&nbsp;Amount
      </button>
    </div>
  </div>
</template>

<script>
import _ from "lodash";
import { mapGetters } from "vuex";

export default {
  props: {
    cash: Number,
    card: Number,
    ebt: Number,
    amountRemaining: Number,
    ebtSubTotal: Number,
    order: Object,
    total: Number,
    forReturns: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      cashLocal: 0,
      cardLocal: 0,
      ebtLocal: 0,
    };
  },

  computed: {
    ...mapGetters(["paymentPartner"]),

    cardMax() {
      if (
        this.forReturns &&
        this.paymentPartner &&
        this.order.processor_reference
      ) {
        return this.order.card_left;
      }

      return this.total;
    },
  },

  methods: {
    remainingToCash() {
      if (this.forReturns) {
        this.$emit("updateGC", {
          giftCardId: null,
          giftCardAmount: 0,
        });
      }
      this.cashLocal += this.amountRemaining;
    },
    remainingToCard() {
      if (this.forReturns) {
        this.$emit("updateGC", {
          giftCardId: null,
          giftCardAmount: 0,
        });
      }
      if (this.cardMax && this.amountRemaining > this.cardMax) {
        return (this.cardLocal = this.cardMax);
      }

      this.cardLocal += this.amountRemaining;
    },
    remainingToEbt() {
      if (this.forReturns) {
        this.$emit("updateGC", {
          giftCardId: null,
          giftCardAmount: 0,
        });
      }
      this.ebtLocal += this.ebtSubTotal;
    },
  },

  watch: {
    cashLocal: _.debounce(function (amount) {
      this.$emit("updateGC", {
        giftCardId: null,
        giftCardAmount: 0,
        giftCardBalance: null,
      });
      this.$emit("update:cash", amount);
    }, 250),
    cardLocal: _.debounce(function (amount) {
      if (this.cardMax && amount > this.cardMax) {
        this.cardLocal = this.cardMax;
        return this.$toasted.show(
          "Set to the maximum amount allowed to be charged on card.",
          { type: "info" }
        );
      }
      this.$emit("updateGC", {
        giftCardId: null,
        giftCardAmount: 0,
        giftCardBalance: null,
      });
      this.$emit("update:card", amount);
    }, 250),
    ebtLocal: _.debounce(function (amount) {
      this.$emit("updateGC", {
        giftCardId: null,
        giftCardAmount: 0,
        giftCardBalance: null,
      });
      this.$emit("update:ebt", amount);
    }, 250),
  },
};
</script>
