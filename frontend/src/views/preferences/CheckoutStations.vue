<template>
  <div>
    <div class="flex flex-row justify-between">
      <div class="flex items-baseline">
        <h1 class="h1" v-text="preference.plural" />
        <!-- <HelpLink link="https://help.retailright.app/#/stations" /> -->
      </div>
      <div>
        <button
          class="rr-button rr-button--primary inline"
          data-test="createPreference-button"
          @click.stop="createPreference()"
        >
          Create {{ preference.singular }}
        </button>
      </div>
    </div>

    <blank-state
      v-if="stationsForStore.length == 0"
      data-test="noStations-indicator"
    >
      <template v-slot:body>
        <div class="max-w-xl mx-auto text-center">
          <CubeTransparentIcon size="36" class="text-blue-600 mb-2 mx-auto" />
          <h2 class="h2">
            No Existing Stations For This Store
          </h2>
          <p>
            Checkout stations are used to link your card terminals to store
            locations. They allow you to take card as payment for transactions.
            <a
              href="https://help.retailright.app/#/checkout-stations"
              target="_blank"
              class="rr-link-blue"
              >Learn More</a
            >
            or <br />
            <a
              href="https://www.youtube.com/watch?v=18Vt8CpCn5A"
              target="_blank"
              class="rr-link-blue"
              >Watch Tutorial</a
            >
          </p>
          <div class="mt-8 flex justify-center">
            <button
              class="rr-button rr-button--lg rr-button--primary"
              @click.stop="createPreference()"
            >
              Create a Station
            </button>
          </div>
        </div>
      </template>
    </blank-state>

    <div class="mt-8" v-for="store in storesVisible" :key="store.id">
      <div v-if="stationsForStore(store.id).length > 0">
        <h2 class="h2">{{ store.name }}</h2>

        <table
          class="rr-table min-w-full table-auto shadow-lg rounded-md overflow-hidden mb-4"
        >
          <thead>
            <tr>
              <th class="rr-table__th">Name</th>
              <th class="rr-table__th">Terminal</th>
              <th class="rr-table__th">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white" data-test="stations-table-body">
            <tr
              class="rr-table__tr--hover"
              :class="{ 'rr-table__tr--hidden': preference.deleted_at }"
              :data-test="`stations-table-body-${preference.id}`"
              v-for="preference in stationsForStore(store.id)"
              :key="preference._id"
              @click="editPreference(preference)"
            >
              <td class="rr-table__td w-1/2">
                <div class="flex flex-col">
                  <div
                    class="text-sm leading-5 font-medium text-gray-900"
                    v-text="preference.name"
                  />
                </div>
              </td>
              <td class="rr-table__td">
                <div class="text-sm leading-5 font-medium text-gray-900">
                  {{ preference.terminal || "No Terminal Assigned" }}
                </div>
              </td>
              <td class="rr-table__td">
                <div
                  class="text-sm leading-5 text-gray-900"
                  v-text="statusText(preference.deleted_at)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ModalWall ref="PreferenceForm" data-test="station-form-modal">
      <template v-slot:header>
        <span class="block" v-text="modalTitle()" />
      </template>
      <template v-slot:body>
        <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
          <div class="rr-field">
            <label class="rr-field__label">
              Name

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.name.required"
              >
                Required
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="name-input"
              v-model="preferenceForm.name"
              @input="$v.preferenceForm.name.$touch()"
            />
          </div>
          <div class="rr-field" v-if="paymentPartner">
            <label class="rr-field__label">
              Terminal
            </label>
            <div class="flex">
              <select
                class="rr-field__input border-r-0 rounded-r-none"
                data-test="terminal-select"
                v-model="preferenceForm.terminal"
              >
                <option :value="null" disabled>{{ stationSelectText }}</option>
                <option
                  v-for="terminal in availableTerminals"
                  :value="terminal"
                  :key="terminal"
                  v-text="terminal"
                />
              </select>
            </div>
          </div>

          <div class="rr-field">
            <label class="rr-field__label">
              Store

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.store_id.required"
              >
                Required
              </span>
            </label>
            <select v-model="preferenceForm.store_id" class="rr-field__input">
              <option
                v-for="store of storesVisible"
                :value="store.id"
                :key="store.id"
                >{{ store.name }}</option
              >
            </select>
          </div>
          <div class="rr-field">
            <label class="rr-field__label">
              Cash Drawer Balance
              <span class="rr-field__label-optional">Optional</span>
            </label>
            <currency-input
              class="rr-field__input"
              placeholder="$100.00"
              v-model="preferenceForm.drawer_balance"
            />
          </div>
        </div>

        <div>
          <label class="rr-field__label">
            Options
          </label>
        </div>
        <div class="flex">
          <div class="rr-field__radio mr-4">
            <input
              type="checkbox"
              v-model="preferenceForm.deleted_at"
              :id="'inputHidden'"
              class="rr-field__radio-input"
              data-test="hidden-input"
              @click="preferenceForm.deleted_at = !preferenceForm.deleted_at"
            />
            <label
              :for="'inputHidden'"
              class="rr-field__radio-label items-baseline"
            >
              Hidden
            </label>
          </div>
        </div>
      </template>
      <template v-slot:footer>
        <div class="flex flex-row mt-12">
          <button
            class="rr-button rr-button--lg rr-button--primary-solid"
            :disabled="$v.$invalid || !preferenceForm.isDirty"
            data-test="submit-button"
            @click.stop="updatePreference()"
            v-text="modalButton()"
          />
          <button
            class="rr-button rr-button--lg ml-4"
            data-test="cancel-button"
            @click.stop="closeForm()"
          >
            Cancel
          </button>
        </div>
      </template>
    </ModalWall>

    <ModalWall
      ref="OccupyingStationModal"
      class="centered"
      data-test="occupyingStation-modal"
    >
      <template v-slot:header>
        <span class="block text-center"
          >The "{{ occupyingStation.name }}" station is using this
          terminal</span
        >
      </template>
      <template v-slot:body>
        <div class="max-w-lg">
          Would you like to assign the terminal to this station instead?
        </div>
      </template>
      <template v-slot:footer>
        <div class="flex">
          <button
            class="rr-button rr-button--lg rr-button--primary"
            data-test="replace-button"
            @click="replaceTerminal()"
          >
            Yes, replace
          </button>
          <button
            class="rr-button rr-button--lg ml-4"
            data-test="dontReplace-button"
            @click="closeOccupyingModal()"
          >
            No
          </button>
        </div>
      </template>
    </ModalWall>
  </div>
