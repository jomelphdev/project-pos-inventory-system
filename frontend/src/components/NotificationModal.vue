<template>
  <modal ref="modal" @closed="() => $store.dispatch('shiftDbNotifications')">
    <template v-slot:header>
      <div class="text-center">
        <h2>Announcement</h2>
        <div v-html="header"></div>
      </div>
    </template>
    <template v-slot:body>
      <div v-html="body" class="body overflow-y-auto"></div>
    </template>
    <template v-slot:footer>
      <div v-html="footer"></div>
      <button
        class="rr-button rr-button--lg rr-button--primary-solid float-right"
        @click="closeModal"
      >
        Close
      </button>
    </template>
  </modal>
</template>

<script>
import Modal from "./Modal.vue";

export default {
  props: {
    notification: {
      type: Object,
      required: true,
      default: () => {}
    },
    preview: Boolean
  },

  components: { Modal },

  computed: {
    header() {
      return this.notification && this.notification.header
        ? this.notification.header
        : "";
    },
    body() {
      return this.notification ? this.notification.body : "";
    },
    footer() {
      return this.notification && this.notification.footer
        ? this.notification.footer
        : "";
    }
  },

  watch: {
    notification(notification) {
      if (notification && !this.preview) {
        this.$store.dispatch("readNotification", notification.id);
      }
    }
  },

  methods: {
    openModal() {
      this.$refs.modal.openModal();
    },
    closeModal() {
      this.$refs.modal.closeModal();
    }
  }
};
</script>

<style lang="scss" scoped>
.body {
  @screen md {
    height: 500px;
  }

  height: 300px;
}
</style>

<style lang="scss">
.ql-video {
  height: 475px;
  width: 100%;
}
</style>
