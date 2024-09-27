<template>
  <LoginForm @login="login" />
</template>

<script>
import LoginForm from "@/components/LoginForm";

export default {
  name: "Login",

  components: { LoginForm },

  methods: {
    login(credentials) {
      this.$store.dispatch("login", credentials);
    }
  }
};
</script>
