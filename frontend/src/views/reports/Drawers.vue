<template>
  <div>
    <div class="flex flex-row justify-between">
      <h1 class="h1" v-text="`Cash Drawers`" />
    </div>

    <div v-if="Object.keys(drawersData).length > 0">
      <div class="my-8" v-for="(data, id) of drawersData" :key="id">
        <div class="font-bold text-xl">
          {{ getStoreName(id) }}
        </div>

        <DataTable>
          <DataTableHeader>
            <DataTableHeaderCell>Station Name</DataTableHeaderCell>
            <DataTableHeaderCell>Starting Balance</DataTableHeaderCell>
            <DataTableHeaderCell>Current Balance</DataTableHeaderCell>
            <DataTableHeaderCell colspan="2">Difference</DataTableHeaderCell>
          </DataTableHeader>

          <DataTableBody>
            <DataTableRow
              class="relative"
              v-for="(station, index) in data"
              :key="index"
              @mouseover.native="hover = id + index"
              @mouseleave.native="hover = null"
            >
              <DataTableCell>{{ station.name }}</DataTableCell>
              <DataTableCell>{{
                formatCurrency(station.starting_balance)
              }}</DataTableCell>
              <DataTableCell>{{
                formatCurrency(station.current_balance)
              }}</DataTableCell>
              <DataTableCell>
                <span
                  :class="{
                    'text-green-500': station.difference > 0,
                    'text-red-500': station.difference < 0,
                  }"
                >
                  {{ formatCurrency(station.difference) }}
                </span>
              </DataTableCell>
              <DataTableCell
                v-if="hover == id + index"
                class="absolute inset-0 bg-gray-100/75 flex justify-center items-center"
                disableDefaultClass
              >
                <div class="flex">
                  <button
                    class="rr-button font-semibold mr-4"
                    @click="showEmployeeModal(id, station)"
                  >
                    Show Employees
                  </button>
                  <button
                    class="rr-button font-semibold"
                    @click="showBalanceModal(id, station)"
                  >
                    Set New Balance
                  </button>
                </div>
              </DataTableCell>
            </DataTableRow>
          </DataTableBody>
        </DataTable>
      </div>
    </div>

    <div v-else>Currently no new cash drawers data to display.</div>

    <Modal class="centered" ref="employeeModal">
      <template v-slot:header> Employee's That Worked On Station </template>
      <template v-slot:body>
        <div class="flex flex-col">
          <div class="text-left">
            <div>
              Store:
              <span class="font-bold">{{ getStoreName(selectedStore) }}</span>
            </div>
            <div>
              Station: <span class="font-bold">{{ selectedStation.name }}</span>
            </div>
          </div>

          <!-- EMPLOYEE TABLE -->
          <DataTable class="mt-8">
            <DataTableHeader>
              <DataTableHeaderCell>Name</DataTableHeaderCell>
              <DataTableHeaderCell>Cash Transacted</DataTableHeaderCell>
              <DataTableHeaderCell>Total Transacted</DataTableHeaderCell>
              <DataTableHeaderCell>Orders</DataTableHeaderCell>
              <DataTableHeaderCell>Returns</DataTableHeaderCell>
            </DataTableHeader>
            <DataTableBody>
              <DataTableRow
                v-for="employee in selectedStation.employee_data"
                :key="employee.user_id"
              >
                <DataTableCell>{{
                  getUsersName(employee.user_id)
                }}</DataTableCell>
                <DataTableCell>{{
                  formatCurrency(employee.cash_transacted)
                }}</DataTableCell>
                <DataTableCell>{{
                  formatCurrency(employee.total_transacted)
                }}</DataTableCell>
                <DataTableCell>{{ employee.orders }}</DataTableCell>
                <DataTableCell>{{ employee.returns || 0 }}</DataTableCell>
              </DataTableRow>
            </DataTableBody>
          </DataTable>
        </div>
      </template>
    </Modal>

    <Modal class="centered" ref="balanceModal" @closed="form.reset()">
      <template v-slot:header> Set New Starting Balance </template>
      <template v-slot:body>
        <div class="flex flex-col">
          <div class="text-left">
            <div>
              Store:
              <span class="font-bold">{{ getStoreName(selectedStore) }}</span>
            </div>
            <div>
              Station: <span class="font-bold">{{ selectedStation.name }}</span>
            </div>
            <div>
              Difference:
              <span class="font-bold">{{
                formatCurrency(selectedStation.difference)
              }}</span>
            </div>
            <div v-if="overShort">
              {{ overShortString }}:
              <span class="font-bold"
                >{{ formatCurrency(overShort) }} ({{
                  overShortPercentage | percent
                }})</span
              >
            </div>
          </div>

          <div class="mt-8">
            <div class="rr-field mb-4">
              <label class="rr-field__label text-left">Actual Difference</label>
              <currency-input
                class="rr-field__input"
                v-model="form.actual_difference"
              />
            </div>

            <div class="rr-field">
              <label class="rr-field__label text-left"
                >New Starting Balance</label
              >
              <currency-input
                class="rr-field__input"
                v-model="form.new_balance"
              />
            </div>
          </div>
        </div>
      </template>
      <template v-slot:footer>
        <button
          class="rr-button rr-button--lg rr-button--primary-solid"
          :disabled="!form.new_balance || form.actual_difference == null"
          @click="setNewDrawerBalance"
        >
          Save
        </button>
      </template>
    </Modal>
  </div>
