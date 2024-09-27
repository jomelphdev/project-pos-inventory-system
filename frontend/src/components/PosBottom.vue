<template>
  <div class="pos-ui__bottom" data-test="pos-bottom">
    <div class="pos-ui__bottom-container">
      <div
        class="container px-8 flex justify-between"
        data-test="pos-bottom-totals"
        v-if="!paymentMethod"
      >
        <!-- Start Over -->
        <div class="flex mr-4">
          <div>
            <button
              class="rr-button rr-button--lg rr-button--danger flex justify-center"
              data-test="pos-start-over-button"
              @click="$root.$emit('reload-pos', true)"
            >
              Start Over
            </button>
          </div>
        </div>
        <!-- Totals -->
        <div class="flex">
          <div class="ml-8">
            <div class="text-xs leading-none text-center">Sub-Total</div>
            <div
              class="font-bold text-3xl"
              data-test="pos-bottom-subTotal-display"
            >
              {{
                order.discount_amount > 0
                  ? formatCurrency(order.prior_sub_total)
                  : formatCurrency(order.sub_total)
              }}
            </div>
          </div>
          <div class="ml-8 text-green-600" v-if="order.discount_amount > 0">
            <div class="text-xs leading-none text-center">Discount</div>
            <div
              class="font-bold text-3xl"
              data-test="pos-bottom-discount-display"
            >
              {{ formatCurrency(order.discount_amount) }}
            </div>
          </div>
          <div class="ml-8">
            <div class="text-xs leading-none text-center">Tax</div>
            <div class="font-bold text-3xl" data-test="pos-bottom-tax-display">
              {{ formatCurrency(order.tax) }}
            </div>
          </div>
          <div class="mx-8">
            <div
              class="text-xs leading-none text-center font-bold text-blue-700"
            >
              Total
            </div>
            <div
              class="font-bold text-3xl text-blue-700"
              data-test="pos-bottom-total-display"
            >
              {{ formatCurrency(order.total) }}
            </div>
          </div>
        </div>
        <!-- Payment Buttons -->
        <div class="flex" data-test="pos-bottom-payment-methods">
          <div class="ml-2">
            <button
              class="rr-button rr-button--lg rr-button--primary flex justify-center"
              data-test="pos-bottom-cash-button"
              @click="cashPaymentBtn"
            >
              Cash
            </button>
          </div>
          <div class="ml-2">
            <button
              class="rr-button rr-button--lg rr-button--primary flex justify-center"
              data-test="pos-bottom-card-button"
              @click="cardPaymentBtn"
            >
              Card
            </button>
          </div>
          <div class="ml-2" v-if="!classifications_disabled">
            <button
              class="rr-button rr-button--lg rr-button--primary flex justify-center"
              data-test="pos-bottom-ebt-button"
              @click="ebtPaymentBtn"
            >
              EBT
            </button>
          </div>
          <div class="ml-2">
            <button
              class="rr-button rr-button--lg rr-button--primary flex justify-center"
              @click="$refs.scannerModal.openModal()"
            >
              GC
            </button>
          </div>
        </div>
      </div>
      <div
        class="container px-8 flex justify-between"
        data-test="pos-bottom-summary"
        v-if="paymentMethod"
      >
        <!-- Order Summary -->
        <div class="text-right">
          <div class="mb-4 font-bold">Order Summary</div>
          <OrderMathSummary :order="order" />
        </div>
        <!-- Payments / Remaining -->
        <div class="text-right">
          <div class="mb-4 font-bold">Payments</div>
          <table class="rr-ruled-table">
            <tr>
              <td>
                <span class="rr-tiny-label"> Cash </span>
              </td>
              <td data-test="pos-bottom-cashPaid">
                {{ formatCurrency(order.cash) }}
              </td>
            </tr>
            <tr>
              <td>
                <span class="rr-tiny-label"> Card </span>
              </td>
              <td data-test="pos-bottom-cardPaid">
                {{ formatCurrency(order.card) }}
              </td>
            </tr>
            <tr v-if="order.ebt > 0">
              <td class="text-left">
                <span class="rr-tiny-label"> EBT </span>
              </td>
              <td data-test="pos-bottom-ebtPaid">
                {{ formatCurrency(order.ebt) }}
              </td>
            </tr>
            <tr>
              <td class="text-left">
                <span class="rr-tiny-label"> GC </span>
              </td>
              <td data-test="pos-bottom-gcPaid">
                {{ formatCurrency(order.gc !== undefined ? order.gc : 0) }}
              </td>
            </tr>
            <tr>
              <td></td>
              <td>
                <strong class="block mt-3 leading-none"
                  >Amount Remaining</strong
                >
                <div>
                  <span
                    v-if="order.amount_remaining >= 0 && order.change == 0"
                    class="font-bold text-3xl"
                    :class="{
                      'text-red-700': order.amount_remaining > 0,
                      'text-green-700': order.amount_remaining == 0,
                    }"
                    data-test="pos-bottom-amountRemaining"
                  >
                    {{ formatCurrency(order.amount_remaining) }}
                  </span>
                  <div
                    data-test="pos-bottom-change-note"
                    v-else-if="order.change > 0"
                  >
                    <span class="font-bold text-3xl text-green-700">
                      {{ formatCurrency(order.change) }}
                    </span>
                    <span
                      class="block rr-tiny-label text-green-700 border-green-600"
                    >
                      Change due to customer
                    </span>
                  </div>
                </div>
              </td>
            </tr>
          </table>
        </div>
        <!-- Payment Inputs -->
        <div style="width: 320px">
          <PaymentInputs
            :cash.sync="order.cash"
            :card.sync="order.card"
            :ebt.sync="order.ebt"
            :amountRemaining="order.amount_remaining"
            :ebtSubTotal="order.ebt_sub_total"
            :total="order.total"
          />
          <div class="flex mt-8 mb-3">
            <button
              class="rr-button rr-button--lg rr-button--primary-solid flex justify-center flex-1"
              data-test="pos-bottom-checkout-button"
              @click.stop="goToCheckout"
              :disabled="order.amount_remaining > 0 || checkingOut"
            >
              Checkout
            </button>
            <button
              class="rr-button rr-button--lg flex justify-center ml-2"
              data-test="pos-bottom-goBack-button"
              @click.stop="$emit('update:paymentMethod', null)"
            >
              Go Back
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- QR Code Reader Modal -->
    <modal ref="scannerModal" :size="`2xl`">
      <template v-slot:header>Scan QR Code</template>
      <template v-slot:body>
        <CameraCodeScanner @scan="onScan" @load="onLoad"></CameraCodeScanner>
      </template>
    </modal>

    <!-- Checking Balance Modal -->
    <modal ref="checkingBalanceModal" :size="`2xl`">
      <template v-slot:header>Gift Card Payment</template>
      <template v-slot:body>
        <div v-if="giftCards.isCheckingBalance" class="h3 text-center">
          Checking Balance...
        </div>

        <div v-if="giftCards.isExpired">
          <div class="h3 text-red-500 text-center">{{ giftCards.message }}</div>
        </div>

        <div v-else-if="giftCards.isDeactivated">
          <div class="h3 text-red-500 text-center">{{ giftCards.message }}</div>
        </div>

        <div v-else-if="giftCards.balance == 0">
          <div class="h3 text-red-500 text-center">
            Gift Card balance is zero.
          </div>
        </div>

        <div v-else-if="giftCards.record.length == 0">
          <p class="h3 text-red-500 text-center">
            Gift Card is not yet registered.
          </p>
        </div>

        <div v-else>
          <div>
            Balance:
            <span class="font-bold">{{
              formatCurrency(giftCards.balance)
            }}</span>
          </div>
          <div>
            Expiration Date:
            <span class="font-bold">{{ giftCards.expiration_date }}</span>
          </div>

          <div class="rr-field mt-10">
            <label class="rr-field__label">
              Enter amount
              <span
                class="rr-field__label-required"
                v-if="!$v.giftCards.amount.required"
              >
                Required
              </span>
              <span
                class="rr-field__label-required"
                v-if="isAmountGreaterThanBalance"
              >
                Amount cannot exceed balance
              </span>
              <span
                class="rr-field__label-required"
                v-if="isAmountGreaterThanOrderTotal"
              >
                Amount cannot exceed order total
              </span>
            </label>
            <currency-input
              class="rr-field__input"
              v-model="giftCards.amount"
            />
          </div>
        </div>
      </template>
      <template v-slot:footer>
        <div
          class="flex"
          v-if="
            !(giftCards.isExpired || giftCards.isDeactivated) &&
            giftCards.balance != 0 &&
            giftCards.record.length != 0
          "
        >
          <button
            class="rr-button rr-button--lg rr-button--primary"
            @click="addToPayment"
            :disabled="
              $v.giftCards.$invalid ||
              isAmountGreaterThanBalance ||
              isAmountGreaterThanOrderTotal
            "
          >
            Add to Payment
          </button>

          <button
            class="rr-button rr-button--lg ml-4"
            @click="$refs.checkingBalanceModal.closeModal()"
          >
            Cancel
          </button>
        </div>
      </template>
    </modal>
  </div>
