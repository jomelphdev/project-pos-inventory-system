<template>
  <div id="app" class="rr-app">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,700&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.core.min.css"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.bubble.min.css"
    />

    <transition name="fade-in" appear>
      <UpdateBar />
    </transition>
    <transition name="fade-in" appear>
      <Header />
    </transition>
    <qz-tray v-show="qzPanel"></qz-tray>
    <transition name="fade-in" mode="out-in" appear>
      <router-view />
    </transition>
    <NotificationModal
      ref="notificationModal"
      :notification="activeNotification"
    />
  </div>
</template>

<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script>
import Header from "@/components/Header";
import UpdateBar from "@/components/UpdateBar";
import Pusher from "pusher-js";
import { mapGetters } from "vuex";
import { EventBus } from "@/event-bus.js";
import NotificationModal from "./components/NotificationModal.vue";

export default {
  name: "App",

  data() {
    return {
      pusher: null,
      activeNotification: {}
    };
  },

  mounted() {
    this.checkPusher();
    this.connectIntercom();
  },

  components: {
    Header,
    UpdateBar,
    NotificationModal
  },

  computed: {
    ...mapGetters([
      "qzPanel",
      "notifications",
      "dbNotifications",
      "currentUser",
      "version"
    ])
  },

  watch: {
    notifications(notifications) {
      if (notifications.length > 0) {
        this.$toasted.clear();

        for (let notification of notifications) {
          this.$toasted.show(notification.text, { type: notification.type });
        }
      }
    },

    dbNotifications(notifications) {
      if (notifications.length > 0) {
        this.activeNotification = notifications[0];
        this.$refs.notificationModal.openModal();
      }
    },

    currentUser() {
      this.checkPusher();
      this.connectIntercom();
    }
  },

  methods: {
    connectIntercom() {
      if (!this.currentUser) {
        return;
      }

      this.$intercom.boot({
        user_id: this.currentUser.id,
        name: this.currentUser.first_name,
        email: this.currentUser.email,
        created_at: this.currentUser.created_at
      });
    },
    checkPusher() {
      if (!this.pusher && this.currentUser) this.connectPusher();
    },
    connectPusher() {
      // Enable pusher logging - don't include this in production
      if (process.env.NODE_ENV != "production") Pusher.logToConsole = true;

      this.pusher = new Pusher(process.env.VUE_APP_PUSHER_KEY, {
        cluster: "us2"
      });

      const channel = this.pusher.subscribe(`App.User.${this.currentUser.id}`);
      const retailRight = this.pusher.subscribe("RetailRight.All");

      // App.User.${this.currentUser.id}

      channel.bind("notification", response => {
        const data = response.response;
        this.$toasted.show(data.message, {
          type: data.success ? "success" : "error"
        });

        switch (data.response_type) {
          case "email-verification":
            EventBus.$emit("verify-user", this.currentUser.id);
            break;
          case "file-finished":
            this.$store.dispatch("setLoadingReport", false);
            break;
        }
      });

      channel.bind("logout", response => {
        this.$toasted.show(response.response.message, { type: "success" });

        setTimeout(() => {
          this.$store.dispatch("logout");
        }, 3000);
      });

      // RetailRight.All

      retailRight.bind("version-updated", response => {
        const version = response.response.version;
        this.$store.dispatch("setVersion", version);
      });
    }
  },
  metaInfo() {
    let title = "RetailRight";
    if (this.version) {
      title = `${title} v${this.version}`;
    }
    return {
      titleTemplate: "%s" + title
    };
  }
};
</script>

<style src="./assets/tailwind.css"></style>
<style lang="scss">
@import "@/assets/scss/app.scss";
</style>
