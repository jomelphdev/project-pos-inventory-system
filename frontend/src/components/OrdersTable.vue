<template>
  <div data-test="orders-table">
    <transition name="fade-in" mode="out-in" appear>
      <template>
        <div
          class="p-8 flex justify-center items-center"
          style="height: 65vh"
          v-if="orders.length < 1"
        >
          <span
            class="text-sm font-medium text-gray-900"
            data-test="ordersLoading-indicator"
          >
            Loading Orders...
          </span>
        </div>
        <table class="rr-table min-w-full table-auto shadow-lg" v-else>
          <thead>
            <tr>
              <th class="rr-table__th">Order #</th>
              <th class="rr-table__th">Store</th>
              <th class="rr-table__th text-right">Items</th>
              <th class="rr-table__th text-right">Total</th>
              <th class="rr-table__th text-right">Date</th>
            </tr>
          </thead>
          <tbody class="bg-white" data-test="orders-table-body">
            <tr
              is="orders-row"
              :key="order._id"
              :order="order"
              v-for="order of orders"
            ></tr>
          </tbody>
        </table>
      </template>
    </transition>
  </div>
</template>

<script>
import OrdersRow from "@/components/OrdersRow";
export default {
  props: {
    orders: {
      type: Array,
      default: () => []
    }
  },

  components: {
    OrdersRow
  }
};
</script>
