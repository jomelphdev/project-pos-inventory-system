<template>
  <div>
    <div class="flex flex-row justify-between">
      <h1 class="h1" v-text="`Daily Sales`" />

      <!-- TODO: Future Layout – Remove v-if="false" to enable -->
      <div v-if="false">
        <select
          class="rr-button font-bold appearance-none"
          v-model="selectedDate"
        >
          <option
            v-for="range in dates"
            :key="range.value"
            :value="range.value"
            v-text="range.label"
          ></option>
        </select>
      </div>
    </div>

    <template>
      <div class="bg-white shadow-lg rounded-md px-12 pt-8 pb-6 mb-8">
        <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
          <div>
            <DatePicker
              :default="new Date()"
              @date-selected="date => (selectedDate = date)"
            />

            <div
              class="rr-field__radio mr-4 mb-4 inline-block"
              v-if="canSortByStation"
            >
              <input
                type="checkbox"
                :id="`sort-by-station`"
                class="rr-field__radio-input"
                :checked="forStations"
                @click="forStations = !forStations"
              />
              <label
                :for="`sort-by-station`"
                class="rr-field__radio-label items-baseline"
              >
                Sorted By Station
              </label>
            </div>
          </div>

          <div class="rr-field rr-field--select">
            <label class="rr-field__label">
              Store
              <span
                class="rr-field__label-required"
                v-if="!$v.selectedStore.required"
              >
                Required
              </span>
            </label>
            <select class="rr-field__input" v-model="selectedStore">
              <option :value="null" disabled>Select a Store</option>
              <option
                v-for="store in storesVisible"
                :key="store.id"
                :value="store.id"
                v-text="store.name"
              ></option>
            </select>
          </div>
        </div>
      </div>

      <div
        v-if="totals"
        class="mx-auto my-6 grid md:grid-cols-2 grid-cols-1 gap-6"
      >
        <MoneyTile :amount="totals.total / 100" :label="`Total`"></MoneyTile>
        <NumberTile :label="`Items Sold`">
          <template v-slot:body>
            <div class="number-tile__value">
              {{ totals.items }}
            </div>
          </template>
        </NumberTile>
      </div>

      <!-- Orders Table -->
      <div v-if="orders.length > 0" class="my-8">
        <div
          class="font-bold mb-4 cursor-pointer"
          @click="hideOrdersTable = !hideOrdersTable"
        >
          Orders
          <span class="rr-pill rr-pill--default">
            {{ orders.length }}
          </span>
        </div>

        <DataTable>
          <DataTableHeader @click.native="hideOrdersTable = !hideOrdersTable">
            <DataTableHeaderCell>Order #</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Cash</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Charge</DataTableHeaderCell>
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
              @click.native="takeToOrderDetails(o.id)"
            >
              <DataTableCell>{{ o.id }}</DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(o.cash) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(o.card) }}
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
                {{ o.quantity_ordered }}
              </DataTableCell>
            </DataTableRow>

            <DataTableRow
              :highlight="true"
              @click.native="hideOrdersTable = !hideOrdersTable"
            >
              <DataTableCell>
                Totals
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(orderTotals.cash) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(orderTotals.card) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(orderTotals.ebt) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(orderTotals.sub_total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(orderTotals.tax) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(orderTotals.total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ orderTotals.items }}
              </DataTableCell>
            </DataTableRow>
          </DataTableBody>
        </DataTable>
      </div>

      <!-- Returns Table -->
      <div v-if="returns.length > 0" class="my-8">
        <div
          class="font-bold mb-4"
          style="cursor: pointer;"
          @click="hideReturnsTable = !hideReturnsTable"
        >
          Returns
          <span class="rr-pill rr-pill--default">
            {{ returns.length }}
          </span>
        </div>

        <DataTable>
          <DataTableHeader @click.native="hideReturnsTable = !hideReturnsTable">
            <DataTableHeaderCell>Order #</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Cash</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Charge</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">EBT</DataTableHeaderCell>
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
              v-for="r in returns"
              :key="r.id"
              :style="{ display: hideReturnsTable ? 'none' : '' }"
              @click.native="takeToOrderDetails(r.pos_order_id)"
            >
              <DataTableCell>{{ r.pos_order_id }}</DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(r.cash) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(r.card) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(r.ebt) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(r.sub_total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(r.tax) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(r.total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ r.quantity_returned }}
              </DataTableCell>
            </DataTableRow>

            <DataTableRow
              :highlight="true"
              @click.native="hideReturnsTable = !hideReturnsTable"
            >
              <DataTableCell>
                Totals
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(returnTotals.cash) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(returnTotals.card) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(returnTotals.ebt) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(returnTotals.sub_total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(returnTotals.tax) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(returnTotals.total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ returnTotals.items }}
              </DataTableCell>
            </DataTableRow>
          </DataTableBody>
        </DataTable>
      </div>

      <!-- Totals Table -->
      <div
        v-if="(totals && orders.length > 0) || returns.length > 0"
        class="my-8"
      >
        <div class="font-bold mb-4">
          Totals
        </div>

        <DataTable>
          <DataTableHeader>
            <DataTableHeaderCell>Order #</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Cash</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Charge</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">EBT</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Sub-Total</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Tax</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Total</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Items</DataTableHeaderCell>
          </DataTableHeader>

          <DataTableBody>
            <DataTableRow :highlight="true">
              <DataTableCell>
                Totals
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(totals.cash) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(totals.card) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(totals.ebt) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(totals.sub_total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(totals.tax) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(totals.total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ totals.items }}
              </DataTableCell>
            </DataTableRow>
          </DataTableBody>
        </DataTable>
      </div>

      <div class="flex flex-row mt-12 mb-6">
        <button
          class="rr-button rr-button--lg rr-button--primary"
          :disabled="$v.$invalid"
          v-text="`Download Excel Report`"
          @click="downloadReport()"
        />
      </div>
    </template>

    <!-- <template v-else>
      <loading-panel :timer="true">
        <template v-slot:title>
          Generating Report → {{ fileName }}.xlsx
        </template>
        <template v-slot:text>
          Note: Reports can take several minutes to generate.</template
        >
      </loading-panel>
    </template> -->
    <ReportDirectory :report_type="'daily_sales'" />
  </div>
