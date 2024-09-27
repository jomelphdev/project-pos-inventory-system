<template>
  <div class="space-y-8" data-test="quantity-log-tables">
    <div
      class="p-8 flex justify-center items-center"
      style="height: 65vh"
      v-if="itemHistory.length == 0"
    >
      <span class="text-sm font-medium text-gray-900"> No records found. </span>
    </div>

    <div v-for="(ih, index) in itemHistory" :key="ih.store_id">
      <div
        class="text-2xl font-bold mb-4"
        style="cursor: pointer"
        @click="hideTable(ih.store_id)"
      >
        {{ getStoreName(ih.store_id) }}
      </div>

      <DataTable
        v-if="!hiddenTables.includes(ih.store_id)"
        :data-test="`quantity-log-table-${index}`"
      >
        <DataTableHeader>
          <DataTableHeaderCell> User </DataTableHeaderCell>
          <DataTableHeaderCell> Action </DataTableHeaderCell>
          <DataTableHeaderCell :right="true"> Amount </DataTableHeaderCell>
          <DataTableHeaderCell> Message </DataTableHeaderCell>
          <DataTableHeaderCell :right="true"> Date </DataTableHeaderCell>
        </DataTableHeader>

        <DataTableBody v-if="loading">
          <InventoryLogSkeleton />
          <InventoryLogSkeleton />
          <InventoryLogSkeleton />
        </DataTableBody>

        <DataTableBody v-else :data-test="`quantity-log-table-${index}-body`">
          <DataTableRow
            v-for="(q, rowIndex) in ih.log"
            :key="rowIndex"
            :data-test="`quantity-log-row-${index}-${rowIndex}`"
          >
            <DataTableCell>
              {{ getUserName(q.created_by) }}
            </DataTableCell>
            <DataTableCell>
              <div>
                <span
                  class="rr-pill"
                  :class="{
                    'rr-pill--green': q.type == 'sale',
                    'rr-pill--blue': q.type == 'add',
                    'rr-pill--dark-blue': q.type == 'transfer',
                    'rr-pill--orange': q.type == 'remove',
                    'rr-pill--yellow': q.type == 'discard',
                    'rr-pill--default': q.type == 'return',
                  }"
                >
                  {{ q.type | capitalize }}
                </span>
              </div>
            </DataTableCell>
            <DataTableCell :right="true">
              {{ q.amount }}
            </DataTableCell>
            <DataTableCell>
              {{ q.message }}
            </DataTableCell>
            <td class="rr-table__td">
              <div class="text-sm leading-5 text-gray-900 text-right">
                {{ q.created_at | moment }}
              </div>

              <div class="text-xs leading-5 text-gray-500 text-right">
                {{ q.created_at | calendar }}
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
import { getStoreName } from "@/helpers";

import InventoryLogSkeleton from "@/components/InventoryLogSkeleton";

export default {
  mixins: [dataTableMixin],
  name: "ItemHistory",

  components: {
    InventoryLogSkeleton,
  },

  data() {
    return {
      itemHistory: [],
      hiddenTables: [],
      loading: true,
    };
  },

  props: {
    storeQuantities: {
      type: Array,
    },
    itemId: {
      type: Number,
    },
  },

  computed: {
    ...mapGetters(["stores", "employees", "currentUser"]),
  },

  methods: {
    async generateItemHistory() {
      let history = [];

      // Create an array of promises for each storeQuantity
      const promises = this.storeQuantities.map(async (sq) => {
        const storeHistory = {
          store_id: sq.store_id,
          log: [],
        };

        // Orders
        sq.orders.forEach((o) => {
          storeHistory.log.push({
            created_at: o.created_at,
            created_by: o.pos_order.created_by,
            message: "Item sold.",
            amount: -o.quantity_ordered,
            type: "sale",
          });
        });

        // Returns
        sq.returns.forEach((r) => {
          storeHistory.log.push({
            created_at: r.created_at,
            created_by: r.pos_return.created_by,
            message:
              r.action === 1 ? "Item returned." : "Item returned & discarded.",
            amount: r.quantity_returned,
            type: r.action === 1 ? "return" : "discard",
          });
        });

        // Quantities
        sq.quantities.forEach((q) => {
          const type = q.is_transfer
            ? "transfer"
            : q.quantity_received > 0
            ? "add"
            : "remove";

          storeHistory.log.push({
            created_at: q.created_at,
            created_by: q.created_by,
            message: q.message,
            amount: q.quantity_received,
            type: type,
          });
        });

        // Sort
        storeHistory.log.sort((a, b) => {
          return a.created_at < b.created_at ? 1 : -1;
        });

        history.push(storeHistory);
      });

      // Wait for all promises to resolve
      await Promise.all(promises);

      this.loading = false;

      return history;
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
    getStoreName,
  },

  mounted() {
    this.generateItemHistory().then((history) => {
      this.itemHistory = history;
    });
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