</template>

<script>
import { formatCurrency } from "@/helpers.js";

import PaymentInputs from "./PaymentInputs";
import OrderMathSummary from "./OrderMathSummary";
import { mapGetters } from "vuex";
import Modal from "@/components/Modal";
import { CameraCodeScanner } from "vue-barcode-qrcode-scanner";
import moment from "moment";
import { required } from "vuelidate/lib/validators";

export default {
  name: "PosBottom",

  data() {
    return {
      giftCards: {
        id: null,
        balance: null,
        expiration_date: null,
        message: null,
        isExpired: false,
        isDeactivated: false,
        isCheckingBalance: true,
        record: [],
        amount: null,
        giftCode: null,
      },

      currentDate: moment().format("YYYY-MM-DD"),
    };
  },

  props: {
    order: Object,
    checkingOut: {
      type: Boolean,
      default: false,
    },
    paymentMethod: String,
  },

  components: {
    PaymentInputs,
    OrderMathSummary,
    CameraCodeScanner,
    Modal,
  },

  computed: {
    ...mapGetters(["paymentPartner", "classifications_disabled"]),

    isAmountGreaterThanBalance() {
      return this.giftCards.amount > this.giftCards.balance;
    },

    isAmountGreaterThanOrderTotal() {
      return this.giftCards.amount > this.order.total;
    },
  },

  watch: {
    paymentMethod(method) {
      if (method) {
        this.$nextTick(() =>
          document.getElementById(`${method}Input`).select()
        );
      }
    },
  },

  methods: {
    goToCheckout() {
      if (this.order.amount_remaining <= 0 && !this.checkingOut) {
        this.$emit("update:checkingOut", true);
        this.$emit("checkout", true);
      }
    },

    onLoad({ controls, scannerElement, browserMultiFormatReader }) {
      console.log(controls);
      console.log(scannerElement);
      console.log(browserMultiFormatReader);
    },

    onScan({ result, raw }) {
      this.giftCards.amount = null;
      this.giftCards.message = null;
      this.giftCards.isExpired = false;
      this.giftCards.isDeactivated = false;
      this.$refs.scannerModal.closeModal();
      const qrcode = result;
      this.checkGiftCardBalance(qrcode);
      this.$refs.checkingBalanceModal.openModal();
      console.log(raw);
    },

    async checkGiftCardBalance(qrcode) {
      try {
        const giftCard = {
          qrcode: qrcode,
        };
        const data = await this.$store.dispatch(
          "checkGiftCardBalance",
          giftCard
        );

        this.giftCards.record = data;
        this.giftCards.isCheckingBalance = false;

        if (data.length > 0) {
          this.giftCards.id = data[0].id;
          this.giftCards.giftCode = qrcode;
          if (data[0].expiration_date <= this.currentDate) {
            this.giftCards.isExpired = true;
            this.giftCards.message = "Gift Card already expired";
          } else if (data[0].is_activated == 0) {
            this.giftCards.isDeactivated = true;
            this.giftCards.message = "Gift Card is deactivated";
          } else {
            this.giftCards.balance = data[0].balance;
            this.giftCards.expiration_date = data[0].expiration_date;
          }
        }
      } catch (error) {
        console.error("Error fetching data:", error);
      }
    },

    // update order.gc based on the entered amount from gift card balance
    addToPayment() {
      const remainingBalance = this.giftCards.balance - this.giftCards.amount;

      this.$emit("updateGC", {
        amount: this.giftCards.amount,
        remainingBalance: remainingBalance,
        giftCode: this.giftCards.giftCode,
        giftCardId: this.giftCards.id,
        giftCardAmount: this.giftCards.amount,
      });

      this.$refs.checkingBalanceModal.closeModal();
      this.$emit("update:paymentMethod", "cash");
    },

    cashPaymentBtn() {
      this.$emit("update:paymentMethod", "cash");
      this.$emit("updateGC", 0);
    },

    cardPaymentBtn() {
      this.$emit("update:paymentMethod", "card");
      this.$emit("updateGC", 0);
    },

    ebtPaymentBtn() {
      this.$emit("update:paymentMethod", "ebt");
      this.$emit("updateGC", 0);
    },

    formatCurrency,
  },

  validations() {
    return {
      giftCards: {
        amount: { required },
      },
    };
  },
};
</script>

<style lang="scss"></style>
