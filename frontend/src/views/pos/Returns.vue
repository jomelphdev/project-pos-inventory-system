<template>
  <div class="container" :ref="'returns'">
    <div v-if="!order" data-test="returns-home">
      <div class="flex items-center mb-6">
        <OrderSearch
          :getDataForReturn="true"
          @order-found="(order) => (this.order = order)"
        />
        <div class="ml-2 mr-2">
          <button
            class="rr-button rr-button--lg flex justify-center"
            data-test="order-search-button"
            @click.prevent="searchPosOrders"
          >
            Search
          </button>
        </div>
        <div class="ml-2">
          <button
            class="rr-button rr-button--lg flex justify-center"
            @click.prevent="goToPos"
          >
            Back To POS
          </button>
        </div>
      </div>

      <blank-state>
        <template v-slot:body>
          <div class="grid md:grid-cols-3 grid-cols-1 gap-8">
            <div>
              <h1 class="h1">
                <span class="text-red-700">Returns</span> →
                {{ posStore.name }}
              </h1>
              <p>
                Scan or enter an Order ID (bottom of receipt) above to get
                started.
              </p>

              <div class="mt-8 font-bold">
                {{ posStore.receipt_option.name }}
              </div>

              <div>
                {{ posStore.address }}
              </div>

              <div>
                {{ posStore.city }},
                {{ posStore.state.name }}
                {{ posStore.zip }}
              </div>

              <div>
                {{ posStore.phone }}
              </div>
            </div>

            <div>
              <h1 class="h1">Orders</h1>
              <p>View a list of recent orders.</p>

              <div class="mt-8">
                <button
                  class="rr-button rr-button--lg flex justify-center"
                  data-test="returns-goToOrders-button"
                  @click="$router.push({ name: 'pos.orders' })"
                >
                  View Recent Orders
                </button>
              </div>
            </div>

            <div>
              <h1 class="h1">POS</h1>
              <p>Ready to make a sale?</p>

              <div class="mt-8 flex">
                <button
                  class="rr-button rr-button--lg flex justify-center mr-4"
                  data-test="returns-goToPos-button"
                  @click="goToPos"
                >
                  Point of Sale
                </button>
              </div>
            </div>
          </div>
        </template>
      </blank-state>
    </div>
    <div
      class="item-ui item-ui--returns"
      v-if="order && selectedItem"
      data-test="returns-main"
    >
      <div class="item-ui__main">
        <h1 class="h1">
          Return Items
          <span class="text-gray-600 font-semibold">
            {{ order.id }}
          </span>
        </h1>
        <PosItemTable
          :items="orderItems"
          :selectedItem.sync="selectedItem"
          :showRemoveOption="false"
        />
      </div>
      <div class="item-ui__aside" data-test="returns-right">
        <div class="mb-4 font-bold text-2xl">
          {{ selectedItem.title | truncate(50) }}
        </div>
        <div class="rr-field">
          <div class="rr-field__label">
            Actions

            <span
              class="rr-field__label-required"
              data-test="returns-right-action-indicator"
              v-if="!('action' in selectedItem)"
            >
              Required to add to return.
            </span>
          </div>
          <div
            v-for="action in actions"
            :key="action.id"
            class="rr-field__radio"
          >
            <input
              type="radio"
              v-model="selectedItem.action"
              :id="action.id"
              :value="action.id"
              :data-test="`returns-right-action-${action.id}`"
              class="rr-field__radio-input"
            />
            <label :for="action.id" class="rr-field__radio-label">{{
              action.name
            }}</label>
          </div>
        </div>
        <div class="rr-field">
          <label class="rr-field__label">
            Quantity Returned

            <span
              class="rr-field__label-required"
              data-test="returns-right-quantity-indicator"
              v-if="!selectedItem.quantity_returned"
            >
              Required to add to return.
            </span>
          </label>
          <input
            class="rr-field__input"
            data-test="returns-right-quantityReturned-input"
            v-model.number="selectedItem.quantity_returned"
          />
          <div class="text-xs mt-1" data-test="returns-right-quantityAvailable">
            <b>Available For Return</b>:
            {{ selectedItem.quantity_left_to_return }}
          </div>
        </div>
      </div>
      <ReturnsBottom
        :returnForm="returnForm"
        :order="order"
        :items="validItems"
        :finalize.sync="finalize"
        :checkingOut.sync="checkingOut"
        @create-return="createReturn"
        @updateGC="updateGC"
      />
    </div>

    <ReceiptFailedModal
      ref="receiptFailed"
      @print-receipt="printReceipt()"
      @reload="$root.$emit('reload-pos', true)"
    />
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import _ from "lodash";

