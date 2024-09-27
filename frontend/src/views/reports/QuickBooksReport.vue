<template>
  <div>
    <h1 class="h1" v-text="`QuickBooks`" />

    <div class="bg-white shadow-lg rounded-md px-12 pt-8 pb-6 mb-8">
      <div v-if="isQuickBooksAuthenticated">
        <div class="mb-4 text-sm">
          <strong>Note:</strong> We automatically generate a daily journal for
          you at 12:00AM PST, but if you require it sooner you can manually
          generate the journal here.
        </div>

        <button
          class="rr-button rr-button--primary"
          @click="generateJournalEntry()"
        >
          Generate Journal
        </button>
      </div>

      <div v-else>
        <div class="mb-4 text-sm text-red-700">
          We no longer have valid credientials for your QuickBooks account
          please re-authenticate to continue usage.
        </div>

        <button
          class="rr-button rr-button--primary"
          @click="$router.push({ name: 'preferences.quickbooks' })"
        >
          Re-Authenticate
        </button>
      </div>
    </div>

    <DataTable v-if="existingJournals">
      <DataTableHeader>
        <DataTableHeaderCell>Date</DataTableHeaderCell>
        <DataTableHeaderCell></DataTableHeaderCell>
      </DataTableHeader>

      <DataTableBody>
        <DataTableRow v-for="journal in existingJournals" :key="journal.id">
          <DataTableCell>
            {{ journal.for_date }}
          </DataTableCell>
          <DataTableCell>
            <a
              :href="journal.quickbooks_journal_url"
              target="_blank"
              class="flex"
            >
              View Journal
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 ml-1"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"
                />
                <path
                  d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"
                />
              </svg>
            </a>
          </DataTableCell>
        </DataTableRow>
      </DataTableBody>
    </DataTable>
  </div>
</template>

<script>
import dataTableMixin from "@/components/table/dataTableMixin";
import { mapGetters } from "vuex";

export default {
  mixins: [dataTableMixin],

  data() {
    return {
      existingJournals: null,
      page: 1
    };
  },

  computed: {
    ...mapGetters(["isQuickBooksAuthenticated"])
  },

  mounted() {
    this.getExistingJournals();
  },

  methods: {
    generateJournalEntry() {
      this.$store.dispatch("generateJournalEntry");
    },

    getExistingJournals() {
      this.$store.dispatch("getExistingJournals", this.page).then(journals => {
        this.existingJournals = journals;
      });
    }
  }
};
</script>
