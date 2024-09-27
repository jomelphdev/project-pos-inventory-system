<template>
  <div class="qz-tray--container qz-tray--connect" v-if="hasParent && !hide">
    <div class="qz-tray--connect--header--wrapper" v-if="!hideHeader">
      <div class="qz-tray--connect--label--wrapper">
        <slot name="label">
          <h3 class="rr-title-h3">
            Connection
          </h3>
        </slot>
      </div>

      <div class="qz-tray--connect--status--wrapper mb-4">
        <slot name="status" :connectionStatus="connectionStatus">
          <h3 class="qz-tray--connect--status" :class="connectedClass">
            {{ connectionStatus }}
          </h3>
        </slot>
      </div>
    </div>

    <slot></slot>

    <div class="qz-tray--connect--connection--wrapper" v-if="!hideBody">
      <div class="qz-tray--connect--connect--wrapper mb-2">
        <slot
          name="button-connect"
          :connect="connect"
          :connected="connected"
          :loading="loading"
        >
          <button
            class="qz-tray--connect--connect--button qz-tray--button rr-button rr-button--primary"
            v-show="!connected"
            :disabled="loading"
            @click="connect"
          >
            Connect
          </button>
        </slot>
      </div>

      <div class="qz-tray--connect--disconnect--wrapper mb-2">
        <slot
          name="button-disconnect"
          :disconnect="disconnect"
          :connected="connected"
          :loading="loading"
        >
          <button
            class="qz-tray--connect--disconnect--button qz-tray--button rr-button"
            v-show="connected"
            :disabled="loading"
            @click="disconnect"
          >
            Disconnect
          </button>
        </slot>
      </div>

      <div class="qz-tray--connect--launch--wrapper">
        <slot
          name="button-launch"
          :launch="launch"
          :connected="connected"
          :loading="loading"
        >
          <button
            class="qz-tray--connect--launch--button qz-tray--button rr-button"
            v-show="!connected"
            :disabled="loading"
            @click="launch"
          >
            Launch QZ Tray
          </button>
        </slot>
      </div>
    </div>

    <qz-tray-help></qz-tray-help>
    <qz-tray-print-settings></qz-tray-print-settings>
  </div>
</template>

<script>
import axios from "axios";
import store from "../store/index";
import QzMixin from "@/qz/qz-mixin";
import qzTrayPrintSettings from "./qz-tray-print-settings.vue";
import { mapGetters } from "vuex";

export default {
  components: { qzTrayPrintSettings },
  name: "QzTrayConnect",

  label: "connect",

  mixins: [QzMixin],

  props: {
    certificatePromiseCallback: {
      type: Function,
      default: null
    },

    signaturePromiseCallback: {
      type: Function,
      default: null
    }
  },

  data: function() {
    return {
      connected: false,
      connecting: false,
      disconnecting: false,
      failed: false
    };
  },

  computed: {
    ...mapGetters(["currentUser"]),

    loading: function() {
      return this.connecting || this.disconnecting;
    },

    connectionStatus: function() {
      if (this.failed) {
        return "Error";
      }

      if (this.disconnecting) {
        return "Disconnecting";
      }

      if (this.connecting) {
        return "Connecting";
      }

      return this.connected ? "✓ Connected" : "Disconnected";
    },

    connectedClass: function() {
      return {
        "text-green-600": this.connected,
        "text-gray-500": !this.connected
      };
    }
  },

  watch: {
    connected: {
      immediate: true,
      handler: function(newConnected, oldConnected) {
        this.$emitLocalAndRoot(
          "connection-changed",
          newConnected,
          oldConnected
        );
      }
    },
    currentUser() {
      if (this.currentUser) {
        if (!this.$qz.websocket.isActive()) {
          this.connect();
        }

        this.launchQzTray();
      }
    }
  },

  methods: {
    launch: function() {
      if (!this.qzIsInitialized()) {
        return;
      }

      window.location.assign("qz:launch");

      this.connect();
    },

    connect: function() {
      if (!this.qzIsInitialized()) {
        return;
      }

      if (this.loading || this.$qz.websocket.isActive()) {
        console.warn("An active connection with QZ already exists.");

        this.$emitLocalAndRoot("connection-exists-warning");

        return;
      }

      this.failed = false;
      this.connecting = true;

      this.$qz.websocket
        .connect(this.$qzConfig.connect)
        .then(this.connectResolve)
        .catch(this.websocketError)
        .finally(this.connectFinally);
    },

    disconnect: function() {
      if (!this.qzIsInitialized()) {
        return;
      }

      if (this.loading || !this.$qz.websocket.isActive()) {
        console.warn("No active connection with QZ exists.");

        this.$emitLocalAndRoot("connection-not-exists-warning");

        return;
      }

      this.failed = false;
      this.disconnecting = true;

      this.$qz.websocket
        .disconnect()
        .then(this.disconnectResolve)
        .catch(this.websocketError)
        .finally(this.disconnectFinally);
    },

    websocketError: function(error) {
      this.failed = true;
      this.connected = false;
      this.connecting = false;
      this.disconnecting = false;

      console.error(error);

      if (error.target !== undefined && error.target.readyState >= 2) {
        // readyState >= 2, means CLOSING or CLOSED
        this.$emitLocalAndRoot("connection-closing-error", error);
      } else {
        this.$emitLocalAndRoot("connection-websocket-error", error);
      }
    },

    websocketClosed: function(closeEvent) {
      this.connected = false;
      this.connecting = false;
      this.disconnecting = false;

      if (closeEvent.reason) {
        console.warn("Connection closed:", closeEvent.reason);
      }

      this.$emitLocalAndRoot("connection-closed", closeEvent);
    },

    connectResolve: function() {
      this.connected = true;
      this.failed = false;

      this.$emitLocalAndRoot("connection-success");
    },

    connectFinally: function() {
      this.connecting = false;
    },

    disconnectResolve: function() {
      this.connected = false;
      this.failed = false;

      this.$emitLocalAndRoot("disconnection-success");
    },

    disconnectFinally: function() {
      this.disconnecting = false;
    },

    launchQzTray() {
      this.$qz.security.setCertificatePromise((resolve, reject) => {
        axios
          .get(`${store.state.service.laravelUrl}/api/qz/cert`)
          .then(response => {
            response.status === 200 ? resolve(response.data) : reject(response);
          });
      });

      this.$qz.security.setSignatureAlgorithm("SHA512");
      this.$qz.security.setSignaturePromise(dataToSign => {
        return function(resolve, reject) {
          axios
            .get(`${store.state.service.laravelUrl}/api/qz/sign`, {
              params: { data: dataToSign }
            })
            .then(response => {
              response.status === 200
                ? resolve(response.data)
                : reject(response);
            });
        };
      });
    }
  },

  created: function() {
    if (this.$qz && this.$qz.security && this.currentUser) {
      this.launchQzTray();
    }
  },

  mounted: function() {
    if (!this.qzIsInitialized()) {
      return;
    }

    this.$qz.websocket.setClosedCallbacks(this.websocketClosed);
    this.$qz.websocket.setErrorCallbacks(this.websocketError);

    if (this.$qz.websocket.isActive()) {
      this.connected = true;
    } else if (this.currentUser) {
      this.connect();
    }
  }
};
</script>
