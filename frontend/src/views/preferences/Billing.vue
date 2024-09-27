<template>
  <div>
    <h1 class="h1">Billing</h1>
    <div class="mt-6">
      <Plan />
    </div>
    <div class="mt-12">
      <Payment />
    </div>
    <div class="mt-12">
      <Cancel />
    </div>
  </div>
</template>

<script>
import Plan from "@/views/billing/Plan";
import Payment from "@/views/billing/Payment";
import Cancel from "@/views/billing/Cancel";

export default {
  name: "Billing",

  components: {
    Plan,
    Payment,
    Cancel
  },

  data() {
    return {};
  }
};
</script>
