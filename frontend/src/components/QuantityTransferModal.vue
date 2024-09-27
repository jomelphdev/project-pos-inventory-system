<template>
  <modal-wall ref="quantityTransferModal" data-test="quantityTransferModal">
    <template v-slot:header>
      <a
        href="#"
        class="modal__close"
        @click.prevent.stop="closeModal(true)"
        data-test="closeTransferModal-button"
      >
        Close
      </a>
      <div class="text-center">
        <h1 class="h1" v-if="modalStep == 0">
          Select store you want <br />to transfer quantity from.
        </h1>

        <h1 class="h1" v-if="modalStep == 1">
          Enter amount to transfer from <br />"{{ sendingStore.name }}".
        </h1>

        <h1 class="h1" v-if="modalStep == 2">
          Select store you want <br />to transfer quantity to.
        </h1>

        <h1 class="h1" v-if="modalStep == 3">
          Confirmation
        </h1>
      </div>
    </template>
    <template v-slot:body>
      <div v-if="modalStep == 0" data-test="bodyStep-0">
        <div
          class="grid auto-rows-auto gap-4 justify-items-center"
          data-test="sendingStores"
        >
          <button
            class="rr-button--lg rr-button--primary justify-center"
            v-for="(store, index) in eligibleStoresWithQuantity"
            :key="'store_' + store.id"
            @click="selectSendingStore(store)"
            :data-test="`sendingStore_${index}`"
          >
            {{ store.name }}
          </button>
        </div>
      </div>

      <div v-if="modalStep == 1" data-test="bodyStep-1">
        <div class="text-center text-sm mb-4" data-test="availableQuantity">
          Available Quantity to Transfer: {{ quantityAvailableForTransfer }}
        </div>

        <div
          class="flex items-center w-full max-w-md shadow"
          :class="{
            'ml-auto': quantityToTransfer == 0,
            'justify-center': quantityToTransfer > 0,
            'm-auto': quantityToTransfer > 0
          }"
        >
          <div
            v-if="quantityToTransfer > 0"
            @click="quantityToTransfer--"
            class="svg svg-key-decrease keypad-ui__decrease"
            data-test="decreaseTransferAmount-button"
          ></div>
          <div>
            <input
              type="number"
              v-model="quantityToTransfer"
              class="keypad-ui__input mx-4"
              data-test="quantityTransferAdjustment-input"
              :class="amountClass(quantityToTransfer)"
            />
          </div>
          <div
            @click="quantityToTransfer++"
            class="svg svg-key-increase keypad-ui__increase"
            data-test="increaseTransferAmount-button"
          ></div>
        </div>
        <div class="text-center mt-4 space-x-2">
          <button
            class="rr-button--lg rr-button--primary"
            v-if="quantityToTransfer > 0"
            @click="modalStep = 2"
            data-test="continue-button"
          >
            Continue
          </button>
          <button
            class="rr-button--lg rr-button--danger"
            @click="
              () => {
                modalStep--;
                quantityToTransfer = 0;
              }
            "
            data-test="modalGoBack-button"
          >
            Go Back
          </button>
        </div>
      </div>

      <div v-if="modalStep == 2" data-test="bodyStep-2">
        <div
          class="grid auto-rows-auto gap-4 justify-items-center"
          data-test="receivingStores"
        >
          <button
            class="rr-button--lg rr-button--primary justify-center"
            v-for="(store, index) in eligibleReceivingStores"
            :key="'store_' + store.id"
            @click="selectReceivingStore(store)"
            :data-test="`receivingStore_${index}`"
          >
            {{ store.name }}
          </button>
        </div>

        <div class="text-center mt-8">
          <button
            class="rr-button--lg rr-button--danger justify-center"
            @click="modalStep--"
            data-test="modalGoBack-button"
          >
            Go Back
          </button>
        </div>
      </div>

      <div class="text-center" v-if="modalStep == 3" data-test="bodyStep-3">
        <div class="text-lg">
          You are transfering
          <strong data-test="quantityToTransfer">{{
            quantityToTransfer
          }}</strong>
          quantity from <br />
          <strong data-test="sendingStoreName"
            >"{{ sendingStore.name }}"</strong
          >
          to
          <strong data-test="receivingStoreName"
            >"{{ receivingStore.name }}"</strong
          >
        </div>
        <div class="mt-8 space-x-2">
          <button
            class="rr-button--lg rr-button--primary-solid justify-center"
            @click="confirmTransfer"
            data-test="confirmTransfer-button"
          >
            Confirm
          </button>
          <button
            class="rr-button--lg rr-button--danger justify-center"
            @click="modalStep--"
            data-test="modalGoBack-button"
          >
            Go Back
          </button>
        </div>
      </div>
    </template>
  </modal-wall>
