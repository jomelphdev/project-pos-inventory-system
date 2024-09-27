<template>
  <div class="container">
    <div class="item-ui">
      <div class="item-ui__main">
        <div class="mb-10">
          <qr-code :text="giftCardForm.gift_code" :size="100"></qr-code>
          <p>{{ giftCardForm.gift_code }}</p>
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

        <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
          <div class="rr-field mb-10">
            <DatePicker
              class="w-full"
              label="Expiration Date"
              :mode="discountsDateMode"
              :minDate="minDate"
              :maxDate="maxDate"
              :default="new Date(giftCardForm.expiration_date)"
              @date-selected="
                (date) =>
                  (giftCardForm.expiration_date = date
                    ? dbDateString(date)
                    : null)
              "
            />
          </div>

          <div class="rr-field">
            <label class="rr-field__label"> Balance </label>
            <currency-input
              class="rr-field__input"
              v-model="giftCardForm.balance"
              disabled
            />
          </div>
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

        <div class="flex">
          <button
            class="rr-button rr-button--lg rr-button--primary"
            @click.stop="updateGiftCard"
            :disabled="
              $v.giftCardForm.$invalid ||
              giftCardForm.is_activated == 0 ||
              isExpired
            "
          >
            Update
          </button>

          <button
            class="rr-button rr-button--lg rr-button--primary ml-4"
            @click="$router.push({ name: 'pos.gift-cards' })"
          >
            Go Back
          </button>
        </div>
      </div>

      <div class="item-ui__aside">
        <div>
          <h1 class="h2">Actions</h1>
        </div>
        <div class="grid grid-rows-3 gap-4 mt-4">
          <button
            @click="topUp"
            class="rr-button rr-button--primary flex justify-center py-3"
            :disabled="giftCardForm.is_activated == 0 || isExpired"
          >
            Top-up
          </button>
          <button
            class="rr-button flex justify-center py-3"
            :class="{
              'rr-button--primary': giftCardForm.is_activated === 0,
              'rr-button--danger': giftCardForm.is_activated !== 0,
            }"
            :disabled="isExpired"
            @click="confirmActivateDeactivate"
          >
            {{ giftCardForm.is_activated === 0 ? "Activate" : "Deactivate" }}
          </button>
        </div>

        <div v-if="giftCardTopUps.length > 0" class="h2 pb-3">Transactions</div>
        <div class="max-h-[370px] overflow-y-auto">
          <div v-if="loading">
            <GiftCardTopUpSkeleton />
            <GiftCardTopUpSkeleton />
            <GiftCardTopUpSkeleton />
          </div>
          <ul
            v-else
            class="max-w-md divide-y divide-gray-200 dark:divide-gray-700"
          >
            <li
              v-for="(gift, index) in giftCardTopUps"
              :key="index"
              class="pb-3 sm:pb-4"
            >
              <div class="flex items-center space-x-4 rtl:space-x-reverse">
                <div class="flex-1 min-w-0">
                  <p
                    class="text-sm font-medium text-gray-900 truncate dark:text-white"
                  >
                    {{
                      gift.action == 1
                        ? "Funds added"
                        : gift.action == 2
                        ? "Item sold"
                        : "Item returned"
                    }}
                  </p>
                  <p class="text-sm text-gray-500 truncate dark:text-gray-400">
                    {{ gift.created_at | calendar }}
                  </p>
                </div>
                <div
                  class="inline-flex items-center text-base font-semibold text-gray-900 dark:text-white"
                  :class="giftCardTopUps.length > 5 ? 'pr-3' : ''"
                >
                  {{ gift.action == 1 ? "+" : gift.action == 2 ? "-" : "+"
                  }}{{ formatCurrency(gift.amount) }}
                </div>
              </div>
              <hr
                class="text-sm text-gray-200 truncate dark:text-gray-200 mt-2"
              />
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Top-up -->
    <modal ref="topUpModal">
      <template v-slot:header> Add Funds </template>
      <template v-slot:body>
        <div class="rr-field">
          <label class="rr-field__label">
            Amount
            <span
              class="rr-field__label-required"
              v-if="!$v.topUpForm.amount.required"
            >
              Required
            </span>
          </label>
          <currency-input class="rr-field__input" v-model="topUpForm.amount" />
        </div>
      </template>
      <template v-slot:footer>
        <div class="flex">
          <button
            class="rr-button rr-button--lg rr-button--primary"
            @click="saveTopUp()"
            :disabled="$v.topUpForm.$invalid"
          >
            Save
          </button>

          <button
            class="rr-button rr-button--lg ml-4"
            @click="$refs.topUpModal.closeModal()"
          >
            Cancel
          </button>
        </div>
      </template>
    </modal>

    <!-- Activate Deactivate Modal  -->
    <modal
      ref="confirmActivateDeactivateModal"
      class="centered"
      data-test="confirm-delete-modal"
    >
      <template v-slot:header>
        Confirm {{ confirmActivation.status }}
      </template>
      <template v-slot:body>
        <div class="max-w-lg">
          <strong>Warning:</strong>
          Are you sure you want to {{ confirmActivation.status }}
          {{ confirmActivation.title }}?
        </div>
      </template>
      <template v-slot:footer>
        <button
          class="rr-button rr-button--lg"
          :class="{
            'rr-button--primary': confirmActivation.status === 'Activate',
            'rr-button--danger': confirmActivation.status !== 'Activate',
          }"
          data-test="confirmDelete-button"
          @click="activateDeactivate()"
        >
          {{ confirmActivation.status }}
        </button>

        <button
          class="rr-button rr-button--lg ml-4"
          data-test="cancelDelete-button"
          @click="$refs.confirmActivateDeactivateModal.closeModal()"
        >
          Cancel
        </button>
      </template>
    </modal>
  </div>