import OrderSearch from "@/components/OrderSearch";
import PosItemTable from "@/components/PosItemTable";
import ReturnsBottom from "@/components/ReturnsBottom";
import BlankState from "@/components/BlankState";
import ReceiptFailedModal from "@/components/ReceiptFailedModal.vue";

import ReceiptMixin from "@/mixins/ReceiptMixin";

import Form from "@/classes/Form";

export default {
  mixins: [ReceiptMixin],

  data() {
    return {
      order: null,
      orderItems: [],
      returnForm: new Form({
        id: null,
        created_by: this.$store.getters.currentUser.id,
        checkout_station_id: null,
        store_id: null,
        pos_order_id: null,
        cash: 0,
        card: 0,
        ebt: 0,
        gc: 0,
        giftCardId: null,
        giftCardBalance: null,
        sub_total: 0,
        taxable_sub_total: 0,
        ebt_sub_total: 0,
        non_taxed_sub_total: 0,
        all_non_taxed_sub_total: 0,
        tax: 0,
        total: 0,
        amount_paid: 0,
        amount_remaining: 0,
        items: [],
      }),
      selectedItem: null,
      checkingOut: false,
      finalize: false,
      actions: [
        {
          id: 1,
          name: "Back To Inventory",
        },
        {
          id: 0,
          name: "Discard",
        },
      ],
      receiptFailed: false,
    };
  },

  computed: {
    ...mapGetters(["posStore", "posStation"]),
    validItems() {
      return [...this.orderItems].filter((item) => {
        return "action" in item && item.quantity_returned;
      });
    },
    itemReadyForCalculation() {
      return (
        this.selectedItem.action != null &&
        this.selectedItem.quantity_returned > 0
      );
    },
  },

  watch: {
    validItems: {
      handler(items) {
        if (items.length > 0) {
          this.returnForm.items = items;
        }
      },
      deep: true,
    },
    order(order) {
      if (order) {
        if (this.posStation) {
          this.returnForm.checkout_station_id = this.posStation.id;
        }

        this.returnForm.pos_order_id = order.id;
        this.returnForm.store_id = this.posStore.id;
        this.orderItems = order.pos_order_items;
        this.selectItem(this.orderItems[0]);
      }
    },
    finalize(bool) {
      if (!bool) {
        this.returnForm.fill({
          cash: 0,
          card: 0,
          ebt: 0,
          amount_remaining: this.returnForm.total,
          amount_paid: 0,
        });
      }
    },
    "returnForm.cash": function () {
      this.calculatePayment();
    },
    "returnForm.card": function () {
      this.calculatePayment();
    },
    "returnForm.ebt": function () {
      this.calculatePayment();
    },
    "selectedItem.action": function (val, oldValue) {
      if (this.itemReadyForCalculation && oldValue == undefined) {
        this.calculateRefund();
      }
    },
    "selectedItem.quantity_returned": function (quantity) {
      if (quantity > this.selectedItem.quantity_left_to_return) {
        this.$toasted.show("Cannot return more items than is left to return.", {
          type: "info",
        });
        this.selectedItem.quantity_returned =
          this.selectedItem.quantity_left_to_return;
      }

      if (this.itemReadyForCalculation) {
        this.calculateRefund();
      }
    },
  },

  created() {
    if (this.$route.params.order) {
      this.order = this.$route.params.order;
    }
  },

  mounted() {
    this.$store.dispatch("getPreferences");

    this.$root.$on("reload-pos", () => {
      this.order = null;
      this.orderItems = [];
      this.finalize = false;
      this.checkingOut = false;
      this.returnForm.reset();
    });

    if (window.Cypress) {
      window.PosReturns = {
        returnForm: this.returnForm,
        selectedItem: this.selectedItem,
      };
    }
  },

  components: {
    OrderSearch,
    PosItemTable,
    ReturnsBottom,
    BlankState,
    ReceiptFailedModal,
  },

  methods: {
    updateGC(data) {
      this.returnForm.cash = 0;
      this.returnForm.card = 0;
      this.returnForm.giftCardId = data.giftCardId;
      this.returnForm.gc = data.giftCardAmount;
      this.returnForm.giftCardBalance = data.giftCardBalance;
      this.calculatePayment();
    },

    selectItem(item) {
      if (!item) {
        this.clearRouteQuery();
        this.$root.$emit("reload-pos", true);
        this.$toasted.show(
          "All items from this order have already been returned.",
          { type: "error" }
        );
        return;
      }

      if (item.quantity_left_to_return > 0) {
        return (this.selectedItem = item);
      }

      const nextItem = this.orderItems.indexOf(item) + 1;
      return this.selectItem(this.orderItems[nextItem]);
    },

    calculateRefund: _.debounce(function () {
      if (this.validItems.length > 0) {
        this.$store
          .dispatch("calculateRefund", {
            orderId: this.order.id,
            items: this.validItems,
          })
          .then((refundMath) => {
            this.returnForm.fill(refundMath);
            this.returnForm.amount_remaining = refundMath.total;
          });
      }
    }, 250),

    calculatePayment() {
      if (
        this.returnForm.cash == 0 &&
        this.returnForm.card == 0 &&
        this.returnForm.ebt == 0 &&
        this.returnForm.gc == 0
      ) {
        return;
      }

      this.$store
        .dispatch("calculatePosPayment", {
          total: this.returnForm.total,
          cash: this.returnForm.cash,
          card: this.returnForm.card,
          ebt: this.returnForm.ebt,
          gc: this.returnForm.gc,
        })
        .then((paymentTotals) => {
          this.returnForm.fill(paymentTotals);
        });
    },
    createReturn() {
      if (!this.readyToPrintReceipt) {
        this.checkingOut = false;
        return this.getReadyToPrint();
      }

      if (!this.receiptFailed) {
        this.$toasted.show("Creating return...", { type: "success" });

        this.returnForm.items = [...this.validItems].map((item) => {
          return {
            pos_order_item_id: item.id,
            item_id: item.item_id,
            quantity_returned: item.quantity_returned,
            action: item.action,
            consignment_fee: item.consignment_fee ? item.consignment_fee : null,
          };
        });

        this.$store
          .dispatch("createReturn", this.returnForm.data)
          .then((returnData) => {
            this.returnForm.fill(returnData);

            this.clearRouteQuery();
            this.printReceipt();

            this.$root.$on("printed", () => {
              this.$toasted.show("Return Created!", { type: "success" });
              this.$root.$off("printed");
              this.$root.$emit("reload-pos", true);
            });
          })
          .catch(() => {
            this.checkingOut = false;
          });
      }
    },

    printReceipt() {
      let receiptData = Object.assign(this.returnForm, this.receiptData);
      this.$root.$emit("print-return-receipt", receiptData);
    },

    searchPosOrders() {
      this.$root.$emit("search-pos-orders", true);
    },

    clearRouteQuery() {
      if (this.$route.query.orderId) {
        this.$router.replace({ query: null });
      }
    },

    // UI/Button Functions
    goToPos() {
      this.$router.push({ name: "pos.index" });
    },
  },
};
</script>
