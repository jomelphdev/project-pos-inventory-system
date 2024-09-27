<template>
  <div>
    <transition name="fade-in" mode="out-in" appear>
      <template>
        <div
          class="p-8 flex justify-center items-center"
          style="height: 65vh"
          v-if="items.length < 1"
          data-test="pos-item-table-loading"
        >
          <span class="text-sm font-medium text-gray-900">
            Loading Items...
          </span>
        </div>
        <table
          class="rr-table min-w-full table-auto shadow-lg rounded-md overflow-hidden"
          data-test="pos-item-table"
          v-else
        >
          <thead>
            <tr>
              <th class="rr-table__th"></th>
              <th class="rr-table__th">Item</th>
              <th class="rr-table__th text-right">QTY</th>
              <th class="rr-table__th text-right">Price</th>
              <th class="rr-table__th text-right">Total</th>
            </tr>
          </thead>
          <tbody class="bg-white" data-test="pos-item-table-items">
            <tr
              is="pos-item-row"
              :data-test="`pos-item-table-item-${index}`"
              :key="`item_${item.id}`"
              :item="item"
              :selectedId="selectedId"
              :index="index"
              :showRemoveOption="showRemoveOption"
              v-for="(item, index) in items"
              @click.native="selectItem(item)"
            ></tr>
          </tbody>
        </table>
      </template>
    </transition>
  </div>
</template>

<script>
import PosItemRow from "@/components/PosItemRow";

export default {
  components: {
    PosItemRow
  },

  props: {
    items: {
      type: Array,
      default: () => []
    },
    selectedItem: Object,
    showRemoveOption: {
      type: Boolean,
      default: true
    }
  },

  computed: {
    selectedId() {
      return this.selectedItem ? this.selectedItem.id : null;
    }
  },

  methods: {
    selectItem(item) {
      if (
        ("quantity_left_to_return" in item &&
          item.quantity_left_to_return > 0) ||
        !("quantity_left_to_return" in item)
      ) {
        return this.$emit("update:selectedItem", item);
      }

      this.$toasted.show("Item has already been returned.", { type: "info" });
    }
  }
};
</script>
