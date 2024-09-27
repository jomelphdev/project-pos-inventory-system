<template>
  <div>
    <div class="flex flex-row justify-between">
      <h1 class="h1" v-text="preference.plural" />
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
      v-if="consignors.length == 0"
      data-test="noConsignors-indicator"
    >
      <template v-slot:body>
        <div class="max-w-xl mx-auto text-center">
          <CubeTransparentIcon size="36" class="text-blue-600 mb-2 mx-auto" />
          <h2 class="h2">
            No Existing Consignors
          </h2>
          <p>
            Consignors are used to track and manage any consignment items and
            sales that you may need to handle in your stores.
            <a
              href="https://help.retailright.app/#/consignors"
              target="_blank"
              class="rr-link-blue"
              >Learn More</a
            >
            or
            <a
              href="https://www.youtube.com/watch?v=9sCWJ3on0Ls"
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
              Create a Consignor
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
          <th class="rr-table__th">Consignment Fee Percentage</th>
        </tr>
      </thead>
      <tbody class="bg-white" data-test="consignors-table-body">
        <tr
          class="rr-table__tr--hover"
          :class="{ 'rr-table__tr--hidden': preference.deleted_at }"
          :data-test="`consignors-table-body-${preference.id}`"
          v-for="preference in consignors"
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
              {{ preference.consignment_fee_percentage | percent(2) }}
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <ModalWall ref="PreferenceForm" data-test="consignor-form-modal">
      <template v-slot:header>
        <span class="block" v-text="modalTitle()" />
      </template>
      <template v-slot:body>
        <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
          <div class="rr-field">
            <label class="rr-field__label">
              Business Name or Name
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
              Consignment Fee Percentage
            </label>
            <div class="flex">
              <input
                class="rr-field__input border-r-0 rounded-r-none"
                type="number"
                data-test="consignmentFeePercentage-input"
                v-model="preferenceForm.consignment_fee_percentage"
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

          <div class="flex">
            <div class="rr-field__radio mr-4">
              <input
                type="checkbox"
                v-model="preferenceForm.deleted_at"
                :id="'inputHidden'"
                data-test="hidden-input"
                class="rr-field__radio-input"
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
import { required, decimal } from "vuelidate/lib/validators";

import ModalWall from "@/components/ModalWall";
import BlankState from "@/components/BlankState";
import { CubeTransparentIcon } from "@vue-hero-icons/outline";

import PreferencesMixin from "@/mixins/PreferencesMixin.js";

import Form from "@/classes/Form";

export default {
  name: "Consignors",

  mixins: [PreferencesMixin],

  components: { ModalWall, BlankState, CubeTransparentIcon },

  data() {
    return {
      editMode: false,
      preferenceForm: new Form({
        id: null,
        name: "",
        consignment_fee_percentage: null,
        deleted_at: null
      })
    };
  },

  computed: {
    ...mapGetters(["consignors"])
  },

  methods: {
    editPreference(preference) {
      this.editMode = true;
      this.$refs.PreferenceForm.openModal();

      let preferenceCopy = { ...preference };
      preferenceCopy.consignment_fee_percentage *= 100;
      this.preferenceForm.update(preferenceCopy);
    }
  },

  validations() {
    return {
      preferenceForm: {
        name: { required },
        consignment_fee_percentage: { decimal }
      }
    };
  }
};
</script>
