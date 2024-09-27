<template>
  <div class="pos-ui__bottom" data-test="returns-bottom">
    <div class="pos-ui__bottom-container">
      <div class="container px-8 flex justify-between">
        <!-- Start Over -->
        <div class="flex mr-4" v-if="!finalize">
          <div>
            <button
              class="rr-button rr-button--lg rr-button--danger flex justify-center"
              data-test="returns-bottom-startOver-button"
              @click.stop="$root.$emit('reload-pos', true)"
            >
              Start Over
            </button>
          </div>
        </div>

        <!-- Totals -->
        <div
          class="flex"
          data-test="returns-bottom-totals-display"
          v-if="!finalize"
        >
          <div class="ml-8">
            <div class="text-xs leading-none text-center">Items</div>
            <div
              class="font-bold text-3xl text-center"
              data-test="returns-bottom-totals-items"
            >
              {{ items.length }}
            </div>
          </div>
          <div class="ml-8">
            <div class="text-xs leading-none text-center">Sub-Total</div>
            <div
              class="font-bold text-3xl"
              data-test="returns-bottom-totals-subTotal"
            >
              {{ formatCurrency(returnForm.sub_total) }}
            </div>
          </div>
          <div class="ml-8">
            <div class="text-xs leading-none text-center">Tax</div>
            <div
              class="font-bold text-3xl"
              data-test="returns-bottom-totals-tax"
            >
              {{ formatCurrency(returnForm.tax) }}
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
              data-test="returns-bottom-totals-total"
            >
              {{ formatCurrency(returnForm.total) }}
            </div>
          </div>
        </div>

        <div v-if="finalize" style="width: 320px">
          <div class="mb-4 font-bold">Items Returning</div>
          <table
            class="rr-ruled-table"
            data-test="returns-bottom-items-returning-table"
          >
            <tr v-for="item of items" :key="item.id">
              <td>
                <div class="mb-1 text-sm leading-5 font-medium text-gray-900">
                  {{ item.title | truncate(75) }}
                </div>
                <span class="rr-tiny-label mr-1">
                  {{ item.quantity_returned }}x
                </span>
                <span class="rr-tiny-label mr-1" v-if="item.is_ebt"> EBT </span>
                <span class="rr-tiny-label mr-1">
                  {{ formatCurrency(item.price) }} /ea
                </span>
              </td>
            </tr>
          </table>
        </div>

        <!-- Return Summary -->
        <div v-if="finalize" class="text-right">
          <div class="mb-4 font-bold">Refund Amount</div>
          <OrderMathSummary :order="returnForm" />
        </div>

        <!-- Payments / Remaining -->
        <div v-if="finalize" class="text-right" data-test="payments-remaining">
          <div class="mb-4 font-bold">Due Customer</div>
          <table class="rr-ruled-table">
            <tr>
              <td>
                <span class="rr-tiny-label"> Cash </span>
              </td>
              <td>
                <span data-test="returns-bottom-cashDue">
                  {{ formatCurrency(returnForm.cash) }}
                </span>
              </td>
            </tr>
            <tr>
              <td>
                <span class="rr-tiny-label"> Card </span>
              </td>
              <td>
                <span data-test="returns-bottom-cardDue">
                  {{ formatCurrency(returnForm.card) }}
                </span>
              </td>
            </tr>
            <tr v-if="returnForm.ebt_sub_total > 0">
              <td class="text-left">
                <span class="rr-tiny-label"> EBT </span>
              </td>
              <td>
                <span data-test="returns-bottom-ebtDue">
                  {{ formatCurrency(returnForm.ebt) }}
                </span>
              </td>
            </tr>
            <tr>
              <td class="text-left">
                <span class="rr-tiny-label"> GC </span>
              </td>
              <td>
                <span>
                  {{ formatCurrency(returnForm.gc) }}
                </span>
              </td>
            </tr>
            <tr>
              <td></td>
              <td>
                <strong class="block mt-3 leading-none"
                  >Amount Remaining</strong
                >
                <span
                  class="font-bold text-3xl"
                  :class="{
                    'text-red-700': returnForm.amount_remaining > 0,
                    'text-green-700': returnForm.amount_remaining <= 0,
                  }"
                  data-test="returns-bottom-amountRemaining"
                >
                  {{ formatCurrency(returnForm.amount_remaining) }}
                </span>
              </td>
            </tr>
          </table>
        </div>

        <!-- Payment Summary -->
        <div v-if="finalize" class="text-right" data-test="payment-summary">
          <div class="mb-4 font-bold">Received on Purchase</div>
          <table class="rr-ruled-table w-full">
            <tr>
              <td class="text-left">
                <span class="rr-tiny-label"> Cash </span>
              </td>
              <td>
                <span data-test="returns-bottom-cashPaid">
                  {{ formatCurrency(order.cash_left) }}
                </span>
              </td>
            </tr>
            <tr>
              <td class="text-left">
                <span class="rr-tiny-label"> Card </span>
              </td>
              <td>
                <span data-test="returns-bottom-cardPaid">
                  {{ formatCurrency(order.card_left) }}
                </span>
              </td>
            </tr>
            <tr v-if="order.ebt_left > 0">
              <td class="text-left">
                <span class="rr-tiny-label"> EBT </span>
              </td>
              <td>
                <span data-test="returns-bottom-ebtPaid">
                  {{ formatCurrency(order.ebt_left) }}
                </span>
              </td>
            </tr>
            <tr>
              <td class="text-left">
                <span class="rr-tiny-label"> GC </span>
              </td>
              <td>
                <span>
                  {{ formatCurrency(returnForm.gc) }}
                </span>
              </td>
            </tr>
          </table>
        </div>

        <!-- Return payment options -->
        <div style="width: 320px" v-if="finalize" data-test="payment-inputs">
          <PaymentInputs
            :cash.sync="returnForm.cash"
            :card.sync="returnForm.card"
            :ebt.sync="returnForm.ebt"
            :amountRemaining="returnForm.amount_remaining"
            :ebtSubTotal="returnForm.ebt_sub_total"
            :order="order"
            :forReturns="true"
            @updateGC="updateGC"
          />

          <div class="mt-4 -mb-4" v-if="confirmRefund">
            <label class="rr-field__label"> Confirm Refund </label>
            <div class="rr-field__radio">
              <input
                type="checkbox"
                v-model="givenRefund"
                :id="'refundRadio'"
                class="rr-field__radio-input"
                data-test="returns-bottom-refunded-input"
                @click="givenRefund = !givenRefund"
              />
              <label
                :for="'refundRadio'"
                class="rr-field__radio-label items-baseline"
              >
                Customer has been refunded
              </label>
            </div>
          </div>

          <!-- Payment buttons -->
          <div class="flex mt-8 mb-3">
            <!-- TODO: Add disabled validation -->
            <div
              v-if="
                givenRefund ||
                (!confirmRefund && returnForm.amount_remaining == 0)
              "
            >
              <button
                class="rr-button rr-button--lg rr-button--primary-solid flex justify-center flex-1"
                data-test="returns-bottom-finalize-button"
                @click.stop="createReturn"
                :disabled="(!givenRefund && confirmRefund) || checkingOut"
                v-text="'Finalize Return'"
              />
            </div>

            <div v-else class="w-full">
              <button
                class="rr-button rr-button--lg rr-button--primary flex justify-center w-full"
                @click="refundToGiftCard"
                v-text="`Refund to a Gift Card`"
              />
              <button
                class="rr-button rr-button--lg flex justify-center w-full mt-5"
                data-test="returns-bottom-goBack-button"
                @click.stop="updateFinalize(false)"
                v-text="`Go Back`"
              />
            </div>
          </div>
        </div>

        <!-- Finalize button -->
        <div class="flex" v-else>
          <div>
            <button
              class="rr-button rr-button--lg rr-button--primary-solid flex justify-center"
              data-test="returns-bottom-continue-button"
              @click.prevent="updateFinalize(true)"
            >
              Continue & Review
            </button>
          </div>
        </div>

        <!-- QR Code Reader Modal -->
        <modal ref="scannerModal" :size="`2xl`">
          <template v-slot:header>Scan QR Code</template>
          <template v-slot:body>
            <CameraCodeScanner
              @scan="onScan"
              @load="onLoad"
            ></CameraCodeScanner>
          </template>
        </modal>

        <!-- Checking Balance Modal -->
        <modal ref="checkingBalanceModal" :size="`2xl`">
          <template v-slot:header>Refund to Gift Card</template>
          <template v-slot:body>
            <div v-if="giftCards.isCheckingBalance" class="h3 text-center">
              Checking Balance...
            </div>

            <div v-if="giftCards.isExpired">
              <div class="h3 text-red-500 text-center">
                {{ giftCards.message }}
              </div>
            </div>

            <div v-else-if="giftCards.isDeactivated">
              <div class="h3 text-red-500 text-center">
                {{ giftCards.message }}
              </div>
            </div>

            <div v-else>
              <div v-if="giftCards.record.length == 0">
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

                <div class="mt-5">
                  <span class="font-bold">{{
                    formatCurrency(returnForm.total)
                  }}</span>
                  will be deposit to your gift card
                </div>
              </div>
            </div>
          </template>
          <template v-slot:footer>
            <div
              class="flex"
              v-if="
                !(giftCards.isExpired || giftCards.isDeactivated) &&
                giftCards.record.length != 0
              "
            >
              <button
                class="rr-button rr-button--lg rr-button--primary"
                @click="refundGiftCardConfirm"
              >
                Confirm
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
    </div>
  </div>
