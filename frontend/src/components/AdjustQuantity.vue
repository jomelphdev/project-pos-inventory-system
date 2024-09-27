<template>
  <div class="modal-background" data-test="adjust-quantity" v-show="show">
    <div class="keypad-ui modal max-w-3xl modal mx-auto" @click.stop>
      <a href="#" class="modal__close" @click.prevent.stop="closeModal(true)">
        Close
      </a>

      <div class="flex flex-col items-center">
        <div
          class="text-3xl font-bold text-black"
          data-test="store-name"
          v-if="quantity.store_id"
        >
          Update
          {{ getStoreName(quantity.store_id) }}
          Quantity
        </div>
        <div class="mt-4 mb-2 text-lg font-bold" data-test="verb">
          <template v-if="negative">
            <span class="text-red-700">Subtract</span>
          </template>
          <template v-else>
            <span class="text-black">Add</span>
          </template>
        </div>
        <div class="flex items-center justify-between w-full max-w-md">
          <div
            @click="decreaseAmount"
            class="svg svg-key-decrease keypad-ui__decrease"
            data-test="decreaseAmount-button"
          ></div>
          <div>
            <input
              type="number"
              v-model="amount"
              class="keypad-ui__input mx-4"
              data-test="quantityAdjustment-input"
              :class="amountClass(amount)"
            />
          </div>
          <div
            @click="increaseAmount"
            class="svg svg-key-increase keypad-ui__increase"
            data-test="increaseAmount-button"
          ></div>
        </div>
        <div
          v-if="negative"
          class="w-1/2 mt-6 text-center"
          data-test="negative-warning"
        >
          <strong>Warning:</strong>
          Subtracting quantity could produce negative inventory. Proceed with
          caution.
        </div>
        <div
          v-if="negative"
          class="w-1/2 mt-6 -mb-4"
          data-test="reason-for-negative"
        >
          <div class="rr-field">
            <label class="rr-field__label">Reason for subtraction</label>
            <input
              class="rr-field__input"
              type="text"
              placeholder="Ex: Added to the wrong store"
              data-test="quantityMessage-input"
              v-model="message"
              @change="editQuantity"
            />
          </div>
        </div>
        <div>
          <button
            class="rr-button rr-button--lg rr-button--primary-solid mt-8"
            @click="closeModal(false)"
            data-test="returnToItem-button"
          >
            Return to Item
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { amountClass, getStoreName } from "@/helpers";

export default {
  data() {
    return {
      quantity: {},
      amount: null,
      message: "",
      show: false
    };
  },

  props: {
    allowNegatives: {
      type: Boolean,
      default: true
    }
  },

  mounted() {
    if (this.amount > 0) {
      this.sendAdjustment();
    }

    this.$root.$on("adjust-quantity", (quantityObj, quantityNeeded = 0) => {
      this.show = true;
      this.quantity = quantityObj;
      this.amount = quantityNeeded;
    });
  },

  computed: {
    negative() {
      return this.amount < 0;
    }
  },

  methods: {
    editQuantity() {
      if (this.negative && this.message != "") {
        this.$root.$emit("quantity-adjustment", {
          id: this.quantity.store_id,
          amount: this.amount,
          message: this.message
        });
      }
    },

    closeModal(closeButton = false) {
      if (this.negative && this.message == "") {
        return this.$toasted.show(
          "Please enter a reason for removal to continue.",
          { type: "error" }
        );
      }

      if (!closeButton) {
        this.$root.$emit("save-quantity");
      }

      this.show = false;
    },

    increaseAmount() {
      this.amount++;
    },

    decreaseAmount() {
      if (this.quantity.quantity - -(this.amount - 1) < 0) {
        return this.$toasted.show(
          "We do not allow for the creation of negative quantities.",
          { type: "error" }
        );
      }

      this.amount--;
    },

    sendAdjustment() {
      this.$root.$emit("quantity-adjustment", {
        id: this.quantity.store_id,
        amount: this.amount
      });
    },

    amountClass,
    getStoreName
  },

  watch: {
    amount: function() {
      if (this.negative) {
        this.message = this.quantity.message;

        if (!this.allowNegatives) {
          this.$toasted.show("Negative quantities not allowed on this page.", {
            type: "info"
          });
          this.amount = 0;
          return;
        }

        if (this.message == "") {
          return;
        }
      }

      this.sendAdjustment();
    }
  }
};
</script>