</template>

<script>
import DataTable from "../../components/table/DataTable.vue";
import DataTableHeader from "../../components/table/DataTableHeader.vue";
import DataTableHeaderCell from "../../components/table/DataTableHeaderCell.vue";
import DataTableBody from "../../components/table/DataTableBody.vue";
import DataTableRow from "../../components/table/DataTableRow.vue";
import DataTableCell from "../../components/table/DataTableCell.vue";
import Modal from "@/components/Modal";

import _ from "lodash";
import Form from "@/classes/Form";
import { getStoreName, formatCurrency, getUsersName } from "@/helpers";

export default {
  components: {
    DataTable,
    DataTableHeader,
    DataTableHeaderCell,
    DataTableBody,
    DataTableRow,
    DataTableCell,
    Modal,
  },

  computed: {
    overShort() {
      if (!this.form.actual_difference) return;

      return this.form.actual_difference - this.selectedStation.difference;
    },

    overShortPercentage() {
      return this.overShort / this.selectedStation.difference;
    },

    overShortString() {
      if (this.overShort < 0) {
        return "Shortage";
      } else if (this.overShort > 0) {
        return "Overage";
      }

      return null;
    },
  },

  data() {
    return {
      drawersData: null,
      selectedStore: null,
      selectedStation: null,
      form: new Form({
        checkout_station_id: null,
        new_balance: null,
        actual_difference: null,
      }),
      hover: null,
    };
  },

  mounted() {
    this.getDrawersReport();
  },

  methods: {
    getDrawersReport() {
      this.$store.dispatch("getCashDrawersReport").then((data) => {
        this.drawersData = _.groupBy(data, (d) => d.store_id);
      });
    },

    showEmployeeModal(storeId, station) {
      this.selectedStore = storeId;
      this.selectedStation = station;
      this.$refs.employeeModal.openModal();
    },

    showBalanceModal(storeId, station) {
      this.selectedStore = storeId;
      this.selectedStation = station;
      this.form.new_balance = station.starting_balance;
      this.form.checkout_station_id = station.id;
      this.$refs.balanceModal.openModal();
    },

    setNewDrawerBalance() {
      this.$store
        .dispatch("setNewDrawerBalance", this.form.dirtyData)
        .then(() => {
          this.$refs.balanceModal.closeModal();
          this.form.reset();
          this.getDrawersReport();
        });
    },

    getStoreName,
    formatCurrency,
    getUsersName,
  },
};
</script>
