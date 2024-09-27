<template>
  <div class="grid grid-cols-1 gap-8">
    <input
      type="file"
      id="file-input"
      class="rr-field__input"
      data-test="file-input"
      @change="selectFile($event)"
    />
    <button
      class="rr-button rr-button--lg rr-button--primary flex justify-center"
      data-test="upload-file"
      @click="uploadFile"
    >
      <UploadIcon size="20" class="mr-1 self-center" />
      Upload Excel File
    </button>
  </div>
</template>

<script>
import { UploadIcon } from "@vue-hero-icons/outline";

export default {
  props: {
    types: {
      type: Array,
      default: null
    },
    file: {
      default: null
    }
  },

  components: {
    UploadIcon
  },

  methods: {
    // TODO FOR FULL V-3: Allow multiple file upload. (Create job and run async)
    selectFile(event) {
      const file = event.target.files[0];
      if (
        (this.types && this.types.includes(file.type)) ||
        this.types == null
      ) {
        return this.$emit("update:file", file);
      }

      this.$toasted.show("Invalid file type.", { type: "error" });
      document.getElementById("file-input").value = "";
    },
    uploadFile() {
      if (!this.file) {
        return this.$toasted.show("Please select a file.", {
          type: "error"
        });
      }

      this.$emit("upload-file", this.file);
    }
  }
};
</script>
