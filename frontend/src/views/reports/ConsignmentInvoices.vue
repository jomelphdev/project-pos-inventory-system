<template>
  <div>
    <h1 class="h1" v-text="`Consignment Invoices`" />

    <data-table>
      <data-table-header>
        <data-table-header-cell>Consignor Name</data-table-header-cell>
        <data-table-header-cell>Fees Collected</data-table-header-cell>
        <data-table-header-cell
          >Amount Paid to Consignor</data-table-header-cell
        >
        <data-table-header-cell>Invoice Created On</data-table-header-cell>
      </data-table-header>

      <data-table-body>
        <data-table-row
          class="rr-table__tr--hover relative"
          v-for="invoice in invoices"
          :key="invoice.id"
        >
          <data-table-cell>{{ invoice.consignor_name }}</data-table-cell>
          <data-table-cell>{{
            formatCurrency(invoice.amount_collected)
          }}</data-table-cell>
          <data-table-cell>{{
            formatCurrency(invoice.amount_paid)
          }}</data-table-cell>
          <data-table-cell>{{ invoice.created_at | moment }}</data-table-cell>
        </data-table-row>
        <data-table-row v-if="invoices.length == 0">
          <data-table-cell colspan="5" class="text-center"
            >Currently no existing invoices.</data-table-cell
          >
        </data-table-row>
      </data-table-body>
    </data-table>

    <modal> </modal>
  </div>
</template>

<script>
import { formatCurrency } from "@/helpers";
import moment from "moment";

import dataTableMixin from "@/components/table/dataTableMixin";

import Modal from "@/components/Modal";

export default {
  components: { Modal },

  mixins: [dataTableMixin],

  data() {
    return {
      invoices: []
    };
  },

  mounted() {
    this.getInvoices();
  },

  methods: {
    getInvoices() {
      this.$store.dispatch("getConsignmentInvoices").then(({ invoices }) => {
        this.invoices = invoices;
      });
    },

    formatCurrency
  },

  filters: {
    moment(date) {
      return moment(date).format("MM/DD/YYYY LT");
    }
  }
};
</script>