</template>

<script>
import Form from "@/classes/Form";
import { required } from "vuelidate/lib/validators";
import { mapGetters } from "vuex";
import DatePicker from "@/components/DatePicker.vue";
import { dbDateString, generateQrCode, formatCurrency } from "@/helpers";
import Modal from "@/components/Modal";
import moment from "moment";
import GiftCardTopUpSkeleton from "@/components/GiftCardTopUpSkeleton";

export default {
  name: "GiftCardsEdit",

  components: {
    DatePicker,
    Modal,
    GiftCardTopUpSkeleton,
  },

  data() {
    return {
      giftCards: [],

      giftCardForm: new Form({
        gift_code: "",
        title: "",
        description: "",
        amount: null,
        expiration_date: this.$route.params.giftcard.expiration_date,
        storeIds: [],
        matchedStoreIds: true,
      }),

      selectedStores: [],
      giftCardStores: [],
      giftCardTopUps: [],

      discountsDateMode: "date",
      minDate: new Date(),
      maxDate: null,

      topUpForm: {
        giftId: null,
        amount: null,
      },

      confirmActivation: {
        id: null,
        is_activated: null,
        title: "",
        status: "",
      },

      loading: true,

      passExpirationDate: null,
    };
  },

  mounted() {
    if (this.$route.params.giftcard) {
      const gift = this.$route.params.giftcard;

      this.passExpirationDate = this.$route.params.giftcard.expiration_date;

      this.giftCardStores = gift.gift_card_store;
      const filteredStoreIds = [
        ...new Set(
          this.giftCardStores
            .filter((card) =>
              this.storesVisible.some((store) => store.id === card.store_id)
            )
            .map((card) => card.store_id)
        ),
      ];
      this.selectedStores = filteredStoreIds;

      this.giftCardForm = gift;

      this.getGiftCardTopUp(this.giftCardForm.id);
    }
  },

  computed: {
    ...mapGetters(["storesVisible"]),

    allStoresSelected() {
      return this.selectedStores.length == this.storesVisible.length;
    },

    isExpired() {
      const currentDate = new Date();
      const formattedCurrentDate = currentDate.toISOString().split("T")[0];
      const expirationDate = new Date(this.passExpirationDate);
      const formattedExpirationDate = expirationDate
        .toISOString()
        .split("T")[0];
      return formattedExpirationDate <= formattedCurrentDate;
    },
  },

  watch: {
    selectedStores(storesVisible) {
      this.giftCardForm.storeIds = storesVisible;
    },
  },

  methods: {
    allStores() {
      if (this.allStoresSelected) return (this.selectedStores = []);
      this.selectedStores = this.storesVisible.map((s) => s.id);
    },

    updateGiftCard() {
      if (
        this.giftCardForm.gift_card_store.length ==
        this.giftCardForm.storeIds.length
      ) {
        // if true, still the same selected stores, means no changes
        const matchedStoreIds = this.giftCardForm.gift_card_store.every(
          (card) => this.giftCardForm.storeIds.includes(card.store_id)
        );

        if (matchedStoreIds) {
          this.giftCardForm.matchedStoreIds = true;
        } else {
          this.giftCardForm.matchedStoreIds = false;
        }
      } else {
        this.giftCardForm.matchedStoreIds = false;
      }

      this.$store
        .dispatch("updateGiftCard", {
          giftCard: this.giftCardForm,
          id: this.giftCardForm.id,
        })
        .then(() => {
          this.$toasted.show("Gift card has been updated.", {
            type: "success",
          });
        })
        .catch((e) => {
          console.log(e);
        });
    },

    topUp() {
      this.$refs.topUpModal.openModal();
      this.topUpForm.giftId = this.giftCardForm.id;
    },

    saveTopUp() {
      this.$store
        .dispatch("updateGiftCardBalance", this.topUpForm)
        .then(() => {
          this.$refs.topUpModal.closeModal();
          this.giftCardForm.balance += this.topUpForm.amount;
          this.topUpForm = {};

          this.$toasted.show("balance has been updated.", {
            type: "success",
          });

          this.getGiftCardTopUp(this.giftCardForm.id);
        })
        .catch((e) => {
          console.log(e);
        });
    },

    confirmActivateDeactivate() {
      const status =
        this.giftCardForm.is_activated == 0 ? "Activate" : "Deactivate";

      this.confirmActivation.id = this.giftCardForm.id;
      this.confirmActivation.is_activated = this.giftCardForm.is_activated;
      this.confirmActivation.title = this.giftCardForm.title;
      this.confirmActivation.status = status;

      this.$refs.confirmActivateDeactivateModal.openModal();
    },

    activateDeactivate() {
      // if 1 means activated, make it 0 to deactivate
      const status = this.confirmActivation.is_activated == 1 ? 0 : 1;

      this.$store
        .dispatch("activateDeactivate", {
          id: this.confirmActivation.id,
          is_activated: status,
        })
        .then((res) => {
          this.giftCardForm.is_activated = status;
          this.$refs.confirmActivateDeactivateModal.closeModal();
          this.$toasted.show(res.message, {
            type: "success",
          });
        })
        .catch((e) => {
          console.log(e);
        });
    },

    async getGiftCardTopUp(giftCardId) {
      try {
        const data = await this.$store.dispatch("getGiftCardTopUp", giftCardId);
        this.giftCardTopUps = data;
      } catch (error) {
        console.error("Error fetching data:", error);
      } finally {
        this.loading = false;
      }
    },
    dateToday() {
      const currentDate = new Date();
      const year = currentDate.getFullYear();
      const month = (currentDate.getMonth() + 1).toString().padStart(2, "0");
      const day = currentDate.getDate().toString().padStart(2, "0");
      const formattedDate = `${year}-${month}-${day}`;
      return formattedDate;
    },

    dbDateString,
    generateQrCode,
    formatCurrency,
  },

  validations() {
    return {
      giftCardForm: {
        title: { required },
        description: { required },
        storeIds: { required },
      },
      topUpForm: {
        amount: { required },
      },
    };
  },

  filters: {
    moment(date) {
      return moment(date).fromNow();
    },
    calendar(date) {
      return moment(date).format("MMM Do [at] h:mma");
    },
  },
};
</script>
