<template>
  <div>
    <div class="flex flex-row justify-between">
      <h1 class="h1" v-text="`Gift Card Report`" />
    </div>

    <template>
      <div class="bg-white shadow-lg rounded-md p-12">
        <DateRange v-model="selectedDate" />
      </div>

      <div
        v-if="hasTotals"
        class="mx-auto my-6 grid md:grid-cols-2 grid-cols-1 gap-6"
      >
        <MoneyTile
          :amount="overallTotalSold / 100"
          :label="`Total Sold from Gift Card`"
        ></MoneyTile>
        <MoneyTile
          :amount="overallTotalReturned / 100"
          :label="`Total Refunded to Gift Card`"
        ></MoneyTile>
        <NumberTile :label="`Items Sold`">
          <template v-slot:body>
            <div class="number-tile__value">
              {{ itemSold }}
            </div>
          </template>
        </NumberTile>
        <NumberTile :label="`Items Returned`">
          <template v-slot:body>
            <div class="number-tile__value">
              {{ itemReturned }}
            </div>
          </template>
        </NumberTile>
      </div>

      <!-- Orders Table -->
      <div v-if="orders.length > 0" class="my-8">
        <div
          class="font-bold mb-4"
          style="cursor: pointer"
          @click="hideOrdersTable = !hideOrdersTable"
        >
          Orders
          <span class="rr-pill rr-pill--default">
            {{ Object.keys(orders).length }}
          </span>
        </div>

        <DataTable>
          <DataTableHeader @click.native="hideOrdersTable = !hideOrdersTable">
            <DataTableHeaderCell>Order #</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">GC</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Cash</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">EBT</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Sub-Total</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Tax</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Total</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Items</DataTableHeaderCell>
          </DataTableHeader>

          <DataTableBody>
            <DataTableRowShowDetails
              @click.native="hideOrdersTable = !hideOrdersTable"
              v-show="hideOrdersTable"
            >
              Show details for {{ orders.length }} orders
            </DataTableRowShowDetails>

            <DataTableRow
              v-for="o in orders"
              :key="o.id"
              :style="{ display: hideOrdersTable ? 'none' : '' }"
            >
              <DataTableCell>{{ o.pos_order_id }}</DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(o.gc) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(o.cash) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(o.ebt) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(o.sub_total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(o.tax) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(o.total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ o.total_quantity_ordered }}
              </DataTableCell>
            </DataTableRow>

            <DataTableRow
              :highlight="true"
              @click.native="hideOrdersTable = !hideOrdersTable"
            >
              <DataTableCell> Totals </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(orderTotals.giftCardAmount) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(orderTotals.cash) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(orderTotals.ebt) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(orderTotals.subTotal) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(orderTotals.tax) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(orderTotals.total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ orderTotals.quantityOrdered }}
              </DataTableCell>
            </DataTableRow>
          </DataTableBody>
        </DataTable>
      </div>

      <!-- Returns Table -->
      <div v-if="returns.length > 0" class="my-8">
        <div
          class="font-bold mb-4"
          style="cursor: pointer"
          @click="hideReturnsTable = !hideReturnsTable"
        >
          Returns
          <span class="rr-pill rr-pill--default">
            {{ Object.keys(returns).length }}
          </span>
        </div>

        <DataTable>
          <DataTableHeader @click.native="hideReturnsTable = !hideReturnsTable">
            <DataTableHeaderCell>Order #</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">GC</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Sub-Total</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Tax</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Total</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Items</DataTableHeaderCell>
          </DataTableHeader>

          <DataTableBody>
            <DataTableRowShowDetails
              @click.native="hideReturnsTable = !hideReturnsTable"
              v-show="hideReturnsTable"
            >
              Show details for {{ returns.length }} returns
            </DataTableRowShowDetails>

            <DataTableRow
              v-for="o in returns"
              :key="o.id"
              :style="{ display: hideReturnsTable ? 'none' : '' }"
            >
              <DataTableCell>{{ o.pos_order_id }}</DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(o.gc) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(o.sub_total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(o.tax) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(o.total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ o.total_quantity_returned }}
              </DataTableCell>
            </DataTableRow>

            <DataTableRow
              :highlight="true"
              @click.native="hideReturnsTable = !hideReturnsTable"
            >
              <DataTableCell> Totals </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(returnTotals.giftCardAmount) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(returnTotals.subTotal) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(returnTotals.tax) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(returnTotals.total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ returnTotals.quantityReturned }}
              </DataTableCell>
            </DataTableRow>
          </DataTableBody>
        </DataTable>
      </div>
    </template>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import { required } from "vuelidate/lib/validators";

import DateRange from "@/components/DateRange.vue";
import { formatCurrency } from "@/helpers";
import dataTableMixin from "@/components/table/dataTableMixin";

import ReportsMixin from "@/mixins/ReportsMixin";

import MoneyTile from "@/components/tiles/MoneyTile";
import NumberTile from "@/components/tiles/NumberTile.vue";

export default {
  name: "SalesReport",

  mixins: [ReportsMixin, dataTableMixin],

  components: {
    DateRange,
    MoneyTile,
    NumberTile,
  },

  data() {
    return {
      selectedDate: null,

      hideOrdersTable: true,
      hideReturnsTable: true,

      orders: [],
      orderTotals: {},

      returns: [],
      returnTotals: {},

      hasTotals: false,

      overallTotalSold: 0,
      overallTotalReturned: 0,
      itemSold: 0,
      itemReturned: 0,
    };
  },

  computed: {
    ...mapGetters(["storesVisible"]),

    readyForQuery() {
      return this.selectedDate.start && this.selectedDate.end;
    },
  },

  watch: {
    selectedDate: {
      handler() {
        if (this.readyForQuery) {
          this.getGiftCardReportData();
        }
      },
      deep: true,
    },
  },

  methods: {
    getGiftCardReportData() {
      this.$toasted.show("Loading report...", { type: "success" });

      this.$store
        .dispatch("getGiftCardReportData", {
          startDate: this.selectedDate.start,
          endDate: this.selectedDate.end,
        })
        .then((giftCardData) => {
          this.hasTotals = true;

          this.orders = giftCardData.orders;
          this.orderTotals = giftCardData.orderTotals;

          this.returns = giftCardData.returns;
          this.returnTotals = giftCardData.returnTotals;

          this.overallTotalSold = giftCardData.overallTotalSold;
          this.overallTotalReturned = giftCardData.overallTotalReturned;
          this.itemSold = giftCardData.itemSold;
          this.itemReturned = giftCardData.itemReturned;

          this.$toasted.clear();
          this.$toasted.show("Loaded", { type: "success" });
        });
    },

    formatCurrency,
  },

  validations() {
    return {
      selectedDate: {
        required,
      },
    };
  },
};
</script>
