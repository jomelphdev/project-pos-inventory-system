<template>
  <div class="container mb-12">
    <transition name="fade-in" appear>
      <div class="rr-field no-print">
        <input
          class="rr-field__input"
          type="text"
          v-model="query"
          placeholder="Search Items (Title, SKU, or UPC)"
          ref="itemSearchInput"
        />
      </div>
    </transition>
    <ItemsTable :items="items" :loading="loading" />
  </div>
</template>

<script>
import _ from "lodash";

import ItemsTable from "@/components/ItemsTable";

export default {
  name: "ItemsIndex",

  components: {
    ItemsTable
  },

  data() {
    return {
      data: {},
      loading: false,
      items: [],
      lastSeenId: null,
      viewItem: false,
      query: "",
      previousQuery: "",
      timeout: null
    };
  },

  watch: {
    query: _.debounce(function() {
      this.getItems();
    }, 500)
  },

  methods: {
    getItems() {
      const newQuery = this.previousQuery != this.query;
      this.loading = true;

      if (newQuery) {
        this.items = [];
        this.lastSeenId = null;
      }

      let options = {
        query: this.query,
        last_seen_id: this.lastSeenId
      };

      this.$store
        .dispatch("queryItems", options)
        .then(items => {
          if (newQuery) {
            this.items = items;
            this.itemsLeft = true;
          } else {
            this.items = this.items.concat(items);
          }

          if (items.length == 0 || items.length < 30) {
            this.itemsLeft = false;
          }

          this.lastSeenId = this.items[this.items.length - 1].id;
          this.previousQuery = this.query;
        })
        .finally(() => {
          this.loading = false;
          this.focusInput();
        });
    },

    watchScroll: _.throttle(function() {
      this.onScroll();
    }, 250),

    onScroll() {
      let bottomOfWindow =
        Math.max(
          window.pageYOffset,
          document.documentElement.scrollTop,
          document.body.scrollTop
        ) +
          window.innerHeight >
        document.documentElement.offsetHeight - 250;

      if (bottomOfWindow && !this.loading && this.itemsLeft) {
        this.getItems();
      }
    },

    focusInput() {
      this.$refs.itemSearchInput.focus();
    }
  },

  mounted() {
    this.getItems();
    window.addEventListener("scroll", this.watchScroll);
  },

  destroyed() {
    window.removeEventListener("scroll", this.watchScroll);
  }
};
</script>
