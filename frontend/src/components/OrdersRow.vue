<template>
  <tr class="rr-table__tr--hover" @click="editOrder(order)">
    <td class="rr-table__td">
      <span class="rr-pill rr-pill--default">
        {{ order.id }}
      </span>
    </td>
    <td class="rr-table__td  text-sm leading-5 font-medium text-gray-900">
      {{ getStoreName(order.store_id) }}
    </td>
    <td
      class="rr-table__td text-sm leading-5 font-medium text-gray-900 text-right"
    >
      {{ order.quantity_ordered }}
    </td>
    <td
      class="rr-table__td text-sm leading-5 font-medium text-gray-900 text-right"
    >
      {{ formatCurrency(order.total) }}
    </td>
    <td class="rr-table__td rr-table__td--added">
      <div class="text-sm leading-5 text-gray-700 text-right">
        {{ order.created_at | moment }}
      </div>

      <div class="text-xs leading-5 text-gray-500 text-right">
        {{ order.created_at | calendar }}
      </div>
    </td>
  </tr>
</template>

<script>
import moment from "moment";
import { mapGetters } from "vuex";
import { formatCurrency } from "@/helpers";
export default {
  props: {
    order: {
      type: Object
    }
  },

  computed: {
    ...mapGetters(["stores"])
  },

  methods: {
    editOrder(order) {
      this.$router.push({
        name: "pos.orders.details",
        params: {
          id: order.id,
          order: order
        }
      });
    },

    getStoreName(storeId) {
      return this.stores.find(s => s.id == storeId).name;
    },
    formatCurrency
  },

  filters: {
    moment(date) {
      // return moment(date).format("MMMM Do, YYYY");
      return moment(date).fromNow();
    },
    calendar(date) {
      return moment(date).format("MMM Do [at] h:mma");
    }
  }
};
</script>
