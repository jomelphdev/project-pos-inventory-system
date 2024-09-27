<template>
  <div>
    <div class="flex flex-row justify-between">
      <h1 class="h1">QuickBooks</h1>
    </div>

    <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8 mt-4">
      <div>
        <div class="mb-4">
          When connecting your QuickBooks with RetailRight it allows us to
          generate journal entries for you automatically on a daily basis,
          making your accounting much easier and less error prone.
        </div>
        <div class="flex mb-2">
          <strong>Authentication Status:</strong>
          <div class="font-medium ml-1">
            <span class="text-red-700" v-if="!isQuickBooksAuthenticated"
              >Not Authenticated</span
            >
            <span
              class="text-orange-700"
              v-if="!isQuickBooksAuthenticated && isUsingQuickBooks"
              >Expired Re-Authenticate</span
            >
            <span class="text-green-700" v-if="isQuickBooksAuthenticated"
              >Authenticated</span
            >
          </div>
        </div>

        <button
          class="rr-button rr-button--md rr-button--danger"
          data-test="revokeAccess-button"
          @click.stop="revokeQuickBooksAccess()"
          v-text="'Un-link QuickBooks'"
          v-if="isQuickBooksAuthenticated"
        />
        <button
          class="rr-button rr-button--md rr-button--primary-solid"
          data-test="authenticate-button"
          @click.stop="connectQuickBooks()"
          v-text="'Connect Your QuickBooks'"
          v-else
        />
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
export default {
  computed: {
    ...mapGetters(["isQuickBooksAuthenticated", "isUsingQuickBooks"])
  },

  methods: {
    connectQuickBooks() {
      this.$store.dispatch("authorizeQuickBooks").then(authUrl => {
        window.location.replace(authUrl);
      });
    },

    revokeQuickBooksAccess() {
      this.$store.dispatch("revokeQuickBooksAccess");
    }
  }
};
</script>
