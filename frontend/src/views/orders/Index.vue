<template>
  <div class="container" :ref="'orders'">
    <div class="rr-field">
      <label class="rr-field__label"> Item Search </label>

      <input
        class="rr-field__input"
        data-test="item-search-input"
        v-model="query"
        placeholder="Search for orders with SKU or UPC"
      />
    </div>
    <div class="grid grid-cols-2 gap-x-4">
      <DatePicker
        label="Date From"
        @date-selected="(date) => (dateFrom = formatDate(date))"
      />
      <DatePicker
        label="Date To"
        @date-selected="(date) => (dateTo = formatDate(date))"
      />
    </div>
    <OrdersTable :orders="orders" />
  </div>
</template>

<script>
import OrdersTable from "@/components/OrdersTable";
import _ from "lodash";
import DatePicker from "../../components/DatePicker.vue";
import moment from "moment";

export default {
  components: {
    OrdersTable,
    DatePicker,
  },

  data() {
    return {
      orders: [],
      moreToQuery: true,
      querying: false,
      lastSeenId: null,
      query: null,
      dateTo: null,
      dateFrom: null,
    };
  },

  watch: {
    query(val, prev) {
      if (
        (/^\d+$/.test(val) && (val.length == 10 || val.length == 12)) ||
        (val == "" && prev != "")
      ) {
        this.resetAndQuery();
      }
    },
    dateFrom() {
      this.resetAndQuery();
    },
    dateTo() {
      this.resetAndQuery();
    },
  },

  mounted() {
    this.getOrders();
    window.addEventListener("scroll", this.checkIfBottomOfPage);
  },

  methods: {
    resetAndQuery() {
      this.orders = [];
      this.moreToQuery = true;
      this.lastSeenId = null;
      this.getOrders();
    },

    formatDate(date) {
      if (date) return moment(date).format("YYYY-MM-DD");
    },

    getOrders: _.debounce(function () {
      if (this.moreToQuery) {
        this.querying = true;

        this.$store
          .dispatch("getOrders", {
            last_seen_id: this.lastSeenId,
            query: this.query,
            date_to: this.dateTo,
            date_from: this.dateFrom,
          })
          .then((orders) => {
            if (orders.length < 30) {
              this.moreToQuery = false;
              this.$toasted.show("There are no more orders to load.", {
                type: "info",
              });
            }

            this.querying = false;
            this.lastSeenId = orders[orders.length - 1].id;
            this.orders = this.orders.concat(orders);
          });
      }
    }, 300),

    checkIfBottomOfPage: _.throttle(function () {
      let bottomOfPage =
        Math.max(
          window.pageYOffset,
          document.documentElement.scrollTop,
          document.body.scrollTop
        ) +
          window.innerHeight >
        document.documentElement.offsetHeight - 250;

      if (bottomOfPage) {
        if (!this.querying) {
          this.queryPage += 1;
          this.getOrders();
        }
      }
    }, 200),
  },

  beforeDestroy() {
    window.removeEventListener("scroll", this.checkIfBottomOfPage);
  },
};
</script>
