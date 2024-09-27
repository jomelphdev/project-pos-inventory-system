<template>
  <div>
    <div class="flex flex-row justify-between">
      <div class="flex items-baseline">
        <h1 class="h1" v-text="preference.plural" />
        <HelpLink link="https://help.retailright.app/#/stores" />
      </div>
      <div>
        <button
          class="rr-button rr-button--primary inline"
          data-test="createStore-button"
          @click.stop="createPreference()"
        >
          Create {{ preference.singular }}
        </button>
      </div>
    </div>

    <blank-state v-if="!completed.stores" data-test="noStores-indicator">
      <template v-slot:body>
        <div class="max-w-xl mx-auto text-center">
          <CubeTransparentIcon size="36" class="text-blue-600 mb-2 mx-auto" />
          <h2 class="h2">
            Stores Needed
          </h2>
          <p>
            Stores are physical locations of your business. Item quantities and
            POS sales are connected to stores.
            <a
              href="https://help.retailright.app/#/stores"
              target="_blank"
              class="rr-link-blue"
              >Learn More</a
            >
          </p>
          <div class="mt-8 flex justify-center">
            <button
              class="rr-button rr-button--lg rr-button--primary "
              @click.stop="createPreference()"
            >
              Create Your First Store
            </button>
          </div>
        </div>
      </template>
    </blank-state>

    <table
      class="rr-table min-w-full table-auto shadow-lg rounded-md overflow-hidden mb-4"
      v-else
    >
      <thead>
        <tr>
          <th class="rr-table__th">Name</th>
          <th class="rr-table__th">Address</th>
          <th class="rr-table__th">Tax Rate</th>
          <th class="rr-table__th">Status</th>
        </tr>
      </thead>
      <tbody class="bg-white" data-test="stores-table-body">
        <tr
          class="rr-table__tr--hover relative"
          :class="{ 'rr-table__tr--hidden': preference.deleted_at }"
          v-for="preference in stores"
          :key="preference.id"
          :data-test="`stores-table-body-${preference.id}`"
          @click="editPreference(preference)"
        >
          <td class="rr-table__td w-1/3">
            <div class="flex flex-col">
              <div
                class="text-sm leading-5 font-medium text-gray-900"
                v-text="preference.name"
              />
              <div class="text-xs leading-5 text-gray-500">
                {{ preference.phone }}
              </div>
            </div>
          </td>
          <td class="rr-table__td">
            <div class="text-sm leading-5 font-medium text-gray-900">
              {{ preference.address }}
            </div>
            <div class="text-xs leading-5 text-gray-500">
              {{ preference.city }},
              {{ preference.state.name }}
              {{ preference.zip }}
            </div>
          </td>
          <td class="rr-table__td">
            <div
              class="text-sm leading-5 font-medium text-gray-900"
              data-test="store-body-taxRate"
            >
              {{ preference.tax_rate | percent(3) }}
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

    <ModalWall ref="PreferenceForm" data-test="store-form-modal">
      <template v-slot:header>
        <span
          class="block"
          data-test="stores-form-modal-header"
          v-text="modalTitle()"
        />
      </template>
      <template v-slot:body>
        <!-- Name / Tax Rate -->
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
          <div class="rr-field">
            <label class="rr-field__label">
              Tax Rate

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.tax_rate.required"
              >
                Required
              </span>
            </label>
            <div class="flex">
              <input
                class="rr-field__input border-r-0 rounded-r-none"
                type="number"
                step="0.001"
                data-test="taxRate-input"
                v-model="taxRate"
                @input="$v.preferenceForm.tax_rate.$touch()"
              />
              <span class="rr-field__input-label border-l-0 rounded-r-md">
                %
              </span>
            </div>
          </div>
        </div>
        <!-- Address -->
        <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
          <div class="rr-field">
            <label class="rr-field__label">
              Address

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.address.required"
              >
                Required
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="address-input"
              v-model="preferenceForm.address"
              @input="$v.preferenceForm.address.$touch()"
            />
          </div>
          <div class="rr-field">
            <label class="rr-field__label">
              Phone

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.phone.required"
              >
                Required
              </span>
              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.phone.minLength"
              >
                Invalid Phone Number
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="phone-input"
              v-model="preferenceForm.phone"
              v-mask="'###-###-####'"
              placeholder="555-555-5555"
              @input="$v.preferenceForm.phone.$touch()"
            />
          </div>
        </div>
        <!-- City / State / Zip -->
        <div class="grid md:grid-cols-3 grid-cols-1 md:gap-x-8 md:gap-y-8">
          <div class="rr-field">
            <label class="rr-field__label">
              City

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.city.required"
              >
                Required
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="city-input"
              v-model="preferenceForm.city"
              @input="$v.preferenceForm.city.$touch()"
            />
          </div>
          <div class="rr-field">
            <label class="rr-field__label">
              State

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.state_id.required"
              >
                Required
              </span>
            </label>
            <select
              class="rr-field__input"
              type="text"
              data-test="state-select"
              v-model="preferenceForm.state_id"
            >
              <option v-for="state of states" :key="state.id" :value="state.id">
                {{ state.name }} | {{ state.abbreviation }}
              </option>
            </select>
          </div>
          <div class="rr-field">
            <label class="rr-field__label">
              Zip

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.zip.required"
              >
                Required
              </span>
            </label>
            <input
              class="rr-field__input"
              type="number"
              data-test="zip-input"
              v-model="preferenceForm.zip"
              @input="$v.preferenceForm.zip.$touch()"
            />
          </div>
        </div>
        <!-- Receipt / Options -->
        <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
          <div>
            <div class="rr-field">
              <label class="rr-field__label">
                Receipt Title (Store Name)

                <span
                  class="rr-field__label-required"
                  v-if="!$v.preferenceForm.receipt_option.name.required"
                >
                  Required
                </span>
              </label>
              <input
                class="rr-field__input"
                type="text"
                data-test="receiptTitle-input"
                v-model="preferenceForm.receipt_option.name"
                @input="$v.preferenceForm.receipt_option.name.$touch()"
              />
            </div>
            <div v-if="editMode">
              <label class="rr-field__label"> Options </label>

              <div class="flex mb-6">
                <div class="rr-field__radio mr-4">
                  <input
                    type="checkbox"
                    v-model="preferenceForm.deleted_at"
                    :id="'inputHidden'"
                    class="rr-field__radio-input"
                    data-test="hidden-input"
                    @click="
                      preferenceForm.deleted_at = !preferenceForm.deleted_at
                    "
                  />
                  <label
                    :for="'inputHidden'"
                    class="rr-field__radio-label items-baseline"
                  >
                    Hidden
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="rr-field mb-0">
            <label class="rr-field__label">
              Receipt Footer
            </label>
            <textarea
              rows="6"
              class="rr-field__textarea"
              data-test="receiptFooter-input"
              v-model="preferenceForm.receipt_option.footer"
            ></textarea>
          </div>
        </div>
      </template>
      <template v-slot:footer>
        <div class="flex flex-row mt-12">
          <button
            class="rr-button rr-button--lg rr-button--primary-solid"
            data-test="submit-button"
            :disabled="$v.$invalid || !preferenceForm.isDirty"
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
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import { required, decimal, minLength } from "vuelidate/lib/validators";
import { CubeTransparentIcon } from "@vue-hero-icons/outline";

