<template>
  <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
    <ul
      class="flex flex-wrap -mb-px text-sm font-medium text-center"
      role="tablist"
    >
      <li class="mr-2" role="presentation">
        <button
          class="inline-block p-4 border-b-2 rounded-t-lg"
          type="button"
          role="tab"
          v-on:click="toggleTabs(1)"
          v-bind:class="{
            'text-black bg-white': openTab !== 1,
            'text-white rr-button--primary-solid': openTab === 1,
          }"
        >
          Inventory Logs
        </button>
      </li>
      <li class="mr-2" role="presentation">
        <button
          class="inline-block p-4 border-b-2 rounded-t-lg"
          type="button"
          role="tab"
          v-on:click="toggleTabs(2)"
          v-bind:class="{
            'text-black bg-white': openTab !== 2,
            'text-white rr-button--primary-solid': openTab === 2,
          }"
        >
          Promotional Logs
        </button>
      </li>
      <li class="mr-2" role="presentation">
        <button
          class="inline-block p-4 border-b-2 rounded-t-lg"
          type="button"
          role="tab"
          v-on:click="toggleTabs(3)"
          v-bind:class="{
            'text-black bg-white': openTab !== 3,
            'text-white rr-button--primary-solid': openTab === 3,
          }"
        >
          Price Change Logs
        </button>
      </li>
    </ul>

    <div
      class="relative flex flex-col min-w-0 break-words bg-white w-full mb-6 shadow-lg rounded"
    >
      <div class="px-4 py-5 flex-auto">
        <div class="tab-content tab-space">
          <div v-bind:class="{ hidden: openTab !== 1, block: openTab === 1 }">
            <InventoryLogs
              :storeQuantities="storeQuantities"
              :itemId="itemId"
            />
          </div>
          <div v-bind:class="{ hidden: openTab !== 2, block: openTab === 2 }">
            <PromotionalLogs :itemId="itemId" />
          </div>
          <div v-bind:class="{ hidden: openTab !== 3, block: openTab === 3 }">
            <PriceChangeLogs :itemId="itemId" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import InventoryLogs from "@/components/Logs/InventoryLogs";
import PromotionalLogs from "@/components/Logs/PromotionalLogs";
import PriceChangeLogs from "@/components/Logs/PriceChangeLogs";

export default {
  components: {
    InventoryLogs,
    PromotionalLogs,
    PriceChangeLogs,
  },

  data() {
    return {
      openTab: 1,
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

  methods: {
    toggleTabs: function (tabNumber) {
      this.openTab = tabNumber;
    },
  },
};
</script>
