<template>
  <div class="bg-gray-300 py-3" v-show="updateAvailable">
    <div class="container">
      <div class="flex justify-center items-center text-sm">
        <CloudDownloadIcon size="20" class="mr-1" />
        <span style="margin-left:1px;margin-bottom:1px;">
          <strong>
            {{ message }}
          </strong>
          <button @click.stop="applyUpdate" class="rr-link-blue ml-1">
            {{ label }}
          </button>
        </span>
      </div>
    </div>
  </div>
</template>

<script>
import { CloudDownloadIcon } from "@vue-hero-icons/outline";
import { mapGetters } from "vuex";

export default {
  computed: {
    ...mapGetters(["updateAvailable"])
  },
  components: {
    CloudDownloadIcon
  },
  props: {
    message: {
      type: String,
      default: "A new update is available."
    },
    label: {
      type: String,
      default: "Click here to apply the update."
    }
  },
  methods: {
    applyUpdate() {
      this.$store.dispatch("applyUpdate");
    }
  }
};
</script>
