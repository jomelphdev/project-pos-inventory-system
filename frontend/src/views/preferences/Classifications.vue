<template>
  <div>
    <div class="flex flex-row justify-between">
      <div class="flex items-baseline">
        <h1 class="h1" v-text="preference.plural" />
        <HelpLink link="https://help.retailright.app/#/classifications" />
      </div>
      <div>
        <div class="rr-field__radio mr-4 inline-block">
          <input
            type="checkbox"
            v-model="disableClassifications"
            id="disableClassifications"
            data-test="disableClassifications-input"
            class="rr-field__radio-input"
            @click="disableClassifications = !disableClassifications"
          />
          <label
            for="disableClassifications"
            class="rr-field__radio-label items-baseline"
          >
            Disable Classifications
          </label>
        </div>
        <button
          class="rr-button rr-button--primary inline"
          data-test="createDefaults-button"
          v-if="classifications.length == 0"
          @click.stop="createDefaultPreferences('classifications')"
        >
          Create {{ preference.singular }} Defaults
        </button>
        <button
          class="rr-button rr-button--primary inline ml-2"
          data-test="createPreference-button"
          @click.stop="createPreference()"
        >
          Create {{ preference.singular }}
        </button>
      </div>
    </div>

    <blank-state
      v-if="!completed.classifications"
      data-test="noClassifications-indicator"
    >
      <template v-slot:body>
        <div class="max-w-xl mx-auto text-center">
          <CubeTransparentIcon size="36" class="text-blue-600 mb-2 mx-auto" />
          <h2 class="h2">
            Classifications Needed
          </h2>
          <p>
            Classifications are used to categorize items, apply an optional
            discount, and calculate price reliably & consistently.
            <a
              href="https://help.retailright.app/#/classifications"
              target="_blank"
              class="rr-link-blue"
              >Learn More</a
            >
          </p>
          <div class="mt-8 flex justify-center">
            <button
              class="rr-button rr-button--lg rr-button--primary"
              @click.stop="createPreference()"
            >
              Create a Classification
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
          <th class="rr-table__th">Discount</th>
          <th class="rr-table__th">Status</th>
        </tr>
      </thead>
      <tbody class="bg-white" data-test="classifications-table-body">
        <tr
          class="rr-table__tr--hover"
          :class="{ 'rr-table__tr--hidden': preference.deleted_at }"
          :data-test="`classifications-table-body-${preference.id}`"
          v-for="preference in classifications"
          :key="preference.id"
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
              {{ preference.discount | percent(2) }}
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

    <ModalWall ref="PreferenceForm" data-test="classification-form-modal">
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
              :placeholder="suggestion"
            />
          </div>
          <div class="rr-field">
            <label class="rr-field__label">
              Discount

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.discount.required"
              >
                Required
              </span>
            </label>
            <div class="flex">
              <input
                class="rr-field__input border-r-0 rounded-r-none"
                type="number"
                data-test="discount-input"
                v-model="preferenceForm.discount"
                @input="$v.preferenceForm.discount.$touch()"
              />
              <span class="rr-field__input-label border-l-0 rounded-r-md">
                %
              </span>
            </div>
          </div>
        </div>
        <div v-if="editMode">
          <label class="rr-field__label">
            Options
          </label>

          <div class="rr-field__radio">
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

        <div class="mt-4">
          <label class="rr-field__label">
            Store Options
          </label>

          <data-table>
            <data-table-header>
              <data-table-header-cell>Store</data-table-header-cell>
              <data-table-header-cell>Is EBT</data-table-header-cell>
              <data-table-header-cell>Tax Free</data-table-header-cell>
            </data-table-header>
            <data-table-body>
              <data-table-row
                :data-test="`classifications-options-table-body`"
                v-for="store in storesVisible"
                :key="store.id"
              >
                <data-table-cell>{{ store.name }}</data-table-cell>
                <data-table-cell>
                  <input
                    type="checkbox"
                    class="rr-field__radio input"
                    @click="toggleOption(store.id, 'is_ebt')"
                    :checked="isEbt(store.id)"
                  />
                </data-table-cell>
                <data-table-cell>
                  <input
                    type="checkbox"
                    class="rr-field__radio input"
                    @click="toggleOption(store.id, 'is_taxed', false)"
                    :checked="!isTaxed(store.id)"
                  />
                </data-table-cell>
              </data-table-row>
            </data-table-body>
          </data-table>
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
  </div>
</template>

<script>
import { mapGetters } from "vuex";

import HelpLink from "@/components/HelpLink";
import BlankState from "@/components/BlankState";
import { CubeTransparentIcon } from "@vue-hero-icons/outline";
import ModalWall from "@/components/ModalWall";
import DataTable from "../../components/table/DataTable.vue";
import DataTableHeader from "../../components/table/DataTableHeader.vue";
import DataTableHeaderCell from "../../components/table/DataTableHeaderCell.vue";
import DataTableBody from "../../components/table/DataTableBody.vue";
import DataTableCell from "../../components/table/DataTableCell.vue";
import DataTableRow from "../../components/table/DataTableRow.vue";

import PreferencesMixin from "@/mixins/PreferencesMixin.js";

import Form from "@/classes/Form";

export default {
  name: "Classifications",

  components: {
    ModalWall,
    HelpLink,
    BlankState,
    CubeTransparentIcon,
    DataTable,
    DataTableHeader,
    DataTableHeaderCell,
    DataTableBody,
    DataTableCell,
    DataTableRow
  },

  mixins: [PreferencesMixin],

  computed: {
    ...mapGetters([
      "classifications_disabled",
      "classifications",
      "storesVisible"
    ])
  },

  data() {
    return {
      editMode: false,
      preference: {
        singular: this.$options.name.slice(0, -1),
        plural: this.$options.name
      },
      preferenceForm: new Form({
        id: null,
        discount: 0,
        name: null,
        preference_options: [],
        apply_to_all_stores: false,
        deleted_at: null
      }),
      examples: [
        "Food",
        "Tools",
        "Electronics",
        "Toys",
        "Pet",
        "Health and Beauty",
        "Clothing"
      ],
      suggestion: "",
      disableClassifications: false
    };
  },

  watch: {
    disableClassifications(bool) {
      if (bool != this.classifications_disabled) {
        this.$store.dispatch("updatePreferences", {
          classifications_disabled: bool
        });
      }
    }
  },

  mounted() {
    this.disableClassifications = this.classifications_disabled;

    if (window.Cypress) {
      window.Classifications = {
        preferenceForm: this.preferenceForm
      };
    }
  },

  methods: {
    statusText(hidden) {
      return hidden ? "Hidden" : "Active";
    },

    toggleOption(storeId, key, fallBackValue = true) {
      let option = this.preferenceForm.preference_options.find(
        o => o.store_id == storeId && o.key == key
      );

      if (option) {
        option.value = !option.value;
      } else {
        this.preferenceForm.preference_options.push({
          store_id: storeId,
          key,
          value: fallBackValue
        });
      }
    },

    createPreference() {
      this.editMode = false;
      this.$refs.PreferenceForm.openModal();
      this.suggestion = this.examples[
        Math.floor(Math.random() * this.examples.length)
      ];
    },

    isTaxed(storeId) {
      const option = this.preferenceForm.preference_options.find(
        option => option.store_id == storeId && option.key == "is_taxed"
      );

      if (!option) return true;
      return option.value == true;
    },

    isEbt(storeId) {
      const option = this.preferenceForm.preference_options.find(
        option => option.store_id == storeId && option.key == "is_ebt"
      );

      if (!option) return false;
      return option.value == true;
    }
  }
};
</script>
