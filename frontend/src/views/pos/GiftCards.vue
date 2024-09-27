<template>
  <div class="container">
    <div class="rr-field no-print">
      <input
        class="rr-field__input"
        type="text"
        v-model="searchQuery"
        placeholder="Search Gift Code, Title or Description"
      />
    </div>

    <div class="flex flex-row justify-between">
      <div class="flex items-baseline">
        <h1 class="h1">Gift Cards</h1>
      </div>
      <div>
        <button
          class="rr-button rr-button--primary inline"
          @click="createGiftCard"
        >
          Create Gift Card
        </button>
      </div>
    </div>

    <div
      class="p-8 flex justify-center items-center"
      style="height: 65vh"
      v-if="giftCards.length == 0"
    >
      <span class="text-sm font-medium text-gray-900"> No records found. </span>
    </div>

    <div v-else>
      <table
        class="rr-table min-w-full table-auto shadow-lg rounded-md overflow-hidden mb-4"
      >
        <thead>
          <tr>
            <th class="rr-table__th">Gift Code</th>
            <th class="rr-table__th">Title</th>
            <th class="rr-table__th">Balance</th>
            <th class="rr-table__th">Created by</th>
            <th class="rr-table__th">Expiration Date</th>
          </tr>
        </thead>

        <tbody class="bg-white" data-test="stores-table-body" v-if="loading">
          <GiftCardSkeleton />
          <GiftCardSkeleton />
          <GiftCardSkeleton />
        </tbody>

        <tbody v-else class="bg-white" data-test="stores-table-body">
          <tr
            class="rr-table__tr--hover relative"
            v-for="gift in filteredGiftCards"
            :key="gift.id"
            @click="editGiftCard(gift)"
          >
            <td class="rr-table__td">
              <div class="text-sm leading-5 font-medium text-gray-900">
                <qr-code :text="gift.gift_code" :size="80"></qr-code>
                <span>{{ gift.gift_code }}</span>
              </div>
            </td>

            <td class="rr-table__td">
              <div class="text-sm leading-5 font-medium text-gray-900">
                {{ gift.title }}
              </div>
            </td>

            <td class="rr-table__td">
              <div class="text-sm leading-5 font-medium text-gray-900">
                {{ formatCurrency(gift.balance) }}
              </div>
            </td>

            <td class="rr-table__td">
              <div class="text-sm leading-5 font-medium text-gray-900">
                {{ gift.user.full_name }}
              </div>
            </td>

            <td class="rr-table__td">
              <div class="text-sm leading-5 font-medium text-gray-900">
                {{ gift.expiration_date | calendar }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add Modal  -->
    <modal ref="giftCardModal">
      <template v-slot:header> Create Gift Card </template>
      <template v-slot:body>
        <div class="rr-field mb-10">
          <span class="font-bold">Gift Code:</span> {{ giftCardForm.gift_code }}
        </div>

        <!-- Stores -->
        <div>
          <label class="rr-field__label">
            Stores

            <span
              class="rr-field__label-required"
              v-if="
                !$v.giftCardForm.storeIds.required || selectedStores.length == 0
              "
            >
              Required
            </span>
          </label>
        </div>
        <div class="mb-10">
          <div
            class="rr-field__radio mr-4 mb-4 inline-block"
            v-for="store in storesVisible"
            :key="store.id"
          >
            <input
              type="checkbox"
              v-model="selectedStores"
              :value="store.id"
              :id="`input${store.id}`"
              class="rr-field__radio-input"
            />
            <label
              :for="`input${store.id}`"
              class="rr-field__radio-label items-baseline"
            >
              {{ store.name }}
            </label>
          </div>
          <div class="rr-field__radio mr-4 mb-4 inline-block" key="input_all">
            <input
              type="checkbox"
              :id="`input_all`"
              class="rr-field__radio-input"
              :checked="allStoresSelected"
              @click="allStores"
            />
            <label
              :for="`input_all`"
              class="rr-field__radio-label items-baseline"
            >
              All
            </label>
          </div>
        </div>

        <div class="rr-field">
          <DatePicker
            class="w-full"
            label="Expiration Date"
            :mode="discountsDateMode"
            :minDate="minDate"
            :maxDate="maxDate"
            :required="!$v.giftCardForm.expiration_date.required"
            @date-selected="
              (date) =>
                (giftCardForm.expiration_date = date
                  ? dbDateString(date)
                  : null)
            "
            :default="
              giftCardForm.expiration_date
                ? new Date(giftCardForm.expiration_date)
                : null
            "
          />
        </div>

        <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
          <div class="rr-field">
            <label class="rr-field__label">
              Title
              <span
                class="rr-field__label-required"
                v-if="!$v.giftCardForm.title.required"
              >
                Required
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              v-model="giftCardForm.title"
            />
          </div>

          <div class="rr-field">
            <label class="rr-field__label">
              Description
              <span
                class="rr-field__label-required"
                v-if="!$v.giftCardForm.description.required"
              >
                Required
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              v-model="giftCardForm.description"
            />
          </div>
        </div>
      </template>
      <template v-slot:footer>
        <div class="flex">
          <button
            class="rr-button rr-button--lg rr-button--primary"
            @click.stop="saveGiftCard"
            :disabled="$v.giftCardForm.$invalid"
          >
            Save
          </button>

          <button
            class="rr-button rr-button--lg ml-4"
            @click="$refs.giftCardModal.closeModal()"
          >
            Cancel
          </button>
        </div>
      </template>
    </modal>

    <!-- QR Code Reader Modal -->
    <modal ref="scannerModal" :size="`2xl`">
      <template v-slot:header>Scan QR Code</template>
      <template v-slot:body>
        <CameraCodeScanner @scan="onScan" @load="onLoad"></CameraCodeScanner>
      </template>
    </modal>
  </div>
</template>

<script>
import Modal from "@/components/Modal";
import { required } from "vuelidate/lib/validators";
import { dbDateString, formatCurrency } from "@/helpers";
import DatePicker from "@/components/DatePicker.vue";
import Form from "@/classes/Form";
import { mapGetters } from "vuex";
import moment from "moment";

import GiftCardSkeleton from "@/components/GiftCardSkeleton";

import { CameraCodeScanner } from "vue-barcode-qrcode-scanner";

export default {
  name: "GiftCards",

  components: {
    Modal,
    DatePicker,
    GiftCardSkeleton,
    CameraCodeScanner,
  },

  data() {
    return {
      giftCards: [],

      loading: true,

      giftCardForm: new Form({
        gift_code: "",
        title: "",
        description: "",
        amount: null,
        expiration_date: null,
        storeIds: [],
        matchedStoreIds: true,
      }),

      selectedStores: [],
      giftCardStores: [],

      discountsDateMode: "date",
      minDate: new Date(),
      maxDate: null,

      searchQuery: "",
    };
  },

  mounted() {
    this.getGiftCards();
  },

  computed: {
    ...mapGetters(["storesVisible"]),

    allStoresSelected() {
      return this.selectedStores.length == this.storesVisible.length;
    },

    filteredGiftCards() {
      const query = this.searchQuery.toLowerCase();
      return this.giftCards.filter((item) => {
        return (
          item.gift_code.toLowerCase().includes(query) ||
          item.title.toLowerCase().includes(query) ||
          item.description.toLowerCase().includes(query) ||
          item.expiration_date.toLowerCase().includes(query) ||
          item.user.full_name.toLowerCase().includes(query)
        );
      });
    },
  },

  watch: {
    selectedStores(storesVisible) {
      this.giftCardForm.storeIds = storesVisible;
    },
  },

  methods: {
    onLoad({ controls, scannerElement, browserMultiFormatReader }) {
      console.log(controls);
      console.log(scannerElement);
      console.log(browserMultiFormatReader);
    },

    onScan({ result, raw }) {
      this.$refs.scannerModal.closeModal();
      this.giftCardForm.gift_code = result;
      this.$refs.giftCardModal.openModal();
      // ---- Scan result ----
      // "http://en.m.wikipedia.org"

      console.log(raw);
      // ---- Raw BrowserMultiFormatReader.decodeFromVideoDevice result ----
      // format: 11
      // numBits: 272
      // rawBytes: Uint8Array(34) [65, 150, 135, 71, 71, 3, 162, 242, 246, 86, 226, 230, 210, 231, 118, 150, 182, 151, 6, 86, 70, 150, 18, 230, 247, 38, 112, 236, 17, 236, 17, 236, 17, 236, buffer: ArrayBuffer(34), byteLength: 34, byteOffset: 0, length: 34, Symbol(Symbol.toStringTag): 'Uint8Array']
      // resultMetadata: Map(2) {2 => Array(1), 3 => 'Q'}
      // resultPoints: (4) [FinderPattern, FinderPattern, FinderPattern, AlignmentPattern]
      // text: "http://en.m.wikipedia.org"
      // timestamp: 1654535879486
    },

    async getGiftCards() {
      try {
        const data = await this.$store.dispatch("getGiftCards");
        this.giftCards = data;
      } catch (error) {
        console.error("Error fetching data:", error);
      } finally {
        this.loading = false;
      }
    },

    editGiftCard(giftcard) {
      this.$router.push({
        name: "pos.gift-cards.edit",
        params: {
          id: giftcard.id,
          giftcard: giftcard,
        },
      });
    },

    createGiftCard() {
      this.clearForm();
      this.$refs.scannerModal.openModal();
    },

    saveGiftCard() {
      this.$store
        .dispatch("createGiftCard", this.giftCardForm)
        .then(() => {
          this.getGiftCards();
          this.$refs.giftCardModal.closeModal();
          this.clearForm();

          this.$toasted.show("Gift card has been created.", {
            type: "success",
          });
        })
        .catch((e) => {
          if (e.status === 422) {
            this.$toasted.show(e.data.errors.gift_code[0], {
              type: "error",
            });
            this.$refs.giftCardModal.closeModal();
            this.clearForm();
          }
        });
    },

    clearForm() {
      this.selectedStores = [];
      this.giftCardStores = [];
      this.giftCardForm.title = "";
      this.giftCardForm.description = "";
      this.giftCardForm.expiration_date = null;
      this.giftCardForm.storeIds = [];
    },

    allStores() {
      if (this.allStoresSelected) return (this.selectedStores = []);
      this.selectedStores = this.storesVisible.map((s) => s.id);
    },

    dbDateString,
    formatCurrency,
  },

  validations() {
    return {
      giftCardForm: {
        title: { required },
        description: { required },
        expiration_date: { required },
        storeIds: { required },
      },
    };
  },

  filters: {
    calendar(date) {
      return moment(date).format("MMM Do, YYYY");
    },
  },
};
</script>
