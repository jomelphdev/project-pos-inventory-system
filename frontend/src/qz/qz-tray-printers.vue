<template>
  <div class="qz-tray--container qz-tray--printers" v-if="hasParent && !hide">
    <div class="qz-tray--printers--header--wrapper" v-if="!hideHeader">
      <div class="qz-tray--printers--label--wrapper">
        <slot name="label">
          <h3 class="rr-title-h3">{{ mode | capitalize }} Printer</h3>
        </slot>
      </div>
    </div>

    <slot></slot>

    <div
      class="qz-tray--printers--list--wrapper app-printer-list"
      v-if="!hideBody"
    >
      <slot
        name="list-printers"
        :printers="printers"
        :selectedPrinter="selectedPrinter"
        :pickPrinter="pickPrinter"
        :failed="failed"
        :loading="loading"
      >
        <h6 class="qz-tray--printers--list-loading" v-if="loading">
          Searching for Printers...
        </h6>

        <h6 class="qz-tray--printers--list-failed" v-else-if="failed">Error</h6>

        <h6
          class="qz-tray--printers--list-empty text-gray-500"
          v-else-if="!printers || !printers.length"
        >
          No printers found.
        </h6>

        <div
          class="qz-tray--printers--list-items"
          :data-test="`printerList-${mode}`"
          v-else
        >
          <slot
            name="list-printers-select"
            :printers="printers"
            :selectedPrinter="selectedPrinter"
            :pickPrinter="pickPrinter"
            :failed="failed"
            :loading="loading"
          >
            <div
              v-for="(printer, index) in printers"
              :key="printer"
              class="rr-field__radio"
            >
              <input
                type="radio"
                :id="`${mode}-printer-${index}`"
                :value="printer"
                @click="checkIfSelectedPrinter(printer)"
                v-model="selectedPrinter"
                class="rr-field__radio-input"
              />
              <label
                :for="`${mode}-printer-${index}`"
                class="rr-field__radio-label break-all"
                >{{ printer }}</label
              >
            </div>
          </slot>
        </div>
      </slot>
    </div>
  </div>
</template>

<script>
import QzMixin from "@/qz/qz-mixin";
import { mapGetters } from "vuex";

export default {
  name: "QzTrayPrinters",

  label: "printers",

  mixins: [QzMixin],

  props: {
    printer: String,
    mode: {
      type: String,
      default: "label", // label or receipt
    },
  },

  data: function () {
    return {
      failed: false,
      loading: false,
      printers: null,
    };
  },

  computed: {
    ...mapGetters(["qzLabelPrinter", "qzReceiptPrinter"]),
    defaultPrinter: function () {
      return this.printer;
    },
    selectedPrinter: {
      get: function () {
        switch (this.mode) {
          case "label":
            return this.qzLabelPrinter;
          case "receipt":
            return this.qzReceiptPrinter;
          default:
            return "";
        }
      },
      set: function (newPrinter) {
        switch (this.mode) {
          case "label":
            this.$store.dispatch("updateQzPrinter", newPrinter);
            break;
          case "receipt":
            this.$store.dispatch("updateQzReceiptPrinter", newPrinter);
            break;
        }
      },
    },
  },

  watch: {
    defaultPrinter: {
      immediate: true,
      handler: function () {
        this.testDefaultPrinter();
      },
    },
  },

  methods: {
    retrievePrintersIfConnection: function (newConnectionStatus) {
      if (newConnectionStatus) {
        this.getPrinters();
      } else {
        this.printers = null;
      }
    },

    getPrinters: function () {
      if (!this.qzIsInitialized()) {
        return;
      }

      if (this.loading || !this.qzTrayConnected) {
        console.warn("No active connection with QZ exists.");

        this.$emitLocalAndRoot("connection-not-exists-warning");

        return;
      }

      this.failed = false;
      this.loading = true;

      this.$qz.printers
        .find()
        .then(this.printersResolve)
        .catch(this.websocketError)
        .finally(this.printersFinally);
    },

    pickPrinter: function (printer) {
      if (
        !this.printers ||
        !this.printers.length ||
        this.printers.indexOf(printer) < 0
      ) {
        this.selectedPrinter = "";

        if (!this.printers || !this.printers.length) {
          this.$emitLocalAndRoot("printers-empty-error");
        }

        this.$emitLocalAndRoot("printer-invalid-error", printer, this.printers);

        return;
      }

      this.selectedPrinter = printer;

      this.$emitLocalAndRoot("printer-set-success", printer);
    },

    testDefaultPrinter: function () {
      if (
        this.selectedPrinter &&
        this.selectedPrinter.length &&
        this.printers &&
        this.printers.indexOf(this.selectedPrinter) > -1
      ) {
        return;
      }

      if (this.defaultPrinter && this.defaultPrinter.length) {
        this.pickPrinter(this.defaultPrinter);
      }
    },

    printersResolve: function (data) {
      this.printers = data;
      this.failed = false;

      this.testDefaultPrinter();

      this.$emitLocalAndRoot("retrieved-success");
    },

    websocketError: function (error) {
      this.printers = null;
      this.failed = true;

      console.error(error);

      this.$emitLocalAndRoot("websocket-error", error);
    },

    printersFinally: function () {
      this.loading = false;
    },

    checkIfSelectedPrinter(printer) {
      if (this.selectedPrinter == printer) this.selectedPrinter = "";
    },
  },

  created: function () {
    if (this.$qzRoot) {
      this.$onRoot(
        "connect-connection-changed",
        this.retrievePrintersIfConnection
      );
    }
  },

  beforeDestroy: function () {
    if (this.$qzRoot) {
      this.$offRoot(
        "connect-connection-changed",
        this.retrievePrintersIfConnection
      );
    }
  },
};
</script>

<style lang="scss">
.app-selected-printer {
  @apply flex bg-white border-default border-gray-400 py-2 px-4 rounded-default mb-4;
}

.app-printer-list {
  &__link {
    @apply cursor-pointer;

    &:hover,
    &.selected {
      @apply text-blue-600 font-bold;
    }
  }
}
</style>