import BlankState from "@/components/BlankState";
import HelpLink from "@/components/HelpLink";
import ModalWall from "@/components/ModalWall";

import PreferencesMixin from "@/mixins/PreferencesMixin.js";

import Form from "@/classes/Form";

export default {
  name: "Stores",

  mixins: [PreferencesMixin],

  components: {
    ModalWall,
    BlankState,
    HelpLink,
    CubeTransparentIcon
  },

  data() {
    return {
      editMode: false,
      preferenceForm: new Form({
        id: null,
        receipt_option: {
          name: "",
          footer: "Thanks For Shopping!\nAll Sales Final."
        },
        deleted_at: null,
        name: "",
        tax_rate: null,
        address: "",
        city: "",
        state: "",
        state_id: null,
        zip: "",
        phone: ""
      }),
      disableConditions: false,
      taxRate: null,
      hover: null
    };
  },

  mounted() {
    this.$store.dispatch("setPreferenceStore", null);
  },

  computed: {
    ...mapGetters(["stores", "states", "preference_id"])
  },

  watch: {
    taxRate(rate) {
      this.preferenceForm.tax_rate = (rate / 100).toFixed(5);
    }
  },

  methods: {
    editPreference(preference) {
      this.editMode = true;
      this.$refs.PreferenceForm.openModal();

      let preferenceCopy = { ...preference };
      preferenceCopy.state_id = preferenceCopy.state.id;

      this.preferenceForm.update(preferenceCopy);
      this.taxRate = (preferenceCopy.tax_rate * 100).toFixed(3);
    },

    editPreferenceOptions(store) {
      this.$store.dispatch("setPreferenceStore", store.id);
      this.$router.push({
        name: "preferences.checkoutStations",
        query: { store_id: store.id }
      });
    }
  },

  validations() {
    return {
      preferenceForm: {
        receipt_option: {
          name: { required }
        },
        name: { required },
        tax_rate: { required, decimal },
        address: { required },
        city: { required },
        state_id: { required },
        zip: { required },
        phone: { required, minLength: minLength(12) }
      }
    };
  }
};
</script>
