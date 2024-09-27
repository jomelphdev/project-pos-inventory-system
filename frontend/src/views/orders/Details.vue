<template>
  <div class="container">
    <transition name="fade-in" mode="out-in" appear>
      <div
        class="p-8 flex justify-center items-center"
        style="height: 65vh"
        v-if="loading"
      >
        <span class="text-sm font-medium text-gray-900">
          Loading Order...
        </span>
      </div>
      <div class="item-ui" v-else>
        <div class="item-ui__main">
          <div class="flex flex-row justify-between pr-4">
            <h1 class="h1">
              Order Details
              <span class="text-gray-600 font-semibold">
                {{ order.id }}
              </span>
            </h1>
            <div>
              <div
                class="text-sm leading-5 text-gray-700 text-right font-semibold"
              >
                {{ order.created_at | moment }}
              </div>

              <div class="text-xs leading-4 text-gray-500 text-right">
                {{ order.created_at | calendar }}
              </div>
            </div>
          </div>

          <PosItemTable
            :items="order.pos_order_items"
            :showRemoveOption="false"
          />
        </div>
        <div class="item-ui__aside">
          <div class="grid grid-cols-1 gap-2 mb-4">
            <button
              class="rr-button rr-button--lg rr-button--primary-solid flex justify-center"
              @click="
                $router.push({
                  name: 'pos.returns',
                  query: { orderId: order.id }
                })
              "
            >
              Start a Return
            </button>
            <button
              class="rr-button rr-button--lg rr-button--primary flex justify-center"
              @click="$router.push({ name: 'pos.orders' })"
            >
              Go Back
            </button>
            <!-- TODO: Print receipt -->
            <button
              class="rr-button flex justify-center py-3"
              @click="printReceipt"
            >
              Print Receipt
            </button>
          </div>

          <div class="mt-8 flex flex-col">
            <div class="text-right">
              <div class="mb-4 font-bold text-left">
                Order Summary
              </div>
              <PosOrderTotal
                :subTotal="order.sub_total"
                :tax="order.tax"
                :discount="order.discount_amount"
                :total="order.total"
              />
            </div>

            <div class="text-right mt-8">
              <div class="mb-4 font-bold text-left">
                Transactions
              </div>
              <PosPaymentTotal
                :cash="order.cash"
                :card="order.card"
                :ebt="order.ebt"
                :change="order.change"
              />
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import PosItemTable from "@/components/PosItemTable";
import PosOrderTotal from "@/components/PosOrderTotal";
import PosPaymentTotal from "@/components/PosPaymentTotal";
import moment from "moment";
import { mapGetters } from "vuex";
import ReceiptMixin from "@/mixins/ReceiptMixin";

export default {
  mixins: [ReceiptMixin],

  data() {
    return {
      loading: true,
      store: null,
      order: null
    };
  },

  components: {
    PosItemTable,
    PosOrderTotal,
    PosPaymentTotal
  },

  created() {
    this.getOrder();
  },

  computed: {
    ...mapGetters(["stores"])
  },

  filters: {
    moment(date) {
      return moment(date).fromNow();
    },
    calendar(date) {
      return moment(date).format("MMM Do [at] h:mma");
    }
  },

  methods: {
    getOrder() {
      this.loading = true;

      this.$store.dispatch("getOrder", this.$route.params.id).then(order => {
        this.loading = false;
        this.order = order;
        this.store = this.stores.find(s => s.id == order.store_id);
      });
    },

    printReceipt() {
      if (this.readyToPrintReceipt) {
        this.$toasted.show("Printing...", { type: "success" });

        const storeData = {
          receiptOptions: Object.assign(
            {
              address: this.store.address,
              city: this.store.city,
              state: this.store.state.name,
              zipcode: this.store.zip,
              phone: this.store.phone
            },
            this.store.receipt_option
          )
        };
        let receiptData = Object.assign(this.order, storeData);
        receiptData.items = this.order.pos_order_items;

        this.$root.$emit("print-order-receipt", receiptData);
      }
    }
  }
};
</script>