</template>

<script>
import { formatCurrency } from "@/helpers";

import PaymentInputs from "./PaymentInputs";
import OrderMathSummary from "./OrderMathSummary.vue";
import { mapGetters } from "vuex";
import { CameraCodeScanner } from "vue-barcode-qrcode-scanner";
import Modal from "@/components/Modal";
import moment from "moment";

export default {
  props: {
    returnForm: Object,
    order: Object,
    items: Array,
    finalize: {
      type: Boolean,
      default: false,
    },
    checkingOut: {
      type: Boolean,
      default: false,
    },
  },

  components: {
    PaymentInputs,
    OrderMathSummary,
    CameraCodeScanner,
    Modal,
  },

  data() {
    return {
      givenRefund: false,

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
        isScan: false,
      },

      currentDate: moment().format("YYYY-MM-DD"),
    };
  },

  computed: {
    ...mapGetters(["paymentPartner"]),

    confirmRefund() {
      return (
        (this.returnForm.amount_remaining == 0 && !this.paymentPartner) ||
        (this.paymentPartner &&
          (this.returnForm.cash > 0 ||
            this.returnForm.ebt > 0 ||
            this.returnForm.gc > 0))
      );
    },
  },

  methods: {
    onLoad({ controls, scannerElement, browserMultiFormatReader }) {
      console.log(controls);
      console.log(scannerElement);
      console.log(browserMultiFormatReader);
    },

    onScan({ result, raw }) {
      this.$refs.scannerModal.closeModal();
      const qrcode = result;
      this.checkGiftCardBalance(qrcode);
      // console.log(result);
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

        this.$refs.checkingBalanceModal.openModal();
      } catch (error) {
        console.error("Error fetching data:", error);
      }
    },

    refundToGiftCard() {
      this.$refs.scannerModal.openModal();
    },

    refundGiftCardConfirm() {
      this.giftCards.isScan = true;
      this.$emit("updateGC", {
        giftCardId: this.giftCards.id,
        giftCardBalance: this.giftCards.balance,
        giftCardAmount: this.returnForm.total,
      });
      this.$refs.checkingBalanceModal.closeModal();
    },

    updateGC(data) {
      this.$emit("updateGC", {
        giftCardId: data.giftCardId,
        giftCardBalance: this.giftCards.balance,
        giftCardAmount: data.giftCardAmount,
      });
    },

    createReturn() {
      this.$emit("update:checkingOut", true);
      this.$emit("create-return", true);
    },

    updateFinalize(val) {
      if (this.items.length > 0) {
        return this.$emit("update:finalize", val);
      }

      this.$toasted.show(
        "No items are ready for return, please double check you entered an action and a quantity.",
        { type: "error" }
      );
    },

    formatCurrency,
  },

  watch: {
    amount_remaining(val) {
      if (val != 0) {
        this.givenRefund = false;
      }
    },
  },
};
</script>

<style></style>
