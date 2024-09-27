<template>
  <div>
    <div class="flex flex-row justify-between">
      <h1 class="h1" v-text="`Sales Report`" />
    </div>

    <template>
      <!-- TODO: Future Layout – Remove v-if="false" to enable -->
      <table
        class="rr-table min-w-full table-auto shadow-lg rounded-md overflow-hidden mb-4"
        v-if="false"
      >
        <thead>
          <tr>
            <th class="rr-table__th">Name</th>
            <th class="rr-table__th text-right" style="min-width: 240px">
              Export
            </th>
          </tr>
        </thead>
        <tbody class="bg-white">
          <tr
            class="rr-table__tr--hover"
            v-for="store in storesVisible"
            :key="store.id"
          >
            <td class="rr-table__td w-1/2">
              <div class="flex flex-col">
                <div
                  class="text-sm leading-5 font-medium text-gray-900"
                  v-text="store.name"
                />
              </div>
            </td>
            <td class="rr-table__td">
              <div class="flex flex-row justify-end">
                <button
                  class="rr-button font-medium"
                  @click="downloadReport(store.id)"
                >
                  Excel
                </button>
                <!-- <button
                class="rr-button rr-button--right font-medium"
                @click="downloadReport"
              >
                PDF
              </button> -->
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="bg-white shadow-lg rounded-md p-12">
        <DateRange v-model="selectedDate" />

        <div>
          <!-- Stores -->
          <div>
            <label class="rr-field__label">
              Stores

              <span
                class="rr-field__label-required"
                v-if="!$v.selectedStores.required"
              >
                Required
              </span>
            </label>
          </div>
          <div class="">
            <div
              class="rr-field__radio mr-4 mb-4 inline-block"
              v-for="store in storesVisible"
              :key="store.id"
            >
              <input
                type="checkbox"
                v-model="selectedStores"
                :value="store.id"
                :id="`input${store.id}`"
                class="rr-field__radio-input"
              />
              <label
                :for="`input${store.id}`"
                class="rr-field__radio-label items-baseline"
              >
                {{ store.name }}
              </label>
            </div>
            <div class="rr-field__radio mr-4 mb-4 inline-block" key="input_all">
              <input
                type="checkbox"
                :id="`input_all`"
                class="rr-field__radio-input"
                :checked="allStoresSelected"
                @click="allStores"
              />
              <label
                :for="`input_all`"
                class="rr-field__radio-label items-baseline"
              >
                All
              </label>
            </div>
          </div>
        </div>
        <div class="flex flex-row mt-8">
          <button
            class="rr-button rr-button--lg rr-button--primary"
            :disabled="$v.$invalid"
            v-text="`Download Excel Report`"
            @click="downloadReport()"
          />
        </div>
      </div>

      <!-- Orders Table -->
      <div v-if="sales" class="my-8">
        <div
          class="font-bold mb-4"
          style="cursor: pointer;"
          @click="hideSalesTable = !hideSalesTable"
        >
          Sales
          <span class="rr-pill rr-pill--default">
            {{ Object.keys(sales).length }}
          </span>
        </div>

        <DataTable>
          <DataTableHeader @click.native="hideSalesTable = !hideSalesTable">
            <DataTableHeaderCell>Date</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Cash</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Charge</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">EBT</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Sub-Total</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Tax</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Total</DataTableHeaderCell>
            <DataTableHeaderCell :right="true"
              >Non-Taxed Total</DataTableHeaderCell
            >
            <DataTableHeaderCell :right="true">Sales</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Returns</DataTableHeaderCell>
          </DataTableHeader>

          <DataTableBody>
            <DataTableRow
              v-for="(salesData, date) in sales"
              :key="date"
              :style="{ display: hideSalesTable ? 'none' : '' }"
            >
              <DataTableCell>{{ date }}</DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(salesData.cash) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(salesData.card) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(salesData.ebt) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(salesData.sub_total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(salesData.tax) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(salesData.total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ formatCurrency(salesData.non_taxed_sub_total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ salesData.sales || 0 }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ salesData.returns || 0 }}
              </DataTableCell>
            </DataTableRow>
          </DataTableBody>
        </DataTable>
      </div>

      <!-- Totals Table -->
      <div v-if="totals" class="my-8">
        <div class="font-bold mb-4">
          Totals
        </div>

        <DataTable>
          <DataTableHeader>
            <DataTableHeaderCell>Date</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Cash</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Charge</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">EBT</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Sub-Total</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Tax</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Total</DataTableHeaderCell>
            <DataTableHeaderCell :right="true"
              >Non-Taxed Total</DataTableHeaderCell
            >
            <DataTableHeaderCell :right="true">Sales</DataTableHeaderCell>
            <DataTableHeaderCell :right="true">Returns</DataTableHeaderCell>
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
                {{ formatCurrency(totals.non_taxed_sub_total) }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ totals.sales || 0 }}
              </DataTableCell>
              <DataTableCell :right="true">
                {{ totals.returns || 0 }}
              </DataTableCell>
            </DataTableRow>
          </DataTableBody>
        </DataTable>
      </div>
    </template>

    <!-- <template v-else>
      <loading-panel :timer="true">
        <template v-slot:title> Generating → {{ fileName }}.xlsx </template>
        <template v-slot:text>
          Note: Reports can take several minutes to generate.</template
        >
      </loading-panel>
    </template> -->
    <ReportDirectory :report_type="'sales'" />
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import { required } from "vuelidate/lib/validators";

import DateRange from "@/components/DateRange.vue";
import { formatCurrency } from "@/helpers";
import dataTableMixin from "@/components/table/dataTableMixin";

import ReportsMixin from "@/mixins/ReportsMixin";

export default {
  name: "SalesReport",

  mixins: [ReportsMixin, dataTableMixin],

  components: {
    DateRange
  },

  data() {
    return {
      dates: [],
      selectedDate: null,
      selectedStores: [],
      sales: null,
      totals: null,
      hideSalesTable: false
    };
  },

  computed: {
    ...mapGetters(["storesVisible"]),

    allStoresSelected() {
      return this.selectedStores.length == this.storesVisible.length;
    },

    readyForQuery() {
      return (
        this.selectedStores.length > 0 &&
        this.selectedDate.start &&
        this.selectedDate.end
      );
    }
  },

  watch: {
    selectedStores(storesVisible) {
      if (this.readyForQuery) {
        this.getSalesReportData();
      } else if (storesVisible.length == 0) {
        this.totals = null;
        this.sales = null;
      }
    },
    selectedDate: {
      handler() {
        if (this.readyForQuery) {
          this.getSalesReportData();
        }
      },
      deep: true
    }
  },

  methods: {
    downloadReport() {
      this.$store.dispatch("getSalesReport", {
        storeIds: this.selectedStores,
        startDate: this.selectedDate.start,
        endDate: this.selectedDate.end
      });
    },

    getSalesReportData() {
      this.$toasted.show("Loading report...", { type: "success" });

      this.$store
        .dispatch("getSalesReportData", {
          storeIds: this.selectedStores,
          startDate: this.selectedDate.start,
          endDate: this.selectedDate.end
        })
        .then(salesData => {
          this.$toasted.clear();
          this.$toasted.show("Loaded", { type: "success" });
          this.totals = salesData.totals;
          this.sales = salesData.sales;
        });
    },

    allStores() {
      if (this.allStoresSelected) return (this.selectedStores = []);
      this.selectedStores = this.storesVisible.map(s => s.id);
    },

    formatCurrency
  },

  validations() {
    return {
      selectedDate: {
        required
      },
      selectedStores: {
        required
      }
    };
  }
};
</script>
