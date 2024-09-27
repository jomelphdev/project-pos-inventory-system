<template>
  <modal
    ref="itemUploadModal"
    data-test="itemUploadModal"
    class="centered"
    @closed="resetValues"
  >
    <template v-slot:header>
      Item Inventory Upload
    </template>
    <template v-slot:body>
      <div class="flex flex-col">
        <div class="max-w-lg text-lg mb-2">
          Use the Excel template for importing items directly into inventory.
        </div>

        <div class="flex justify-center items-center">
          <DocumentTextIcon size="20" class="text-blue-600 mr-1" />
          <a
            class="rr-link-blue"
            href="https://help.retailright.app/files/retailright-inventory-import-template.xlsx"
            target="_blank"
          >
            RetailRight Excel Inventory Import Template
          </a>
        </div>
      </div>
    </template>
    <template v-slot:footer>
      <div>
        <label class="rr-field__label text-left">
          Excel File
          <span
            class="rr-field__label-required"
            data-test="file-required"
            v-if="!$v.file.required"
          >
            Required
          </span>
        </label>
        <FileUpload
          :file.sync="file"
          :types="[
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
          ]"
          @upload-file="uploadItems"
        />
      </div>
    </template>
  </modal>
</template>

<script>
import { required } from "vuelidate/lib/validators";

import Modal from "./Modal.vue";
import FileUpload from "./FileUpload.vue";
import { DocumentTextIcon } from "@vue-hero-icons/outline";

export default {
  components: { Modal, FileUpload, DocumentTextIcon },

  data() {
    return {
      file: null
    };
  },

  methods: {
    uploadItems() {
      this.$store.dispatch("uploadItems", this.file).then(() => {
        this.$toasted.show("Items will begin to populate in the Items tab.", {
          type: "success"
        });
        this.closeModal();
        this.file = null;
      });
    },

    resetValues() {
      this.file = null;
    },

    closeModal() {
      this.$refs.itemUploadModal.closeModal();
    },

    openModal() {
      this.$refs.itemUploadModal.openModal();
    }
  },

  validations() {
    return {
      file: { required }
    };
  }
};
</script>
