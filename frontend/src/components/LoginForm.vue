<template>
  <div class="container">
    <div class="rr-card rr-card--shadow px-6 py-8 max-w-sm mx-auto">
      <h1 class="h1">Login</h1>
      <form @submit.prevent="login">
        <div class="rr-field">
          <label class="rr-field__label">Username</label>
          <input
            class="rr-field__input"
            type="text"
            v-model="username"
            data-test="username-input"
          />
        </div>
        <div class="rr-field">
          <label class="rr-field__label">Password</label>
          <input
            class="rr-field__input"
            type="password"
            v-model="password"
            data-test="password-input"
          />
        </div>
        <div class="mt-4">
          <button
            class="rr-button rr-button--primary"
            :disabled="!formSubmittable"
            @click.prevent="submit"
            data-test="login-button"
          >
            Login
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      username: "",
      password: ""
    };
  },

  computed: {
    formSubmittable() {
      return !!this.username && !!this.password;
    }
  },

  methods: {
    submit() {
      this.$emit("login", { username: this.username, password: this.password });
      this.password = "";
    }
  }
};
</script>