</template>

<script>
import { mapGetters } from "vuex";

import { amountClass } from "@/helpers";

import ModalWall from "./ModalWall.vue";

export default {
  components: {
    ModalWall
  },

  props: {
    itemId: {
      type: Number,
      required: true
    },
    storesWithQuantity: {
      type: Array,
      required: true
    }
  },

  computed: {
    ...mapGetters(["stores", "storesVisible", "currentUser"]),

    eligibleStoresWithQuantity() {
      return this.stores.filter(store => {
        const storeQuantity = this.storesWithQuantity.find(
          s => s.store_id == store.id
        );

        return storeQuantity && storeQuantity.quantity > 0;
      });
    },

    eligibleReceivingStores() {
      return this.storesVisible.filter(
        store => store.id != this.sendingStore.id
      );
    },

    quantityAvailableForTransfer() {
      return this.storesWithQuantity.find(
        store => store.store_id == this.sendingStore.id
      ).quantity;
    }
  },

  watch: {
    quantityToTransfer(amount) {
      if (amount < 0) {
        this.quantityToTransfer = 0;
        this.$toasted.show("Cannot transfer a negative amount.", {
          type: "info"
        });
      } else if (amount > this.quantityAvailableForTransfer) {
        this.quantityToTransfer = this.quantityAvailableForTransfer;
        this.$toasted.show(
          `The max available for transfer is ${this.quantityAvailableForTransfer}.`,
          { type: "info" }
        );
      }
    }
  },

  data() {
    return {
      modalStep: 0,
      sendingStore: null,
      receivingStore: null,
      quantityToTransfer: 0
    };
  },

  methods: {
    confirmTransfer() {
      this.$store
        .dispatch("updateItem", {
          itemId: this.itemId,
          update: {
            quantities: [
              {
                store_id: this.sendingStore.id,
                created_by: this.currentUser.id,
                quantity_received: -this.quantityToTransfer,
                message: `Quantity transfered to "${this.receivingStore.name}"`,
                is_transfer: true
              },
              {
                store_id: this.receivingStore.id,
                created_by: this.currentUser.id,
                quantity_received: this.quantityToTransfer,
                message: `Quantity received from "${this.sendingStore.name}"`,
                is_transfer: true
              }
            ]
          }
        })
        .then(() => {
          this.$toasted.show("Quantity transfer successful.", {
            type: "success"
          });
          this.closeModal();

          setTimeout(() => window.location.reload(), 250);
        });
    },

    selectSendingStore(store) {
      this.sendingStore = store;
      this.modalStep = 1;
    },

    selectReceivingStore(store) {
      this.receivingStore = store;
      this.modalStep = 3;
    },

    openModal() {
      if (this.eligibleStoresWithQuantity.length == 0) {
        this.$toasted.show("No stores with active quantity to transfer.", {
          type: "info"
        });
        return this.closeModal();
      }

      this.$refs.quantityTransferModal.openModal();
    },

    closeModal(closeButtonClicked = false) {
      if (closeButtonClicked) {
        this.modalStep = 0;
        this.quantityToTransfer = 0;
        this.sendingStore = null;
        this.receivingStore = null;
      }

      this.$refs.quantityTransferModal.closeModal();
    },

    amountClass
  }
};
</script>
