<template>
  <div class="space-y-8" data-test="quantity-log-tables">
    <div
      class="p-8 flex justify-center items-center"
      style="height: 65vh"
      v-if="promotionalLogs.length == 0"
    >
      <span class="text-sm font-medium text-gray-900"> No records found. </span>
    </div>

    <div
      v-for="(historyLogs, storeId) in groupedPromotionalLogs"
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
          <DataTableHeaderCell :right="true">Price</DataTableHeaderCell>
          <DataTableHeaderCell :right="true"
            >Quantity Ordered</DataTableHeaderCell
          >
          <DataTableHeaderCell :right="true">Active at</DataTableHeaderCell>
          <DataTableHeaderCell :right="true">Expires at</DataTableHeaderCell>
        </DataTableHeader>

        <DataTableBody v-if="loading">
          <PromotionalLogSkeleton />
          <PromotionalLogSkeleton />
          <PromotionalLogSkeleton />
        </DataTableBody>

        <DataTableBody v-else :data-test="`quantity-log-table-${storeId}-body`">
          <DataTableRow v-for="item in historyLogs" :key="item.id">
            <DataTableCell>
              {{ getUserName(item.created_by) }}
            </DataTableCell>
            <DataTableCell :right="true">
              {{ formatCurrency(item.price) }}
            </DataTableCell>

            <DataTableCell :right="true">
              {{ item.quantity_ordered }}
            </DataTableCell>
            <td class="rr-table__td" v-if="item.active_at">
              <div class="text-sm leading-5 text-gray-900 text-right">
                {{ item.active_at | moment }}
              </div>

              <div class="text-xs leading-5 text-gray-500 text-right">
                {{ item.active_at | calendar }}
              </div>
            </td>
            <td class="rr-table__td" v-if="item.expires_at">
              <div class="text-sm leading-5 text-gray-900 text-right">
                {{ item.expires_at | moment }}
              </div>

              <div class="text-xs leading-5 text-gray-500 text-right">
                {{ item.expires_at | calendar }}
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
import PromotionalLogSkeleton from "@/components/PromotionalLogSkeleton";

export default {
  mixins: [dataTableMixin],
  name: "PromotionalLogs",

  data() {
    return {
      promotionalLogs: [],
      hiddenTables: [],
      loading: true,
    };
  },

  components: {
    PromotionalLogSkeleton,
  },

  props: {
    itemId: {
      type: Number,
    },
  },

  computed: {
    ...mapGetters(["stores", "employees", "currentUser"]),

    groupedPromotionalLogs() {
      const groupedData = {};

      this.promotionalLogs.forEach((item) => {
        const storeId = item.store_id;

        if (!groupedData[storeId]) {
          groupedData[storeId] = [];
        }

        groupedData[storeId].push({
          price: item.pos_order_items[0].price,
          quantity_ordered: item.pos_order_items[0].quantity_ordered,
          active_at: item.pos_order_items[0].item_specific_discount_active_at,
          expires_at: item.pos_order_items[0].item_specific_discount_expires_at,
          created_by: item.created_by,
        });
      });

      for (const storeId in groupedData) {
        groupedData[storeId].sort((a, b) => {
          return a.active_at < b.active_at ? 1 : -1;
        });
      }

      return groupedData;
    },
  },

  methods: {
    async getPromotionalLogs(itemId) {
      try {
        const data = await this.$store.dispatch("getPromotionalLogs", itemId);
        this.promotionalLogs = data;
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
    this.getPromotionalLogs(this.itemId);
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
