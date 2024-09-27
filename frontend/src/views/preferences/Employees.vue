<template>
  <div>
    <div class="flex flex-row justify-between">
      <div class="flex items-baseline">
        <h1 class="h1" v-text="preference.plural" />
        <HelpLink link="https://help.retailright.app/#/employees" />
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

    <blank-state v-if="employees.length == 0" data-test="noEmployees-indicator">
      <template v-slot:body>
        <div class="max-w-xl mx-auto text-center">
          <CubeTransparentIcon size="36" class="text-blue-600 mb-2 mx-auto" />
          <h2 class="h2">
            No Existing Employees
          </h2>
          <p>
            Employees are users of the system with reduced access privileges. To
            ensure consistent logging of events (item sales, quantity changes),
            employees should be given their own login that is not shared.
            <a
              href="https://help.retailright.app/#/employees"
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
              Create an Employee
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
          <th class="rr-table__th">Role</th>
          <th class="rr-table__th">Status</th>
        </tr>
      </thead>
      <tbody class="bg-white" data-test="employees-table-body">
        <tr
          class="rr-table__tr--hover"
          :class="{ 'rr-table__tr--hidden': preference.deleted_at }"
          :data-test="`employees-table-body-${preference.id}`"
          v-for="preference in employees"
          :key="preference.id"
          @click="editPreference(preference)"
        >
          <td class="rr-table__td">
            <div class="flex flex-col">
              <div class="text-sm leading-5 font-medium text-gray-900">
                {{ preference.first_name }} {{ preference.last_name }}
              </div>
              <div class="text-xs leading-5 text-gray-500">
                {{ preference.username }}
                –
                {{ preference.email }}
              </div>
            </div>
          </td>
          <td class="rr-table__td">
            <div class="text-sm leading-5 text-gray-900">
              {{ preference.user_role.name | capitalize }}
            </div>
          </td>
          <td class="rr-table__td">
            <div
              class="text-sm leading-5 text-gray-900"
              v-text="statusText(preference.email_verified_at)"
            />
          </td>
        </tr>
      </tbody>
    </table>

    <ModalWall ref="PreferenceForm" data-test="employee-form-modal">
      <template v-slot:header>
        <span class="block" v-text="modalTitle()" />
      </template>
      <template v-slot:body>
        <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
          <div class="rr-field">
            <label class="rr-field__label">
              First Name

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.first_name.required"
              >
                Required
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="firstName-input"
              v-model="preferenceForm.first_name"
              @input="suggestUsername"
            />
          </div>
          <div class="rr-field">
            <label class="rr-field__label">
              Last Name
            </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="lastName-input"
              v-model="preferenceForm.last_name"
              @input="suggestUsername"
            />
          </div>
        </div>
        <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
          <div class="rr-field">
            <label class="rr-field__label">
              Username

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.username.required"
              >
                Required
              </span>
              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.username.alphaNum"
              >
                No spaces A-Z 0-9 characters only
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="username-input"
              :disabled="editMode"
              v-model="preferenceForm.username"
              @change="usernameToLower()"
            />
          </div>
          <div class="rr-field">
            <label class="rr-field__label">
              Email

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.email.required && !editMode"
              >
                Required
              </span>
              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.email.email && !editMode"
              >
                Invalid email format
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="email-input"
              v-model="preferenceForm.email"
            />
          </div>
        </div>
        <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
          <div>
            <div>
              <label class="rr-field__label"> Security </label>
            </div>
            <div class="rr-field__radio mb-4 inline-block" v-show="editMode">
              <input
                type="checkbox"
                v-model="changePassword"
                :id="'changePassword'"
                class="rr-field__radio-input"
                @click="changePassword = !changePassword"
              />
              <label
                :for="'changePassword'"
                class="rr-field__radio-label items-baseline"
              >
                Change Password
              </label>
            </div>
          </div>
        </div>
        <div
          class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8"
          v-if="changePassword"
        >
          <div class="rr-field">
            <label class="rr-field__label">
              Password

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.password.required"
              >
                Required
              </span>
              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.password.minLength"
              >
                Min. 8 characters
              </span>
            </label>
            <input
              class="rr-field__input"
              type="password"
              data-test="password-input"
              v-model="preferenceForm.password"
            />
          </div>
          <div class="rr-field">
            <label class="rr-field__label">
              Confirm Password

              <span
                class="rr-field__label-required"
                v-if="!$v.preferenceForm.passwordConfirm.sameAsPassword"
              >
                Doesn't match
              </span>
            </label>
            <input
              class="rr-field__input"
              type="password"
              data-test="passwordConfirm-input"
              v-model="preferenceForm.passwordConfirm"
            />
          </div>
        </div>

        <div>
          <label class="rr-field__label"> Access </label>

          <div class="grid grid-rows-2">
            <div class="row-span-1">
              <div class="rr-field__radio inline-block">
                <input
                  type="checkbox"
                  id="managerCheck"
                  class="rr-field__radio-input"
                  :checked="isManager"
                  @click="
                    preferenceForm.role = isManager ? 'employee' : 'manager'
                  "
                />
                <label for="managerCheck" class="rr-field__radio-label">
                  Is Manager?
                </label>
              </div>

              <span class="block mt-2 text-red-700" v-if="isManager">
                Managers have all the same permissions as the owner.
              </span>
            </div>

            <div class="row-span-1" v-if="!isManager">
              <div
                class="rr-field__radio mr-4 mb-4 inline-block"
                v-for="(value, index) in tabs"
                :key="index"
              >
                <input
                  type="checkbox"
                  v-model="tempPermissions"
                  :value="index"
                  :id="`input${index}`"
                  class="rr-field__radio-input"
                />
                <label
                  :for="`input${index}`"
                  class="rr-field__radio-label items-baseline"
                >
                  {{ value.name }}
                </label>
              </div>
            </div>
          </div>
        </div>

        <div v-if="editMode && !editUserAndCurrentUserAreBothManagers">
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
                Disable
              </label>
            </div>
          </div>
        </div>
      </template>
      <template v-slot:footer>
        <div class="flex flex-row mt-8">
          <button
            class="rr-button rr-button--lg rr-button--primary-solid"
            :disabled="$v.$invalid || !preferenceForm.isDirty"
            data-test="submit-button"
            @click.stop="submitForm()"
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
import {
  required,
  email,
  alphaNum,
  sameAs,
  minLength
} from "vuelidate/lib/validators";
import { CubeTransparentIcon } from "@vue-hero-icons/outline";
import { generateUsername } from "@/helpers";
import { debounce } from "lodash";

