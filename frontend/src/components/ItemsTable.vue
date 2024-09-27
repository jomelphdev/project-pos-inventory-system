<template>
  <div>
    <transition name="fade-in" mode="out-in" appear>
      <template>
        <div
          class="p-8 flex justify-center items-center"
          style="height: 65vh"
          v-if="items.length == 0 && !loading"
        >
          <span class="text-sm font-medium text-gray-900">
            No Items Found.
          </span>
        </div>
        <table
          class="rr-table min-w-full table-auto shadow-lg"
          data-test="items-table"
          v-else
        >
          <thead>
            <tr>
              <th class="rr-table__th"></th>
              <th class="rr-table__th">Item</th>
              <th class="rr-table__th text-right">Price</th>
              <th class="rr-table__th">Condition</th>
              <th class="rr-table__th rr-table__th--added">Added</th>
            </tr>
          </thead>
          <tbody
            class="bg-white"
            data-test="items-table-items"
            v-if="items.length > 0"
          >
            <tr
              is="item-row"
              v-for="(item, index) in items"
              :data-test="`items-table-item-${index}`"
              :key="item.id"
              :item="item"
            ></tr>
          </tbody>
          <tbody
            class="bg-white"
            data-test="items-table-skeleton-items"
            v-else-if="loading"
          >
            <ItemRowSkeleton />
            <ItemRowSkeleton />
            <ItemRowSkeleton />
          </tbody>
        </table>
      </template>
    </transition>
  </div>
</template>

<script>
import ItemRow from "@/components/ItemRow";
import ItemRowSkeleton from "@/components/ItemRowSkeleton";

export default {
  components: {
    ItemRow,
    ItemRowSkeleton
  },

  props: {
    items: {
      type: Array
    },
    loading: {
      type: Boolean
    }
  }
};
</script>
