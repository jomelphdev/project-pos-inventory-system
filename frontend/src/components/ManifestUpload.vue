<template>
  <modal ref="manifestUploadModal" class="centered" @closed="resetValues">
    <template v-slot:header>
      Manifest Upload
    </template>
    <template v-slot:body>
      <div class="flex flex-col">
        <div class="max-w-lg text-lg mb-2">
          Use the Excel template for importing manifest items.
        </div>

        <div class="flex justify-center items-center">
          <DocumentTextIcon size="20" class="text-blue-600 mr-1" />
          <a
            class="rr-link-blue"
            href="https://help.retailright.app/files/retailright-excel-import-template.xlsx"
            target="_blank"
          >
            RetailRight Excel Import Template
          </a>
        </div>
      </div>
    </template>
    <template v-slot:footer>
      <div>
        <div class="rr-field">
          <label class="rr-field__label text-left">
            Manifest Name

            <span
              class="rr-field__label-required"
              v-if="!$v.manifestName.required"
            >
              Required
            </span>
          </label>
          <input
            class="rr-field__input"
            type="text"
            v-model="manifestName"
            @input="$v.manifestName.$touch()"
          />
        </div>
        <label class="rr-field__label text-left">
          Excel File
          <span class="rr-field__label-required" v-if="!$v.file.required">
            Required
          </span>
        </label>
        <FileUpload
          :file.sync="file"
          :types="[
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
          ]"
          @upload-file="uploadManifest"
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
      file: null,
      manifestName: null
    };
  },

  methods: {
    uploadManifest() {
      this.$store
        .dispatch("uploadManifest", {
          file: this.file,
          manifest: this.manifestName
        })
        .then(() => {
          this.$toasted.show(
            "Manifest items will begin to populate in the manifest tab.",
            { type: "success" }
          );
          this.closeModal();
          this.resetValues();
        });
    },

    resetValues() {
      this.file = null;
      this.manifestName = null;
    },

    closeModal() {
      this.$refs.manifestUploadModal.closeModal();
    },

    openModal() {
      this.$refs.manifestUploadModal.openModal();
    }
  },

  validations() {
    return {
      manifestName: { required },
      file: { required }
    };
  }
};
</script>
