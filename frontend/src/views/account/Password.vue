<template>
  <div>
    <h1 class="h1">Password</h1>
    <div class="mt-6">
      <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
        <div class="rr-field">
          <label class="rr-field__label">
            Current Password

            <span
              class="rr-field__label-required"
              v-if="!$v.old.required || !oldPasswordVerified"
            >
              Required
            </span>
          </label>
          <input
            class="rr-field__input"
            type="password"
            v-model="old"
            @input="$v.old.$touch()"
          />
        </div>
      </div>

      <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
        <div class="rr-field">
          <label class="rr-field__label">
            New Password

            <span
              class="rr-field__label-required"
              v-if="!$v.password.new.required"
            >
              Required
            </span>
          </label>
          <input
            class="rr-field__input"
            type="password"
            v-model="password.new"
            @input="$v.password.new.$touch()"
          />
        </div>
        <div class="rr-field">
          <label class="rr-field__label">
            Confirm Password

            <span
              class="rr-field__label-required"
              v-if="!$v.password.confirm.sameAsPassword"
            >
              Doesn't match
            </span>
          </label>
          <input
            class="rr-field__input"
            type="password"
            v-model="password.confirm"
            @input="$v.password.confirm.$touch()"
          />
        </div>
      </div>

      <div class="flex flex-row mt-8 mb-4">
        <button
          class="rr-button rr-button--lg rr-button--primary-solid"
          :disabled="$v.$invalid || !oldPasswordVerified"
          @click.stop="submitForm()"
          v-text="`Update Password`"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { required, sameAs } from "vuelidate/lib/validators";
import { validationMixin } from "vuelidate";
import { mapGetters } from "vuex";
import _ from "lodash";

export default {
  name: "Password",

  data() {
    return {
      old: "",
      oldPasswordVerified: false,
      password: {
        new: "",
        confirm: ""
      },
      timeout: null
    };
  },

  mixins: [validationMixin],

  computed: {
    ...mapGetters(["currentUser"])
  },

  watch: {
    old: _.debounce(function() {
      this.checkPassword();
    }, 500)
  },

  methods: {
    submitForm() {
      const update = { password: this.password.new };
      this.$store
        .dispatch("updateUser", {
          userId: this.currentUser.id,
          update: update
        })
        .then(() => {
          this.$router.push({ name: "scan" });
        });
    },
    checkPassword() {
      this.$store
        .dispatch("verifyPassword", {
          userId: this.currentUser.id,
          password: this.old
        })
        .then(isMatch => {
          this.oldPasswordVerified = isMatch;
        });
    }
  },

  validations() {
    return {
      old: { required },
      password: {
        new: { required },
        confirm: { required, sameAsPassword: sameAs("new") }
      }
    };
  }
};
</script>

<style lang="scss"></style>