</template>

<script>
import { formatCurrency } from "@/helpers";
import { mapGetters } from "vuex";
import moment from "moment";
import { required } from "vuelidate/lib/validators";

import DatePicker from "@/components/DatePicker.vue";
import dataTableMixin from "@/components/table/dataTableMixin";
import MoneyTile from "@/components/tiles/MoneyTile";
import NumberTile from "@/components/tiles/NumberTile.vue";

import ReportsMixin from "@/mixins/ReportsMixin";

export default {
  name: "DailySales",

  data() {
    return {
      dates: [],
      hideOrdersTable: true,
      hideReturnsTable: true,
      selectedDate: new Date(),
      selectedStore: null,
      orders: [],
      orderTotals: [],
      returns: [],
      returnTotals: [],
      totals: null,
      forStations: false
    };
  },

  mixins: [ReportsMixin, dataTableMixin],

  components: {
    DatePicker,
    MoneyTile,
    NumberTile
  },

  computed: {
    ...mapGetters(["storesVisible", "stationsVisible"]),
    canSortByStation() {
      return (
        this.stationsVisible.filter(s => s.store_id == this.selectedStore)
          .length > 0
      );
    }
  },

  watch: {
    selectedDate() {
      if (this.selectedStore) {
        this.getDailySalesData();
      }
    },
    selectedStore() {
      if (this.selectedDate) {
        this.getDailySalesData();
      }
    }
  },

  methods: {
    downloadReport() {
      const data = {
        date: moment(this.selectedDate)
          .startOf("day")
          .format(),
        store_id: this.selectedStore,
        for_stations: this.forStations
      };

      this.$store.dispatch("getDailySalesReport", data);
    },

    getDailySalesData() {
      this.$toasted.show("Loading report...", { type: "success" });

      this.$store
        .dispatch("getDailySalesReportData", {
          storeId: this.selectedStore,
          date: this.selectedDate
        })
        .then(salesData => {
          this.$toasted.clear();
          this.$toasted.show("Loaded", { type: "success" });

          this.orders = salesData.orders;
          this.orderTotals = salesData.order_totals;
          this.returns = salesData.returns;
          this.returnTotals = salesData.return_totals;
          this.totals = salesData.totals;
        });
    },

    takeToOrderDetails(orderId) {
      this.$router.push({
        name: "pos.orders.details",
        params: {
          id: orderId
        }
      });
    },

    formatCurrency
  },

  validations() {
    return {
      selectedDate: {
        required
      },
      selectedStore: {
        required
      }
    };
  }
};
</script>