</template>

<script>
import { required } from "vuelidate/lib/validators";
import { mapGetters } from "vuex";

import ModalWall from "@/components/ModalWall";
import BlankState from "@/components/BlankState";
import { CubeTransparentIcon } from "@vue-hero-icons/outline";

import PreferencesMixin from "@/mixins/PreferencesMixin.js";

import Form from "@/classes/Form";

export default {
  name: "Checkout_Stations",

  mixins: [PreferencesMixin],

  data() {
    return {
      editMode: false,
      preference: {
        singular: this.$options.name.replace("_", " ").slice(0, -1),
        plural: this.$options.name.replace("_", " ")
      },
      preferenceForm: new Form({
        id: null,
        store_id: null,
        name: null,
        terminal: null,
        drawer_balance: null,
        deleted_at: null
      }),
      occupyingStation: null,
      fetchingTerminals: false,
      availableTerminals: []
    };
  },

  components: {
    ModalWall,
    BlankState,
    CubeTransparentIcon
  },

  computed: {
    ...mapGetters(["stations", "paymentPartner", "storesVisible"]),

    stationSelectText() {
      if (this.fetchingTerminals) {
        return "Fetching Terminals...";
      } else if (this.availableTerminals == []) {
        return "No Terminals Found";
      } else {
        return "Select a Terminal...";
      }
    }
  },

  mounted() {
    if (this.paymentPartner) this.getCardTerminals();

    if (this.$route.query.store_id) {
      this.preferenceForm.store_id = this.$route.query.store_id;
      this.createPreference();
    }
  },

  methods: {
    getCardTerminals() {
      this.fetchingTerminals = true;

      this.$store
        .dispatch("getCardTerminals")
        .then(terminals => {
          this.availableTerminals = terminals;
        })
        .finally(() => {
          this.fetchingTerminals = false;
        });
    },

    stationsForStore(storeId) {
      return this.stations.filter(station => station.store_id == storeId);
    },

    createPreference() {
      this.editMode = false;
      this.$refs.PreferenceForm.openModal();
    },

    updatePreference() {
      if (!this.preferenceForm.isDirty) {
        return this.closeForm();
      }

      const payload = {
        type: this.preference.plural.replace(" ", "_").toLowerCase(),
        update: this.preferenceForm.dirtyData
      };

      payload.update.id = this.preferenceForm.id;

      this.$store
        .dispatch("updatePreference", payload)
        .then(() => {
          this.closeForm();
        })
        .catch(({ checkout_station }) => {
          this.occupyingStation = checkout_station;
          this.$refs.OccupyingStationModal.openModal();
        });
    },

    replaceTerminal() {
      this.$store
        .dispatch("updateMultiplePreferences", [
          {
            type: "checkout_stations",
            update: {
              id: this.occupyingStation.id,
              terminal: null
            }
          },
          {
            type: "checkout_stations",
            update: Object.assign(
              {
                id: this.preferenceForm.id
              },
              this.preferenceForm.dirtyData
            )
          }
        ])
        .then(() => {
          this.closeOccupyingModal();
          this.closeForm();
        });
    },

    closeOccupyingModal() {
      this.$refs.OccupyingStationModal.closeModal();
    }
  },

  validations() {
    return {
      preferenceForm: {
        name: { required },
        store_id: { required }
      }
    };
  }
};
</script>

<style lang="scss"></style>
