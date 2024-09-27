<template>
  <div v-if="!loading" :ref="'edit-item'" class="container">
    <ItemUI :isEditMode="true" :existingItem="item" />
  </div>
</template>

<script>
import ItemUI from "@/components/ItemUI";

import ItemsMixin from "@/mixins/ItemsMixin.js";

export default {
  name: "ItemsEdit",

  mixins: [ItemsMixin],

  components: {
    ItemUI
  },

  data() {
    return {
      item: null, // From DB
      loading: true
    };
  },

  mounted() {
    this.$store.dispatch("getPreferences");
    this.getItem();

    /* Scrolls the page down to the top of the div to
     * expose as much of the item edit options as possible */
    // TODO: Abstract scroll to top to helpers?
    setTimeout(() => {
      let el = this.$refs["edit-item"];
      if (el) {
        window.scrollTo(0, el.offsetTop);
      }
    }, 350);
  },

  methods: {
    getItem() {
      this.$store
        .dispatch("getItem", this.$route.params.id)
        .then(item => {
          this.item = JSON.parse(JSON.stringify(item));
          this.loading = false;
        })
        .catch(() => {
          this.$router.push({ name: "items.index" });
        });
    }
  }
};
</script>
