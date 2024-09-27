<template>
  <div class="container" :ref="'pos'">
    <div v-if="selectedStore" data-test="pos-ui">
      <div class="flex items-center mb-6">
        <PosSearch
          ref="posSearch"
          :fireQuery.sync="query"
          @item-found="(item) => addToCart(item)"
        />
        <div class="ml-2 mr-2">
          <button
            class="rr-button rr-button--lg flex justify-center"
            data-test="pos-search-button"
            @click.stop="query = true"
          >
            Search
          </button>
        </div>
        <div class="ml-2">
          <button
            class="rr-button rr-button--lg rr-button--primary flex justify-center"
            data-test="pos-addScratchItem-button"
            @click="addScratchItem"
          >
            Add Scratch Item
          </button>
        </div>
      </div>
      <div
        class="pos-ui"
        v-if="items.length > 0 && selectedItem"
        :class="{ 'pos-ui--drawer-active': paymentMethod }"
        data-test="pos-order-ui"
      >
        <div class="pos-ui__left">
          <PosItemTable :items="items" :selectedItem.sync="selectedItem" />
          <div class="mt-6 flex justify-end">
            <button
              class="rr-button self-end"
              :class="{ 'rr-button--primary': editMode }"
              data-test="pos-editOrder-button"
              @click="editMode = true"
            >
              Edit Order
            </button>
          </div>
        </div>
        <div class="pos-ui__right">
          <PosRight
            :key="posRightKey"
            :editMode="editMode"
            :currentlyTaxed="!noTax"
            :selectedItem.sync="selectedItem"
            :order="orderForm"
            @update-price="
              (price) => {
                selectedItem.price = price;
                selectedItem.temp_price = price;
              }
            "
            @update-title="(title) => (selectedItem.title = title)"
            @update-quantity-ordered="
              (qty) => (selectedItem.quantity_ordered = qty)
            "
            @update-discount="
              (discountId) => {
                selectedItem.discount_id = discountId;
                calculatePriceForAllItems();
              }
            "
            @update-discount-amount="
              (amount) => {
                if (editMode) orderForm.discount_amount = amount;
                else {
                  selectedItem.discount_id = null;
                  selectedItem.discount_amount = amount;
                }

                calculatePriceForAllItems();
              }
            "
            @update-discount-amount-type="
              (type) => {
                if (editMode) return;

                selectedItem.discount_id = null;
                selectedItem.discount_amount_type = type;
                calculatePriceForAllItems();
              }
            "
            @update-classification="
              (classificationId) =>
                (selectedItem.classification_id = classificationId)
            "
            @apply-no-tax="(bool) => (noTax = bool)"
            @disable-edit-mode="editMode = false"
          />
        </div>
        <PosBottom
          :order="orderForm"
          :checkingOut.sync="checkingOut"
          :paymentMethod.sync="paymentMethod"
          @checkout="checkout"
          @updateGC="updateGC"
        />
      </div>
      <template v-else>
        <blank-state>
          <template v-slot:body>
            <div
              class="grid md:grid-cols-3 grid-cols-1 gap-8"
              data-test="pos-standby"
            >
              <div>
                <h1 class="h1">
                  {{ selectedStore.name }} →
                  <span class="text-blue-700">POS</span>
                </h1>
                <p>Scan or enter a SKU or UPC above to get started.</p>

                <div class="mt-8">
                  <div class="font-bold">
                    {{ selectedStore.receipt_option.name }}
                  </div>

                  <div>
                    {{ selectedStore.address }}
                  </div>

                  <div>
                    {{ selectedStore.city }},
                    {{ selectedStore.state.name }}
                    {{ selectedStore.zip }}
                  </div>

                  <div>
                    {{ selectedStore.phone }}
                  </div>
                </div>

                <div
                  class="mt-2"
                  v-if="selectedStation"
                  data-test="selectedStation-data"
                >
                  <div>
                    Station:
                    <span class="font-bold">{{ selectedStation.name }}</span>
                  </div>

                  <div v-if="paymentPartner">
                    HSN: {{ selectedStation.terminal }}
                  </div>
                </div>

                <div
                  class="mt-8 flex flex-col lg:flex-row lg:space-x-4 lg:space-y-0 md:space-y-4"
                >
                  <button
                    class="rr-button rr-button--lg justify-center"
                    data-test="pos-changeStore-button"
                    @click="clearStoreSelection"
                  >
                    Change Store
                  </button>
                  <button
                    class="rr-button rr-button--lg justify-center"
                    data-test="pos-changeStation-button"
                    @click="clearStationSelection"
                    v-if="selectedStation"
                  >
                    Change Station
                  </button>
                </div>
              </div>

              <div>
                <h1 class="h1">Orders</h1>
                <p>View a list of recent orders.</p>

                <div class="mt-8">
                  <button
                    class="rr-button rr-button--lg flex justify-center"
                    data-test="pos-goToOrders-button"
                    @click="$router.push({ name: 'pos.orders' })"
                  >
                    View Recent Orders
                  </button>
                </div>
              </div>

              <div>
                <h1 class="h1">Returns</h1>
                <p>Need to make a return?</p>

                <div class="mt-8 flex">
                  <button
                    class="rr-button rr-button--lg flex justify-center mr-4"
                    data-test="pos-startReturn-button"
                    @click="$router.push({ name: 'pos.returns' })"
                  >
                    Start a Return
                  </button>
                </div>
              </div>
            </div>
          </template>
        </blank-state>

        <div class="mx-auto my-6 grid md:grid-cols-3 grid-cols-1 gap-6">
          <money-tile
            :amount="dailySales"
            :label="`Today’s Sales`"
            data-test="pos-daily-sales-tile"
            v-if="!hide_pos_sales"
          ></money-tile>
          <money-tile
            :amount="dailyReturns"
            :label="`Today’s Returns`"
            data-test="pos-daily-returns-tile"
            v-if="!hide_pos_sales"
          ></money-tile>
          <idle-tile data-test="pos-idle-tile"></idle-tile>
        </div>
      </template>
    </div>

    <modal-wall
      ref="storeModal"
      class="centered"
      data-test="store-select-modal"
    >
      <template v-slot:header> Select A Store </template>
      <template v-slot:body>
        <div class="max-w-lg">
          Select the store you are processing orders for.
        </div>
      </template>
      <template v-slot:footer>
        <template v-if="storesVisible.length > 8">
          <select
            v-model="selectedStore"
            name="store"
            id="selected-store"
            class="rr-field__input"
          >
            <option disabled :value="null">Please Select one...</option>
            <option
              v-for="store of storesVisible"
              :value="store"
              :key="store.id"
            >
              {{ store.name }}
            </option>
          </select>
        </template>
        <template v-else>
          <div class="flex flex-col max-w-sm space-y-4 w-full">
            <button
              class="rr-button rr-button--lg rr-button--primary w-full justify-center"
              v-for="store of storesVisible"
              :data-test="`store-select-modal-store-${store.id}`"
              :key="'store_' + store.id"
              @click="selectedStore = store"
            >
              {{ store.name }}
            </button>

            <button
              class="rr-button rr-button--lg rr-button--primary w-full justify-center"
              v-if="storesVisible.length == 0"
              data-test="goToPreferences-button"
              @click="$router.push({ name: 'preferences.stores' })"
            >
              No stores created, go to preferences
            </button>
            <button
              class="rr-button rr-button--lg rr-button--primary w-full justify-center"
              v-if="storesVisible.length == 0"
              data-test="goToBack-button"
              @click="$router.go(-1)"
            >
              Go Back
            </button>
          </div>
        </template>
      </template>
    </modal-wall>

    <modal-wall
      ref="stationModal"
      class="centered"
      data-test="station-select-modal"
    >
      <template v-slot:header> Select A Station </template>
      <template v-slot:body>
        <div class="max-w-lg">
          Select the station you are processing orders on.
        </div>
      </template>
      <template v-slot:footer>
        <template v-if="stationsForStore.length > 8">
          <select
            v-model="selectedStation"
            name="station"
            id="selected-station"
            class="rr-field__input"
          >
            <option disabled :value="null">Please Select one...</option>
            <option
              v-for="station of stationsForStore"
              :value="station"
              :key="station.id"
            >
              {{ station.name }}
            </option>
          </select>
        </template>
        <template v-else>
          <div class="flex flex-col max-w-sm space-y-4 w-full">
            <button
              class="rr-button rr-button--lg rr-button--primary w-full justify-center"
              v-for="(station, index) of stationsForStore"
              :data-test="`station-select-modal-station-${index}`"
              :key="'station_' + station.id"
              @click="selectedStation = station"
            >
              {{ station.name }}
            </button>

            <button
              class="rr-button rr-button--lg rr-button--primary w-full justify-center"
              v-if="stationsForStore.length == 0"
              data-test="goToPreferences-button"
              @click="routeToStoreStations()"
            >
              No stations created, go to preferences
            </button>
            <button
              class="rr-button rr-button--lg w-full justify-center"
              data-test="modal-changeStore-button"
              @click="
                () => {
                  clearStoreSelection();
                  $refs.stationModal.closeModal();
                }
              "
            >
              Change Store
            </button>
          </div>
        </template>
      </template>
    </modal-wall>

    <modal-wall
      ref="cardTypeModal"
      class="centered"
      data-test="card-type-modal"
    >
      <template v-slot:header> Card Type </template>
      <template v-slot:body>
        Select type of card customer is paying with.
      </template>
      <template v-slot:footer>
        <div class="flex flex-col">
          <div class="flex flex-row">
            <button
              class="rr-button rr-button--lg rr-button--primary mr-4"
              data-test="credit-button"
              @click="cardTypeSelection(false)"
            >
              Credit
            </button>
            <button
              class="rr-button rr-button--lg rr-button--primary"
              data-test="debit-button"
              @click="cardTypeSelection(true)"
            >
              Debit
            </button>
          </div>

          <button
            class="rr-button rr-button--lg rr-button--danger flex justify-center mt-8"
            data-test="debit-button"
            @click="
              () => {
                checkingOut = false;
                $refs.cardTypeModal.closeModal();
              }
            "
          >
            Go Back
          </button>
        </div>
      </template>
    </modal-wall>

    <LoadingModal
      ref="loadingModal"
      loadingText="Processing Transaction..."
      :hideClose="true"
    />

    <modal-wall
      ref="orderSummaryModal"
      class="centered"
      data-test="order-summary-modal"
    >
      <template v-slot:header> Checkout Complete </template>
      <template v-slot:body>
        <div class="max-w-lg">
          <strong class="block mt-3 leading-none">Note:</strong>
          <span
            class="font-bold text-3xl"
            :class="{
              'text-green-700': orderForm.change > 0,
            }"
            data-test="order-summary-modal-change-note"
          >
            <template v-if="orderForm.change > 0">
              {{ formatCurrency(orderForm.change) }} Change Due
            </template>
            <template v-else> No Change Due </template>
          </span>

          <div class="mt-5 text-xl" v-if="orderForm.giftCardId != null">
            <span class="text-gray-700">Gift Card Balance: </span>
            <span class="font-bold">{{
              formatCurrency(orderForm.giftCardRemainingBalance)
            }}</span>
          </div>
        </div>
      </template>
      <template v-slot:footer>
        <div class="flex flex-col items-center">
          <button
            class="rr-button rr-button--lg rr-button--primary-solid mb-4"
            data-test="order-summary-modal-newOrder-button"
            @click="$root.$emit('reload-pos', true)"
          >
            Start New Order
          </button>
        </div>
      </template>
    </modal-wall>

    <modal-wall
      ref="consignmentFeeModal"
      class="centered"
      data-test="consignmentFee-modal"
    >
      <template v-slot:header> Change Consignment Fee? </template>

      <template v-slot:body>
        <div>
          Price was changed on a consignment item.<br />
          Would you like to change the fee as well or use the original
          consignment fee?<br />
          <a
            :href="`/items/${selectedItem.id}/edit?hide-consignment=true`"
            target="_blank"
            class="underline text-blue-600 inline-flex"
          >
            Show Item
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"
              />
              <path
                d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"
              />
            </svg>
          </a>
          <br />
        </div>
      </template>

      <template v-slot:footer>
        <div>
          <div class="mb-2 font-semibold" v-if="consignmentFeeEstimate">
            Estimated Consignment Fee ({{
              consingmentFeePercentage | percent(2)
            }}): {{ formatCurrency(consignmentFeeEstimate) }}
          </div>

          <div class="rr-field">
            <label class="rr-field__label text-left"> Consignment Fee </label>
            <currency-input
              class="rr-field__input"
              data-test="consignmentFee-input"
              v-model="selectedItem.consignment_fee"
            />
          </div>

          <div class="flex gap-4 justify-center">
            <button
              class="rr-button rr-button--lg rr-button--primary-solid"
              data-test="saveFee-button"
              @click="
                $toasted.show('New fee will be used for this sale only.', {
                  type: 'success',
                });
                $refs.consignmentFeeModal.closeModal();
                this.consignmentFeeEstimate = null;
              "
            >
              Save
            </button>
            <button
              class="rr-button rr-button--lg"
              data-test="keepFee-button"
              @click="
                $refs.consignmentFeeModal.closeModal();
                this.consignmentFeeEstimate = null;
              "
            >
              Keep Original Fee
            </button>
          </div>
        </div>
      </template>
    </modal-wall>

    <ReceiptFailedModal
      ref="receiptFailedModal"
      data-test="receipt-failed-modal"
      @print-receipt="printReceipt()"
      @reload="$root.$emit('reload-pos', true)"
    />
  </div>
