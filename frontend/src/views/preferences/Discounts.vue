<template>
  <div>
    <div class="flex flex-row justify-between">
      <div class="flex items-baseline">
        <h1 class="h1" v-text="preference.plural" />
        <HelpLink link="https://help.retailright.app/#/discounts" />
      </div>
      <div>
        <button
          class="rr-button rr-button--primary inline"
          data-test="createDefaults-button"
          v-if="discounts.length == 0"
          @click.stop="createDefaultPreferences('discounts')"
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

    <blank-state v-if="discounts.length == 0" data-test="noDiscounts-indicator">
      <template v-slot:body>
        <div class="max-w-xl mx-auto text-center">
          <CubeTransparentIcon size="36" class="text-blue-600 mb-2 mx-auto" />
          <h2 class="h2">
            Discounts Needed
          </h2>
          <p>
            Discounts are available during Point of Sale (POS) and can be
            applied to items individually.
            <a
              href="https://help.retailright.app/#/discounts"
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
              Create a Discount
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
      <tbody class="bg-white" data-test="discounts-table-body">
        <tr
          class="rr-table__tr--hover"
          :class="{ 'rr-table__tr--hidden': preference.deleted_at }"
          :data-test="`discounts-table-body-${preference.id}`"
          v-for="preference in discounts"
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

    <ModalWall ref="PreferenceForm" data-test="discount-form-modal">
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

import ModalWall from "@/components/ModalWall";
import BlankState from "@/components/BlankState";
import HelpLink from "@/components/HelpLink";
import { CubeTransparentIcon } from "@vue-hero-icons/outline";

import PreferencesMixin from "@/mixins/PreferencesMixin.js";

import Form from "@/classes/Form";

export default {
  name: "Discounts",

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
        discount: 0,
        name: "",
        deleted_at: null
      }),
      examples: ["Red Tag", "Employee", "Donation"],
      suggestion: ""
    };
  },

  computed: {
    ...mapGetters(["discounts"])
  }
};
</script>
