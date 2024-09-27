<template>
  <div class="container">
    <div class="max-w-2xl mx-auto text-center">
      <blank-state>
        <template v-slot:body>
          <h1 class="h1">
            Verify Your Account
          </h1>
          <p>
            A verfication email must be sent to
            <strong> {{ currentUser.email }}</strong
            >. <br />Please click the verification link to gain access to the
            application.
          </p>
          <div class="mt-8 flex justify-center">
            <button
              class="rr-button rr-button--lg rr-button--primary "
              @click.stop="sendVerification"
              v-if="!verificationSent"
            >
              Send Verification Email
            </button>
            <div v-else class="rr-button rr-button--lg">
              Email Sent to {{ currentUser.email }}
            </div>
          </div>
        </template>
      </blank-state>
    </div>
  </div>
</template>

<script>
import BlankState from "@/components/BlankState";
import { mapGetters } from "vuex";
import { EventBus } from "@/event-bus.js";

export default {
  name: "Login",

  components: {
    BlankState
  },

  mounted() {
    this.getUser(this.currentUser.id);

    EventBus.$on("verify-user", () => {
      this.getUser(this.currentUser.id);
    });
  },

  beforeDestroy() {
    EventBus.$off("verify-user");
  },

  data() {
    return {
      verificationSent: false
    };
  },

  computed: {
    ...mapGetters(["currentUser"])
  },

  methods: {
    verifyAccount() {
      this.$store.dispatch("verifyAccount");
    },
    sendVerification() {
      this.verificationSent = true;
      this.$store.dispatch("resendEmailVerification");
      setTimeout(() => (this.verificationSent = false), 300);
    },
    getUser(userId) {
      this.$store.dispatch("getUser", userId).then(user => {
        this.$store.dispatch("setUser", user);
      });
    }
  }
};
</script>
