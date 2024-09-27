<template>
  <div :ref="'create-item'" class="container">
    <div v-if="!loading">
      <ItemUI
        :existingItem="existingItem"
        :hiddenConditions="listedConditions"
      />
    </div>

    <modal ref="listedBefore" data-test="listed-before-modal">
      <template v-slot:header>
        <span class="text-center block">
          Item Listed Before
        </span>
      </template>
      <template v-slot:body>
        <div class="max-w-lg text-lg text-center mb-8 mx-auto">
          Choose a saved listing or create a variant below.
        </div>

        <div v-if="listedUpcItems.length > 0">
          <div class="max-w-lg text-center mb-8 mx-auto">
            Items we found searching by UPC.
          </div>
          <table class="rr-table min-w-full table-auto shadow-lg mb-12">
            <thead>
              <tr>
                <th class="rr-table__th"></th>
                <th class="rr-table__th">Item</th>
                <th class="rr-table__th text-right">Price</th>
                <th class="rr-table__th">Condition</th>
                <th class="rr-table__th rr-table__th--added">Added</th>
              </tr>
            </thead>
            <tbody class="bg-white" data-test="listed-before-modal-items">
              <tr
                is="item-row"
                :key="item.id"
                :item="item"
                v-for="(item, index) in listedUpcItems"
                :data-test="`listed-before-modal-item-${index}`"
              ></tr>
            </tbody>
          </table>
        </div>
      </template>
      <template v-slot:footer>
        <div class="flex flex-col items-center">
          <button
            class="rr-button rr-button--lg rr-button--primary-solid mb-4"
            @click="$refs.listedBefore.closeModal()"
          >
            Add Variant Condition
          </button>
          <button
            class="rr-button rr-button--lg"
            @click="$router.push({ name: 'scan' })"
          >
            Scan a Different Item
          </button>
        </div>
      </template>
    </modal>
  </div>
</template>

<script>
import ItemRow from "@/components/ItemRow";
import ItemUI from "@/components/ItemUI";

import ItemsMixin from "@/mixins/ItemsMixin.js";

export default {
  name: "ItemsCreate",

  mixins: [ItemsMixin],

  components: {
    ItemRow,
    ItemUI
  },

  props: {
    upc: {
      type: String,
      required: false,
      default: null
    }
  },

  data() {
    return {
      existingItem: {},
      listedItems: [],
      listedUpcItems: [],
      itemToCreate: null,
      loading: true,
      loadingUpc: false
    };
  },

  computed: {
    listedConditions() {
      return [
        ...new Set(this.listedItems.map(({ condition_id }) => condition_id))
      ];
    }
  },

  mounted() {
    this.$store.dispatch("getPreferences");

    if (this.upc) {
      this.queryUpc();
    }

    this.loading = false;

    /* Scrolls the page down to the top of the div to
     *  expose as much of the item create options as possible */
    setTimeout(() => {
      let el = this.$refs["create-item"];
      if (el) {
        window.scrollTo(0, el.offsetTop);
      }
    }, 350);
  },

  methods: {
    queryUpc() {
      this.existingItem.upc = this.upc;
      this.loadingUpc = true;

      this.$store
        .dispatch("getUpcData", this.upc)
        .then(({ upc_item, listed_upc_items }) => {
          this.loadingUpc = false;

          if (upc_item) {
            delete upc_item.weight;
            this.existingItem = upc_item;
          } else if (!upc_item) {
            this.$toasted.show("Did not find any data for UPC.", {
              type: "error"
            });
          }

          if (listed_upc_items.length > 0) {
            this.listedItems = [...listed_upc_items];
            this.listedUpcItems = listed_upc_items;
            this.$refs.listedBefore.openModal();
            this.existingItem.classification_id = this.listedItems[0].classification_id;
          }
        });
    }
  }
};
</script>
