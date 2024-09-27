<template>
  <div class="container m-auto text-center">
    <h1 class="h1">Authorizing QuickBooks</h1>
    <div style="text-align: -webkit-center">
      <LoadingSpinner size="xl" />
    </div>
  </div>
</template>

<script>
import LoadingSpinner from "@/components/LoadingSpinner.vue";

export default {
  components: { LoadingSpinner },

  mounted() {
    const queryParams = new URLSearchParams(window.location.search);
    if (queryParams.has("code") && queryParams.has("realmId")) {
      this.$store
        .dispatch("getQuickBooksAccessTokens", {
          auth: queryParams.get("code"),
          realm_id: queryParams.get("realmId"),
        })
        .then(() => {
          this.$router.push({ name: "scan" });
          this.$store.dispatch("pushNotifications", {
            text: "Successfully linked QuickBooks!",
            type: "success",
          });
        });
    } else {
      this.$router.push({ name: "scan" });
    }
  },
};
</script>
