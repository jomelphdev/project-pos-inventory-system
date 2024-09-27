<template>
  <div>
    <h1 class="h1" v-text="`Consignment`" />

    <data-table>
      <data-table-header>
        <data-table-header-cell>Consignor Name</data-table-header-cell>
        <data-table-header-cell>Sales</data-table-header-cell>
        <data-table-header-cell>Fees Collected</data-table-header-cell>
        <data-table-header-cell colspan="2"
          >Amount Owed to Consignor</data-table-header-cell
        >
      </data-table-header>

      <data-table-body>
        <data-table-row
          class="rr-table__tr--hover relative"
          v-for="row in reportData"
          :key="row.consignor_id"
          @mouseover.native="hover = row.consignor_id"
          @mouseleave.native="hover = null"
        >
          <data-table-cell>{{ row.consignor_name }}</data-table-cell>
          <data-table-cell>{{ row.sales }}</data-table-cell>
          <data-table-cell>{{
            formatCurrency(row.consignment_sum)
          }}</data-table-cell>
          <data-table-cell>{{
            formatCurrency(row.amount_owed)
          }}</data-table-cell>
          <div
            class="absolute inset-0 bg-gray-100/75 flex justify-center items-center"
            v-show="hover == row.consignor_id"
          >
            <data-table-cell>
              <button
                class="rr-button font-semibold mr-2"
                data-test="payOut-button"
                @click="
                  $refs.payOutModal.openModal();
                  selectedConsignor = row.consignor_id;
                "
              >
                Pay Out
              </button>
            </data-table-cell>
          </div>
        </data-table-row>
        <data-table-row v-if="reportData.length == 0">
          <data-table-cell colspan="5" class="text-center"
            >Currently no outstanding consignment balances.</data-table-cell
          >
        </data-table-row>
      </data-table-body>
    </data-table>

    <modal-wall ref="payOutModal">
      <template v-slot:header>
        <a
          href="#"
          class="modal__close"
          @click.prevent.stop="$refs.payOutModal.closeModal()"
        >
          Close
        </a>

        <span class="text-center block"> Pay Consignor </span>
      </template>
      <template v-slot:body>
        <div class="max-w-lg text-lg text-center mb-8 mx-auto">
          Are you sure you are ready to pay consignor, all transaction tracking
          is reset on confirmation.
        </div>

        <div class="flex justify-center">
          <button
            class="rr-button rr-button--primary rr-button--lg mr-5"
            @click="createInvoice()"
          >
            Yes
          </button>
          <button
            class="rr-button rr-button--lg rr-button--danger"
            @click="$refs.payOutModal.closeModal()"
          >
            No
          </button>
        </div>
      </template>
    </modal-wall>
  </div>
</template>

<script>
import { formatCurrency } from "@/helpers";
import { mapGetters } from "vuex";

import dataTableMixin from "@/components/table/dataTableMixin";
import ModalWall from "../../components/ModalWall.vue";

export default {
  components: { ModalWall },

  mixins: [dataTableMixin],

  computed: {
    ...mapGetters(["consignors"]),
  },

  data() {
    return {
      reportData: null,
      selectedConsignor: null,
      hover: null,
    };
  },

  mounted() {
    this.getReportData();
  },

  methods: {
    getReportData() {
      this.$store.dispatch("getConsignmentReportData").then((data) => {
        this.reportData = data;
      });
    },

    createInvoice() {
      this.$store
        .dispatch("createConsignmentInvoice", this.selectedConsignor)
        .then(() => {
          this.$refs.payOutModal.closeModal();
          this.getReportData();
        });
    },

    formatCurrency,
  },
};
</script>
