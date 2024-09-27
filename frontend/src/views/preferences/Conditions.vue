<template>
  <div>
    <div class="flex flex-row justify-between">
      <div class="flex items-baseline">
        <h1 class="h1" v-text="preference.plural" />
        <HelpLink link="https://help.retailright.app/#/conditions" />
      </div>
      <div>
        <div class="rr-field__radio mr-4 inline-block">
          <input
            type="checkbox"
            v-model="disableConditions"
            id="disableConditions"
            data-test="disableConditions-input"
            class="rr-field__radio-input"
            @click="disableConditions = !disableConditions"
          />
          <label
            for="disableConditions"
            class="rr-field__radio-label items-baseline"
          >
            Disable Conditions
          </label>
        </div>
        <button
          class="rr-button rr-button--primary inline"
          data-test="createDefaults-button"
          v-if="conditions.length == 0"
          @click.stop="createDefaultPreferences('conditions')"
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
      v-if="!completed.conditions"
      data-test="noConditions-indicator"
    >
      <template v-slot:body>
        <div class="max-w-xl mx-auto text-center">
          <CubeTransparentIcon size="36" class="text-blue-600 mb-2 mx-auto" />
          <h2 class="h2">
            Conditions Needed
          </h2>
          <p>
            Conditions are used to categorize and (optionally) apply an
            additional discount to classified items.
            <a
              href="https://help.retailright.app/#/conditions"
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
              Create a Condition
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
      <tbody class="bg-white" data-test="conditions-table-body">
        <tr
          class="rr-table__tr--hover"
          :class="{ 'rr-table__tr--hidden': preference.deleted_at }"
          :data-test="`conditions-table-body-${preference.id}`"
          v-for="preference in conditions"
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

    <ModalWall ref="PreferenceForm" data-test="condition-form-modal">
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
import HelpLink from "@/components/HelpLink";
import BlankState from "@/components/BlankState";
import { CubeTransparentIcon } from "@vue-hero-icons/outline";

import PreferencesMixin from "@/mixins/PreferencesMixin.js";

import Form from "@/classes/Form";

export default {
  name: "Conditions",

  components: {
    ModalWall,
    HelpLink,
    BlankState,
    CubeTransparentIcon
  },

  mixins: [PreferencesMixin],

  data() {
    return {
      editMode: false,
      preferenceForm: new Form({
        id: null,
        discount: 0,
        name: "",
        deleted_at: null
      }),
      examples: ["New", "Used", "Damaged"],
      suggestion: "",
      disableConditions: false
    };
  },

  computed: {
    ...mapGetters(["conditions", "conditions_disabled"])
  },

  watch: {
    disableConditions(bool) {
      if (bool != this.conditions_disabled) {
        this.$store.dispatch("updatePreferences", {
          conditions_disabled: bool
        });
      }
    }
  },

  mounted() {
    this.disableConditions = this.conditions_disabled;
  }
};
</script>