</template>

<script>
import moment from "moment";
import { mapGetters } from "vuex";
import { formatCurrency } from "@/helpers.js";
import _ from "lodash";

import PosSearch from "@/components/PosSearch";
import PosItemTable from "@/components/PosItemTable";
import PosRight from "@/components/PosRight";
import PosBottom from "@/components/PosBottom";
import ModalWall from "@/components/ModalWall";
import BlankState from "@/components/BlankState";
import MoneyTile from "@/components/tiles/MoneyTile";
import IdleTile from "@/components/tiles/IdleTile";
import ReceiptFailedModal from "@/components/ReceiptFailedModal.vue";
import LoadingModal from "@/components/LoadingModal";

import ReceiptMixin from "@/mixins/ReceiptMixin";

import Form from "@/classes/Form";

export default {
  mixins: [ReceiptMixin],

  data() {
    return {
      items: [],
      orderForm: new Form({
        id: null,
        created_by: this.$store.getters.currentUser.id,
        checkout_station_id: null,
        store_id: null,
        cash: 0,
        card: 0,
        ebt: 0,
        gc: 0,
        giftCardId: null,
        giftCardAmount: null,
        giftCardRemainingBalance: 0,
        giftCode: null,
        sub_total: 0,
        taxable_sub_total: 0,
        ebt_sub_total: 0,
        non_taxed_sub_total: 0,
        all_non_taxed_sub_total: 0,
        prior_sub_total: 0,
        tax: 0,
        total: 0,
        discount_amount: 0,
        amount_paid: 0,
        amount_remaining: 0,
        change: 0,
        tax_rate: 0,
        items: [],
        ebt_eligible: false,
      }),
      selectedStore: null,
      selectedStation: null,
      selectedItem: null,
      checkingOut: false,
      paymentMethod: null,
      query: false,
      addedItemNumber: 0,
      posRightKey: 0,
      editMode: false,
      noTax: false,
      dailySales: 0,
      dailyReturns: 0,
      receiptFailed: false,
      isDebit: null,
      processingDetails: null,
      consignmentFeeEstimate: null,
      consingmentFeePercentage: null,
    };
  },

  components: {
    PosSearch,
    PosItemTable,
    PosRight,
    PosBottom,
    ModalWall,
    BlankState,
    MoneyTile,
    IdleTile,
    ReceiptFailedModal,
    LoadingModal,
  },

  computed: {
    ...mapGetters([
      "storesVisible",
      "currentUser",
      "posStore",
      "posStation",
      "paymentPartner",
      "stationsVisible",
      "hide_pos_sales",
      "classifications_disabled",
    ]),

    validItems() {
      return this.items.filter((item) => {
        const hasTotalData =
          (item.price > 0 ||
            (item.price == 0 && (item.discount_id || item.discount_amount))) &&
          item.quantity_ordered > 0;

        return (
          (hasTotalData &&
            item.added_item &&
            !!item.title &&
            (item.classification_id || this.classifications_disabled)) ||
          !item.added_item
        );
      });
    },

    readyForCheckout() {
      return this.items.length == this.validItems.length;
    },

    mostRecentCartItem() {
      return this.items[this.items.length - 1];
    },

    itemReadyForCalculation() {
      return (
        (this.selectedItem.price > 0 ||
          (this.selectedItem.price == 0 &&
            (!!this.selectedItem.discount_id ||
              !!this.selectedItem.discount_amount))) &&
        this.selectedItem.quantity_ordered > 0 &&
        (!!this.selectedItem.classification_id ||
          this.classifications_disabled) &&
        !!this.selectedItem.title
      );
    },

    stationsForStore() {
      return this.stationsVisible.filter(
        (station) =>
          station.store_id == this.posStore.id &&
          (this.paymentPartner || station.drawer_balance != null)
      );
    },

    cardTypeRequired() {
      return (
        this.orderForm.card > 0 && this.paymentPartner && this.isDebit === null
      );
    },

    terminalHsn() {
      return this.selectedStation ? this.selectedStation.terminal : null;
    },

    discountPerItem() {
      return Math.round(
        this.orderForm.discount_amount /
          this.items.reduce((a, b) => {
            if (b.item_specific_discount_id) return a;
            return a + b.quantity_ordered;
          }, 0)
      );
    },

    isNoPayment() {
      return (
        this.orderForm.cash == 0 &&
        this.orderForm.card == 0 &&
        this.orderForm.ebt == 0 &&
        this.orderForm.gc == 0
      );
    },
  },

  watch: {
    selectedStore(store) {
      this.getTodaysSales();
      this.$store.dispatch("selectStore", store);

      if (!store) {
        return this.$refs.storeModal.openModal();
      }

      if (
        this.stationsForStore.length > 0 ||
        (this.paymentPartner && !this.selectedStation)
      ) {
        this.$refs.stationModal.openModal();
      }

      this.$refs.storeModal.closeModal();
      this.orderForm.update({
        store_id: store.id,
        tax_rate: store.tax_rate,
      });
    },
    selectedStation(station) {
      this.$store.dispatch("selectStation", station);

      if (
        station &&
        (!this.paymentPartner || (this.paymentPartner && !!station.terminal))
      ) {
        this.$refs.stationModal.closeModal();
        this.orderForm.update({ checkout_station_id: station.id });
      } else if (this.selectedStore) {
        this.$refs.stationModal.openModal();
      }
    },
    validItems: {
      handler(items) {
        if (items.length > 0) {
          this.orderForm.items = items;
        }
      },
      deep: true,
    },
    paymentMethod(method) {
      if (!method) {
        this.orderForm.fill({
          cash: 0,
          card: 0,
          ebt: 0,
          amount_remaining: this.orderForm.total,
          amount_paid: 0,
          change: 0,
        });
      } else if (!this.readyForCheckout) {
        this.paymentMethod = null;

        return this.$toasted.show(
          "Order is not ready for checkout. Please review and try again.",
          { type: "error" }
        );
      } else if (method == "ebt") {
        this.calculateOrderTotals();
      }
    },
    noTax(bool) {
      this.items.forEach((i) => (i.is_taxed = !bool));
      this.calculateOrderTotals();
    },
    discountPerItem(val) {
      if (val <= 0) {
        this.validItems.forEach((i) => {
          i.discount_amount = null;
        });
      } else {
        this.validItems.forEach((i) => {
          if (i.item_specific_discount_id) return;

          i.discount_id = null;
          i.discount_amount = val;
          i.discount_amount_type = "order_total";
        });

        this.calculatePriceForAllItems();
      }
    },
    "orderForm.cash": function (amount) {
      if (amount == null) {
        return (this.orderForm.cash = 0);
      }

      this.calculatePayment();
    },
    "orderForm.card": function (amount) {
      if (amount == null) {
        return (this.orderForm.card = 0);
      }

      this.calculatePayment();
    },
    "orderForm.ebt": function (amount) {
      if (amount == null) {
        return (this.orderForm.ebt = 0);
      }

      this.calculatePayment();
    },
    "selectedItem.price": async function () {
      if (this.itemReadyForCalculation) {
        if (
          this.selectedItem.discount_amount &&
          this.selectedItem.discount_amount_type === "total"
        )
          await this.calculatePriceForAllItems();

        this.calculateOrderTotals();
      }

      if (
        this.selectedItem.consignment_fee &&
        this.selectedItem.original_price != this.selectedItem.price
      ) {
        this.$refs.consignmentFeeModal.openModal();
        this.calculateConsignmentFee();
      }
    },
    "selectedItem.quantity_ordered": async function () {
      if (this.itemReadyForCalculation) {
        if (
          this.selectedItem.discount_amount &&
          this.selectedItem.discount_amount_type === "total"
        )
          await this.calculatePriceForAllItems();

        this.calculateOrderTotals();
      }
    },
    "selectedItem.classification_id": function () {
      if (this.itemReadyForCalculation) {
        this.calculateOrderTotals();
      }
    },
    "selectedItem.title": function () {
      if (this.itemReadyForCalculation) {
        this.calculateOrderTotals();
      }
    },
    isNoPayment(bool) {
      if (bool) {
        this.orderForm.amount_remaining = this.orderForm.total;
      }
    },
  },

  mounted() {
    this.$store.dispatch("getPreferences");

    const vuexStoreFound = this.posStore
      ? this.storesVisible.find((store) => store.id == this.posStore.id)
      : false;

    if (!vuexStoreFound) {
      this.$refs.storeModal.openModal();

      if (this.paymentPartner && this.posStation) {
        this.$store.dispatch("selectStation", null);
      }
    } else {
      this.selectedStore = this.posStore;

      if (this.posStation && this.posStation.store_id == this.posStore.id) {
        this.selectedStation = this.posStation;
      }
    }

    if (this.storesVisible.length == 1) {
      this.selectedStore = this.storesVisible[0];
    } else if (vuexStoreFound) {
      this.selectedStore = this.posStore;
    } else {
      this.$refs.storeModal.openModal();
    }

    this.$root.$on("remove-item", (item) => {
      this.removeItemFromCart(item.id);
      this.selectItem(this.mostRecentCartItem);
      this.calculateOrderTotals();
    });

    this.$root.$on("order-wide-discount", (discount) => {
      this.applyOrderDiscount(discount);
    });

    this.$root.$on("reload-pos", () => {
      this.addedItemNumber = 0;
      this.orderForm.reset();
      this.items = [];
      this.paymentMethod = null;
      this.noTax = false;
      this.isDebit = null;
      this.checkingOut = false;

      this.$refs.orderSummaryModal.closeModal();
      this.$refs.posSearch.focusInput();
      this.getTodaysSales();
    });

    if (window.Cypress) {
      window.PosIndex = {
        items: this.validItems,
        order: this.orderForm,
      };
    }
  },

  beforeDestroy() {
    this.$root.$off("remove-item");
    this.$root.$off("order-wide-discount");
    this.$root.$off("reload-pos");
  },

  methods: {
    updateGC(data) {
      this.orderForm.gc = data.amount;
      this.orderForm.giftCardRemainingBalance = data.remainingBalance;
      this.orderForm.giftCode = data.giftCode;
      this.orderForm.giftCardId = data.giftCardId;
      this.orderForm.giftCardAmount = data.giftCardAmount;
      this.calculatePayment();
    },

    addToCart(item) {
      if (!item.temp_price) {
        item.original_price = item.price;
        item.temp_price = item.price;
      }

      const existingOrderItem = this.items.find((i) => i.id == item.id);
      if (existingOrderItem) {
        existingOrderItem.quantity_ordered += 1;

        return this.handleAddedCartItem(existingOrderItem);
      }

      item.quantity_ordered = 1;
      item.discount_amount = null;
      item.item_specific_discount_id = null;
      item.item_specific_discount_can_stack = null;
      this.items.unshift(item);
      this.handleAddedCartItem(item);
      this.calculateOrderTotals();
    },

    addScratchItem() {
      this.addedItemNumber += 1;

      const item = {
        id: `addedItem_${this.addedItemNumber}`,
        title: `Added Item #${this.addedItemNumber}`,
        price: 0,
        item_images: [],
        quantity_ordered: 1,
        sku: "",
        added_item: true,
        classification_id: null,
        discount_amount: null,
      };

      this.items.unshift(item);
      this.handleAddedCartItem(item);
    },

    handleAddedCartItem(item) {
      this.$refs.posSearch.focusInput();
      this.selectItem(item);

      if (this.orderForm.discount_amount > 0) {
        this.orderForm.discount_amount = 0;
        this.calculatePriceForAllItems();
      }

      if (this.paymentMethod) {
        this.paymentMethod = null;
      }

      if (this.items.length == 1) {
        this.scrollDown();
      }
    },

    selectItem(item) {
      this.editMode = false;
      this.selectedItem = item;
    },

    removeItemFromCart(itemId) {
      const index = this.items.findIndex((i) => i.id == itemId);
      let item = this.items[index];

      if (item.quantity_ordered > 1) {
        return (item.quantity_ordered -= 1);
      }

      this.items.splice(index, 1);
    },

    calculateOrderTotals: _.debounce(function () {
      const items = this.validItems.map((item) => {
        return {
          id: item.id,
          price: item.price,
          discount_id: item.discount_id ? item.discount_id : null,
          discount_amount: item.discount_amount ? item.discount_amount : null,
          quantity_ordered: item.quantity_ordered,
          added_item: item.added_item,
          classification_id: item.added_item ? item.classification_id : null,
        };
      });

      if (items.length > 0) {
        this.$store
          .dispatch("calculateOrderTotals", {
            items: items,
            is_ebt: this.paymentMethod == "ebt",
            is_taxed: !this.noTax,
            discount_amount: this.orderForm.discount_amount,
          })
          .then((orderMath) => {
            if (
              this.paymentMethod == "ebt" &&
              orderMath.ebt_eligible == false
            ) {
              this.paymentMethod = null;
              return this.$toasted.show(
                "This order is not eligible for EBT. Please try cash or card.",
                { type: "error" }
              );
            }

            this.orderForm.fill(orderMath);
            this.orderForm.amount_remaining = orderMath.total;

            for (let totalObj of orderMath.item_totals.totals) {
              let item = this.items.find((i) => i.id == totalObj.id);

              if (item.total != totalObj.price) {
                this.$set(item, "total", totalObj.price);
              }

              item.is_taxed = totalObj.is_taxed;
              item.item_specific_discount_quantity =
                totalObj.item_specific_discount_quantity;
              item.item_specific_discount_original_amount =
                totalObj.item_specific_discount_original_amount;
              item.item_specific_discount_amount =
                totalObj.item_specific_discount_amount;
              item.item_specific_discount_type =
                totalObj.item_specific_discount_type;
              this.$set(
                item,
                "item_specific_discount_id",
                totalObj.item_specific_discount_id
              );
              this.$set(
                item,
                "item_specific_discount_times_applied",
                totalObj.item_specific_discount_times_applied
              );
              this.$set(
                item,
                "item_specific_discount_can_stack",
                totalObj.item_specific_discount_can_stack
              );
              this.$set(
                item,
                "discount_description",
                totalObj.discount_description
              );
              this.$set(
                item,
                "item_specific_discount_active_at",
                totalObj.item_specific_discount_active_at
              );
              this.$set(
                item,
                "item_specific_discount_expires_at",
                totalObj.item_specific_discount_expires_at
              );
            }
          });
      }
    }, 400),

    calculatePayment() {
      if (
        this.orderForm.cash == 0 &&
        this.orderForm.card == 0 &&
        this.orderForm.ebt == 0 &&
        this.orderForm.gc == 0
      ) {
        return;
      }

      this.$store
        .dispatch("calculatePosPayment", {
          total: this.orderForm.total,
          cash: this.orderForm.cash,
          card: this.orderForm.card,
          ebt: this.orderForm.ebt,
          gc: this.orderForm.gc,
        })
        .then((paymentTotals) => {
          this.orderForm.fill(paymentTotals);
        });
    },

    calculateConsignmentFee() {
      this.$store
        .dispatch("calculateConsignmentFee", {
          consignor_id: this.selectedItem.consignor_id,
          price: this.selectedItem.price,
        })
        .then(({ consignment_fee, consignment_fee_percentage }) => {
          this.consignmentFeeEstimate = consignment_fee;
          this.consingmentFeePercentage = consignment_fee_percentage;
        });
    },

    async applyOrderDiscount(discountId = null) {
      await this.items.forEach((item) => {
        if (item.discount_id == discountId || item.discount_amount) {
          return;
        }

        item.discount_id = discountId;
      });

      this.calculatePriceForAllItems();
    },

    calculatePriceForAllItems: _.debounce(function () {
      if (this.validItems.length == 0) return;

      const itemData = this.validItems.map((i) => {
        return {
          id: i.id,
          price: i.temp_price ? i.temp_price : i.price,
          discount_id: i.discount_id,
          discount_amount: i.discount_amount,
          discount_amount_type: i.discount_amount_type,
          quantity_ordered: i.quantity_ordered,
        };
      });

      this.$store
        .dispatch("calculatePriceForMultipleItems", itemData)
        .then((prices) => {
          prices.forEach((obj) => {
            const item = this.items.find((item) => item.id == obj.id);
            item.price = obj.price;
          });

          this.calculateOrderTotals();
        });
    }, 250),
    checkout() {
      this.orderForm.gc =
        this.orderForm.gc != undefined ? this.orderForm.gc : 0;

      if (!this.readyToPrintReceipt) {
        this.checkingOut = false;
        return this.getReadyToPrint();
      }

      if (this.cardTypeRequired) {
        return this.$refs.cardTypeModal.openModal();
      }

      if (this.orderForm.amount_paid < this.orderForm.total) {
        return this.$toasted.show(
          `Customer has not paid full amount of order.`,
          {
            type: "error",
          }
        );
      }

      if (!this.receiptFailed && this.items.length == this.validItems.length) {
        if (this.isDebit != null) {
          this.$refs.loadingModal.openModal();
        }

        this.$toasted.show("Creating order...", { type: "success" });

        this.$store
          .dispatch("createOrder", {
            ...this.orderForm.data,
            terminal_hsn: this.terminalHsn,
            is_debit: this.isDebit,
          })
          .then((order) => {
            this.processingDetails = order.processing_details
              ? order.processing_details
              : null;
            this.orderForm.fill(order);

            this.printReceipt();
            this.$root.$on("printed", () => {
              this.$root.$off("printed");
              this.$refs.orderSummaryModal.openModal();
              this.$refs.loadingModal.closeModal();
            });
          })
          .catch((receiptFailed) => {
            this.checkingOut = false;
            this.$refs.loadingModal.closeModal();

            if (receiptFailed) {
              this.receiptFailed = true;
              this.$refs.receiptFailedModal.openModal();
            }
          });
      }
    },

    printReceipt() {
      let receiptData = Object.assign(this.orderForm, this.receiptData, {
        processing_details: this.processingDetails,
      });
      this.$root.$emit("print-order-receipt", receiptData);
    },

    // UI/Button FUNCTIONS
    getTodaysSales() {
      if (this.selectedStore && !this.hide_pos_sales) {
        this.$store
          .dispatch("getDailySalesReportData", {
            storeId: this.selectedStore.id,
            date: moment().format(),
            options: {
              hideMessages: true,
            },
          })
          .then((salesData) => {
            this.dailySales = parseInt(salesData.order_totals.total) / 100;
            this.dailyReturns = parseInt(salesData.return_totals.total) / 100;
          });
      }
    },

    scrollDown() {
      setTimeout(() => {
        window.scrollTo(0, this.$refs["pos"].offsetTop);
      }, 350);
    },

    clearStoreSelection() {
      this.selectedStore = null;
      this.clearStationSelection();
    },

    clearStationSelection() {
      this.selectedStation = null;
    },

    routeToStoreStations() {
      this.$router.push({
        name: "preferences.checkoutStations",
        query: { store_id: this.posStore.id },
      });
    },

    cardTypeSelection(isDebit) {
      this.$refs.cardTypeModal.closeModal();
      this.isDebit = isDebit;

      this.checkout();
    },

    formatCurrency,
  },
};
</script>
