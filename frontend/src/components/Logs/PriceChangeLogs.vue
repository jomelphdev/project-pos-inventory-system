<template>
  <div class="space-y-8" data-test="quantity-log-tables">
    <div
      class="p-8 flex justify-center items-center"
      style="height: 65vh"
      v-if="priceChangeLogs.length == 0"
    >
      <span class="text-sm font-medium text-gray-900"> No records found. </span>
    </div>

    <div
      v-for="(historyLogs, storeId) in groupedPriceChangeLogs"
      :key="storeId"
    >
      <div
        class="text-2xl font-bold mb-4"
        style="cursor: pointer"
        @click="hideTable(storeId)"
      >
        {{ getStoreName(storeId) }}
      </div>
      <DataTable
        v-if="!hiddenTables.includes(storeId)"
        :data-test="`quantity-log-table-${storeId}`"
      >
        <DataTableHeader>
          <DataTableHeaderCell> User </DataTableHeaderCell>
          <DataTableHeaderCell> Prices </DataTableHeaderCell>
          <DataTableHeaderCell> Reason </DataTableHeaderCell>
          <DataTableHeaderCell :right="true"> Date Change </DataTableHeaderCell>
        </DataTableHeader>

        <DataTableBody v-if="loading">
          <PriceChangeLogSkeleton />
          <PriceChangeLogSkeleton />
          <PriceChangeLogSkeleton />
        </DataTableBody>

        <DataTableBody v-else :data-test="`quantity-log-table-${storeId}-body`">
          <DataTableRow v-for="item in historyLogs" :key="item.id">
            <DataTableCell>
              {{ getUserName(item.created_by) }}
            </DataTableCell>
            <DataTableCell>
              <DataTableHeader>
                <DataTableHeaderCell> Property </DataTableHeaderCell>
                <DataTableHeaderCell> Old </DataTableHeaderCell>
                <DataTableHeaderCell> New </DataTableHeaderCell>
              </DataTableHeader>
              <DataTableBody>
                <DataTableRow>
                  <DataTableCell class="uppercase text-xs font-medium">
                    Price
                  </DataTableCell>
                  <DataTableCell class="text-xs font-medium">
                    {{ formatCurrency(item.old_price) }}
                  </DataTableCell>
                  <DataTableCell class="text-xs font-medium">
                    {{ formatCurrency(item.new_price) }}
                  </DataTableCell>
                </DataTableRow>
                <DataTableRow>
                  <DataTableCell class="uppercase text-xs font-medium">
                    Original Price
                  </DataTableCell>
                  <DataTableCell class="text-xs font-medium">
                    {{ formatCurrency(item.old_original_price) }}
                  </DataTableCell>
                  <DataTableCell class="text-xs font-medium">
                    {{ formatCurrency(item.new_original_price) }}
                  </DataTableCell>
                </DataTableRow>
                <DataTableRow>
                  <DataTableCell class="uppercase text-xs font-medium">
                    Cost
                  </DataTableCell>
                  <DataTableCell class="text-xs font-medium">
                    {{ formatCurrency(item.old_cost) }}
                  </DataTableCell>
                  <DataTableCell class="text-xs font-medium">
                    {{ formatCurrency(item.new_cost) }}
                  </DataTableCell>
                </DataTableRow>
              </DataTableBody>
            </DataTableCell>
            <DataTableCell>
              {{ item.reason }}
            </DataTableCell>
            <td class="rr-table__td">
              <div class="text-sm leading-5 text-gray-900 text-right">
                {{ item.updated_at | moment }}
              </div>

              <div class="text-xs leading-5 text-gray-500 text-right">
                {{ item.updated_at | calendar }}
              </div>
            </td>
          </DataTableRow>
        </DataTableBody>
      </DataTable>
    </div>
  </div>
</template>

<script>
import dataTableMixin from "@/components/table/dataTableMixin";
import moment from "moment";
import { mapGetters } from "vuex";
import { formatCurrency, getStoreName } from "@/helpers";
import PriceChangeLogSkeleton from "@/components/PriceChangeLogSkeleton";

export default {
  mixins: [dataTableMixin],
  name: "PriceChangeLogs",

  data() {
    return {
      priceChangeLogs: [],
      hiddenTables: [],
      loading: true,
    };
  },

  components: {
    PriceChangeLogSkeleton,
  },

  props: {
    itemId: {
      type: Number,
    },
  },

  computed: {
    ...mapGetters(["stores", "employees", "currentUser"]),

    groupedPriceChangeLogs() {
      const groupedData = {};

      this.priceChangeLogs.forEach((item) => {
        const storeId = item.store_id;

        if (!groupedData[storeId]) {
          groupedData[storeId] = [];
        }

        groupedData[storeId].push({
          old_price: item.old_price,
          new_price: item.new_price,
          old_original_price: item.old_original_price,
          new_original_price: item.new_original_price,
          old_cost: item.old_cost,
          new_cost: item.new_cost,
          reason: item.reason_for_change,
          updated_at: item.updated_at,
          created_by: item.created_by,
        });
      });

      for (const storeId in groupedData) {
        groupedData[storeId].sort((a, b) => {
          return a.updated_at < b.updated_at ? 1 : -1;
        });
      }

      return groupedData;
    },
  },

  methods: {
    async getItemHistory(itemId) {
      try {
        const data = await this.$store.dispatch("getItemHistory", itemId);
        this.priceChangeLogs = data.filter((item) => item.action === "update");
      } catch (error) {
        console.error("Error fetching data:", error);
      } finally {
        this.loading = false;
      }
    },

    getUserName(id) {
      let name = id;
      const isCurrentUser = this.currentUser.id === id;
      const isEmployee = this.employees.find((e) => {
        return e.id === id;
      });
      if (isCurrentUser) {
        name = this.currentUser.full_name;
      } else if (isEmployee) {
        name = isEmployee.full_name;
      }
      return name;
    },

    hideTable(storeId) {
      const index = this.hiddenTables.indexOf(storeId);

      if (index > -1) {
        return this.hiddenTables.splice(index, 1);
      }

      this.hiddenTables.push(storeId);
    },

    formatCurrency,
    getStoreName,
  },
  mounted() {
    this.getItemHistory(this.itemId);
  },
  // TODO: Refactor into helpers, mixin or make global.
  filters: {
    moment(date) {
      return moment(date).fromNow();
    },
    calendar(date) {
      return moment(date).format("MMM Do, YYYY [at] h:mma");
    },
  },
};
</script>
