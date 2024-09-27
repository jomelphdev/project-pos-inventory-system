<template>
  <div>
    <data-table
      v-if="
        directories.length > 0 ||
        (files.length > 0 && !!dir_store_id) ||
        (directories.length == 0 && files.length == 0 && !!dir_store_id)
      "
      data-test="directory-table"
    >
      <data-table-header>
        <data-table-header-cell>
          <div
            class="flex text-black items-center mb-4 cursor-pointer"
            v-if="dir_store_id"
            @click="dir_store_id = null"
            data-test="back-button"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="w-5 h-5 mr-2"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"
              />
            </svg>
            Back
          </div>
          <div class="flex text-gray-700">
            {{ report_type.replace("_", " ") }}/{{
              dir_store_id && !loading ? getStoreName(dir_store_id) + "/" : ""
            }}
            <LoadingSpinner v-if="loading" size="sm" class="ml-2" />
          </div>
        </data-table-header-cell>
        <data-table-header-cell
          v-if="files.length > 0 && report_type != 'daily_sales'"
          data-test="fromDate-header"
        >
          From Date
        </data-table-header-cell>
        <data-table-header-cell
          v-if="files.length > 0"
          colspan="2"
          data-test="toDate-header"
        >
          {{ report_type != "daily_sales" ? "To" : "For" }} Date
        </data-table-header-cell>
      </data-table-header>
      <data-table-body>
        <data-table-row
          v-for="dir in directories"
          :key="'directory_' + dir"
          @click.native="dir_store_id = dir"
          :data-test="'directory-' + dir"
        >
          <data-table-cell>
            {{ getStoreName(dir) }}
          </data-table-cell>
        </data-table-row>
        <data-table-row
          class="relative"
          v-for="file in files"
          :key="'file_' + file.id"
          @mouseenter.native="hover = file.id"
          @mouseleave.native="hover = null"
          :data-test="'files-row-' + file.id"
        >
          <data-table-cell>
            {{ file.file_download_name }}.xlsx
          </data-table-cell>
          <data-table-cell>
            {{
              new Date(file.from_date).toLocaleDateString("en-US", {
                year: "numeric",
                month: "2-digit",
                day: "2-digit",
              })
            }}
          </data-table-cell>
          <data-table-cell
            v-if="report_type != 'daily_sales'"
            data-test="toDate-cell"
          >
            {{
              new Date(file.to_date).toLocaleDateString("en-US", {
                year: "numeric",
                month: "2-digit",
                day: "2-digit",
              })
            }}
          </data-table-cell>
          <data-table-cell
            v-if="hover == file.id"
            class="absolute inset-0 bg-gray-100/75 flex justify-center items-center"
            disableDefaultClass
            data-test="file-hover"
          >
            <div class="flex">
              <button
                class="rr-button font-semibold mr-4"
                @click="downloadReport(file.file_path, file.file_download_name)"
                :disabled="loading"
                data-test="download-button"
              >
                Download
              </button>
              <button
                class="rr-button font-semibold mr-4"
                @click="regenerateReport(file.id)"
                :disabled="loading"
                data-test="regenerate-button"
              >
                Regenerate
              </button>
              <button
                class="rr-button rr-button--danger font-semibold"
                @click="confirmDelete(file.id)"
                :disabled="loading"
                data-test="delete-button"
              >
                Delete
              </button>
            </div>
          </data-table-cell>
        </data-table-row>
        <data-table-row
          v-if="directories.length == 0 && files.length == 0 && !loading"
          data-test="no-reports"
        >
          <data-table-cell> No reports found. </data-table-cell>
        </data-table-row>
      </data-table-body>
    </data-table>
    <ConfirmationModal ref="confirmationModal" @response="handleResponse" />
  </div>
</template>

<script>
import { getStoreName } from "@/helpers";

import DataTable from "@/components/table/DataTable.vue";
import DataTableHeader from "@/components/table/DataTableHeader.vue";
import DataTableHeaderCell from "@/components/table/DataTableHeaderCell.vue";
import DataTableBody from "@/components/table/DataTableBody.vue";
import DataTableRow from "@/components/table/DataTableRow.vue";
import DataTableCell from "@/components/table/DataTableCell.vue";
import LoadingSpinner from "@/components/LoadingSpinner.vue";
import ConfirmationModal from "@/components/ConfirmationModal.vue";

export default {
  props: {
    report_type: String,
  },

  components: {
    DataTable,
    DataTableHeader,
    DataTableHeaderCell,
    DataTableBody,
    DataTableRow,
    DataTableCell,
    LoadingSpinner,
    ConfirmationModal,
  },

  watch: {
    dir_store_id() {
      this.getDirectories();
    },
  },

  data() {
    return {
      dir_store_id: null,
      directories: [],
      files: [],
      loading: false,
      hover: null,
      file_id: null,
    };
  },

  mounted() {
    this.getDirectories();
  },

  methods: {
    getDirectories() {
      this.loading = true;

      this.$store
        .dispatch("getReportDirectories", {
          report_type: this.report_type,
          store_id: this.dir_store_id,
        })
        .then(({ directories, files }) => {
          this.directories = directories;
          this.files = files;
        })
        .finally(() => {
          this.loading = false;
        });
    },

    downloadReport(path, filename) {
      this.loading = true;

      this.$store
        .dispatch("downloadReport", { path, filename })
        .finally(() => (this.loading = false));
    },

    regenerateReport(id) {
      this.loading = true;

      this.$store
        .dispatch("regenerateReport", { id })
        .finally(() => (this.loading = false));
    },

    deleteReport() {
      this.loading = true;

      this.$store
        .dispatch("deleteReport", { id: this.file_id })
        .then(() => {
          this.getDirectories();
        })
        .finally(() => (this.loading = false));
    },

    confirmDelete(id) {
      this.file_id = id;
      this.$refs.confirmationModal.openModal();
    },

    handleResponse(response) {
      if (response) this.deleteReport();
    },

    getStoreName,
  },
};
</script>

<style></style>
