<template>
  <div
    class="qz-tray--container qz-tray--action rr-card shadow-md px-6 py-8"
    v-if="hasParent && !hide"
  >
    <div class="qz-tray--action--header--wrapper" v-if="!hideHeader">
      <div class="qz-tray--action--label--wrapper">
        <slot name="label">
          <h3 class="rr-title-h3">
            Preview
          </h3>
        </slot>
      </div>
    </div>

    <transition name="fade-in">
      <div
        v-if="currentLabel"
        class="border rounded-md shadow-md p-4 bg-yellow-100"
      >
        <div class="leading-5 font-bold text-gray-900 mb-1">
          {{ currentLabel.price | currency }}
        </div>
        <div class="text-sm leading-5 font-medium text-gray-900">
          {{ currentLabel.title | truncate(30) }}
        </div>
        <div
          v-text="currentLabel.upc"
          class="text-xs leading-5 text-gray-500"
        />
      </div>
    </transition>
    <div
      class="rr-card rr-card--inset bg-gray-100 px-6 py-6 text-center"
      v-if="!currentLabel"
    >
      <span class="text-sm text-gray-900">
        Waiting for label
      </span>
    </div>

    <slot></slot>

    <div class="qz-tray--action--action--wrapper" v-show="false">
      <div class="qz-tray--action--status--wrapper mb-4">
        <slot name="status" :status="status">
          <span class="qz-tray--action--status">{{ status }}</span>
        </slot>
      </div>

      <div class="qz-tray--action--print--wrapper" v-show="false">
        <slot name="button-print" :print="print" :actionStatus="actionStatus">
          <button
            class="qz-tray--action--print--button qz-tray--button rr-button rr-button--primary"
            :disabled="
              actionStatus.loading ||
                !qzTrayConnected ||
                !printerSelected ||
                !pagesCount
            "
            @click="print"
          >
            Print
          </button>
        </slot>
      </div>
    </div>
  </div>
</template>

<script>
import QzMixin from "@/qz/qz-mixin";

export default {
  name: "QzTrayAction",

  label: "action",

  mixins: [QzMixin],

  data: function() {
    return {
      actionStatus: {},
      currentLabel: null
    };
  },

  computed: {
    status: function() {
      if (this.actionStatus.failed) {
        return "Error";
      }

      if (this.actionStatus.loading) {
        return "Printing...";
      }

      if (!this.qzTrayConnected) {
        return "No connection";
      }

      if (!this.printerSelected) {
        return "No printer selected";
      }

      // if (!this.pagesCount) {
      //   return "No pages to print";
      // }

      if (this.actionStatus.printed) {
        return "Printed";
      }

      return "Ready";
    }
  },

  methods: {
    print: function() {
      // eslint-disable-next-line no-console
      console.log("START PRINT");

      if (!this.qzIsInitialized()) {
        return;
      }

      if (!this.$qzRoot) {
        console.error("Vue QZ Tray not initialized properly.");

        this.$emitLocalAndRoot("qz-tray-not-initialized-error");

        return;
      }

      if (this.actionStatus.loading || !this.qzTrayConnected) {
        console.error("No active connection with QZ exists.");

        this.$emitLocalAndRoot("connection-not-exists-error");

        return;
      }

      this.actionStatus.loading = true;
      this.actionStatus.failed = false;

      this.$qzRoot
        .printLabel()
        // .startPrint()
        .then(() => {
          // eslint-disable-next-line no-console
          console.log("SUCCESS PRINT");

          this.actionStatus.printed = true;
        })
        .catch(error => {
          console.error("ERROR PRINT", error);

          this.actionStatus.failed = true;
        })
        .finally(() => {
          this.actionStatus.loading = false;
        });
    }
  },

  mounted: function() {
    if (this.$qzRoot) {
      this.actionStatus = this.$qzRoot.actionStatus;
    }

    // For print demo
    this.$root.$on("print-label", item => {
      this.currentLabel = item;
    });

    // For print demo
    this.$root.$on("view-item", () => {
      this.currentLabel = null;
    });
  }
};
</script>
