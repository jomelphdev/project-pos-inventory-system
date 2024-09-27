<template>
  <div>
    <h1 class="h1">Profile</h1>
    <div class="mt-6">
      <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
        <div class="rr-field">
          <label class="rr-field__label">
            First Name

            <span
              class="rr-field__label-required"
              v-if="!$v.profileForm.first_name.required"
            >
              Required
            </span>
          </label>
          <input
            class="rr-field__input"
            type="text"
            v-model="profileForm.first_name"
            @input="$v.profileForm.first_name.$touch()"
          />
        </div>
        <div class="rr-field">
          <label class="rr-field__label">
            Last Name
          </label>
          <input
            class="rr-field__input"
            type="text"
            v-model="profileForm.last_name"
            @input="$v.profileForm.last_name.$touch()"
          />
        </div>
      </div>
      <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
        <div class="rr-field">
          <label class="rr-field__label">
            Username
          </label>
          <input
            class="rr-field__input"
            type="text"
            :disabled="true"
            v-model="profileForm.username"
          />
        </div>
        <div class="rr-field">
          <label class="rr-field__label">
            Email

            <span
              class="rr-field__label-required"
              v-if="!$v.profileForm.email.required"
            >
              Required
            </span>
            <span
              class="rr-field__label-required"
              v-if="!$v.profileForm.email.email"
            >
              Invalid email format
            </span>
          </label>
          <input
            class="rr-field__input"
            type="text"
            v-model="profileForm.email"
            @input="$v.profileForm.email.$touch()"
          />
        </div>
      </div>

      <div class="flex flex-row mt-8 mb-4">
        <button
          class="rr-button rr-button--lg rr-button--primary-solid"
          :disabled="$v.$invalid"
          @click.stop="submitForm()"
          v-text="`Save Changes`"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { required, email } from "vuelidate/lib/validators";
import { validationMixin } from "vuelidate";
import { mapGetters } from "vuex";

export default {
  name: "Profile",

  data() {
    return {
      profileForm: {}
    };
  },

  computed: {
    ...mapGetters(["currentUser"])
  },

  mixins: [validationMixin],

  methods: {
    initializeProfileForm() {
      this.profileForm = {
        first_name: this.currentUser.first_name,
        last_name: this.currentUser.last_name,
        email: this.currentUser.email,
        username: this.currentUser.username
      };
    },

    submitForm() {
      this.$store
        .dispatch("updateUser", {
          userId: this.currentUser.id,
          update: this.profileForm
        })
        .then(() => {
          this.$router.push({ name: "scan" });
        });
    }
  },

  mounted() {
    this.initializeProfileForm();
  },

  validations() {
    return {
      profileForm: {
        first_name: { required },
        // last_name: { required },
        email: { required, email }
      }
    };
  }
};
</script>

<style lang="scss"></style>
