<template>
  <div class="flex flex-row space-x-8 mb-4 border-b border-blue-600">
    <button
      :class="{ 'font-semibold border-b-4': itemTab == 'details' }"
      class="flex text-xl border-blue-600 focus:outline-none hover:font-semibold"
      data-test="details-tab"
      @click="itemTab = 'details'"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
        class="w-5 h-5 self-center mr-2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
        />
      </svg>
      Item Details
    </button>
    <button
      :class="{ 'font-semibold border-b-4': itemTab == 'shipping' }"
      class="flex text-xl border-blue-600 focus:outline-none hover:font-semibold"
      data-test="shipping-tab"
      @click="itemTab = 'shipping'"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
        class="w-5 h-5 self-center mr-2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
        />
      </svg>
      Shipping
    </button>
  </div>
</template>

<script>
export default {
  name: "ItemNavigation",

  emits: ["change"],

  data() {
    return {
      itemTab: "details",
    };
  },

  watch: {
    itemTab(tab) {
      this.$emit("change", tab);
    },
  },
};
</script>

<style></style>