import BlankState from "@/components/BlankState";
import HelpLink from "@/components/HelpLink";
import ModalWall from "@/components/ModalWall";

import PreferencesMixin from "@/mixins/PreferencesMixin";

import Form from "@/classes/Form";

export default {
  name: "Employees",

  components: {
    ModalWall,
    BlankState,
    HelpLink,
    CubeTransparentIcon
  },

  mixins: [PreferencesMixin],

  data() {
    return {
      changePassword: false,
      editMode: false,
      tempPermissions: [],
      preferenceForm: new Form({
        id: null,
        permissions: [],
        first_name: "",
        last_name: "",
        username: "",
        email: "",
        password: "",
        passwordConfirm: "",
        organization_id: this.$store.getters.organization_id,
        role: "employee",
        deleted_at: null
      }),
      tabs: {
        items: {
          name: "Items",
          permissions: null
        },
        pos: {
          name: "Pos",
          permissions: null
        },
        reports: {
          name: "Reports",
          permissions: null
        },
        import: {
          name: "Import",
          permissions: ["import"]
        }
      }
    };
  },

  mounted() {
    this.tabs.items.permissions = this.all_permissions.filter(perm =>
      perm.includes("items")
    );
    this.tabs.pos.permissions = this.all_permissions.filter(perm =>
      perm.includes("pos")
    );
    this.tabs.reports.permissions = this.all_permissions.filter(perm =>
      perm.includes("reports")
    );
  },

  watch: {
    tempPermissions(perms) {
      var userPerms = [];

      for (let perm of perms) {
        userPerms = userPerms.concat(this.tabs[perm].permissions);
      }

      this.preferenceForm.permissions = userPerms;
    },
    isManager(bool) {
      if (bool) {
        return (this.tempPermissions = []);
      }

      this.setDefaultPerms();
    }
  },

  computed: {
    ...mapGetters([
      "discounts",
      "employees",
      "all_permissions",
      "userRole",
      "currentUser"
    ]),

    isManager() {
      return this.preferenceForm.role == "manager";
    },

    editUserAndCurrentUserAreBothManagers() {
      return this.isManager && this.userRole.name == "manager";
    }
  },

  methods: {
    statusText(hidden) {
      return hidden ? "Active" : "Unverified";
    },

    createPreference() {
      this.setDefaultPerms();
      this.editMode = false;
      this.changePassword = true;
      this.$refs.PreferenceForm.openModal();
    },

    async editPreference(preference) {
      this.editMode = true;

      let preferenceCopy = { ...preference, role: preference.user_role.name };
      this.tempPermissions = await this.getTempPermissions(
        preferenceCopy.user_permissions
      );

      this.preferenceForm.update(preferenceCopy);

      if (this.editUserAndCurrentUserAreBothManagers) {
        return this.$toasted.show(
          "Managers are not allowed to edit other managers.",
          { type: "info" }
        );
      }

      this.$refs.PreferenceForm.openModal();
    },

    closeForm() {
      this.editMode = false;
      this.changePassword = false;
      this.$refs.PreferenceForm.closeModal();
      this.preferenceForm.initialState();
    },

    submitForm() {
      let request = null;

      if (this.editMode) {
        request = this.$store.dispatch("updateUser", {
          userId: this.preferenceForm.id,
          update: this.preferenceForm.dirtyData
        });
      } else {
        request = this.$store.dispatch("createUser", {
          user: this.preferenceForm.data
        });
      }

      request.then(() => {
        this.closeForm();
      });
    },

    usernameToLower() {
      this.preferenceForm.username = this.preferenceForm.username.toLowerCase();
    },

    getTempPermissions(userPerms) {
      var permissions = [];

      if (userPerms.find(p => p.includes("items"))) {
        permissions.push("items");
      }
      if (userPerms.find(p => p.includes("pos"))) {
        permissions.push("pos");
      }
      if (userPerms.find(p => p.includes("reports"))) {
        permissions.push("reports");
      }
      if (userPerms.find(p => p.includes("import"))) {
        permissions.push("import");
      }

      return permissions;
    },

    setDefaultPerms() {
      this.tempPermissions = ["items", "pos", "reports", "import"];
    },

    suggestUsername: debounce(function() {
      this.preferenceForm.username = generateUsername(
        this.preferenceForm.first_name,
        this.preferenceForm.last_name
      );
    }, 250)
  },

  validations() {
    let preferenceFormDynamicRules = {};

    preferenceFormDynamicRules.email = this.editMode ? {} : { required, email };

    preferenceFormDynamicRules.password = this.changePassword
      ? { required, minLength: minLength(8) }
      : {};
    preferenceFormDynamicRules.passwordConfirm = this.changePassword
      ? { sameAsPassword: sameAs("password") }
      : {};

    return {
      preferenceForm: {
        ...preferenceFormDynamicRules,
        first_name: { required },
        username: { required, alphaNum },
        organization_id: { required }
      }
    };
  }
};
</script>
