<template>
  <div class="rr-field mb-0 flex-1" data-test="pos-search">
    <input
      class="rr-field__input"
      type="text"
      v-model="query"
      placeholder="Scan or Enter SKU or UPC"
      ref="posSearchInput"
      data-test="pos-search-input"
      :disabled="disabled"
      @keyup.enter="queryItems"
    />

    <modal-wall ref="upcResultsModal" data-test="upc-results-modal">
      <template v-slot:header>
        <a
          href="#"
          class="modal__close"
          @click.prevent.stop="$refs.upcResultsModal.closeModal()"
        >
          Close
        </a>

        <span class="text-center block">
          Multiple results for UPC
        </span>
      </template>
      <template v-slot:body>
        <div class="max-w-lg text-lg text-center mb-8 mx-auto">
          Choose which one your customer is checking out.
        </div>

        <table class="rr-table min-w-full table-auto shadow-lg mb-12">
          <thead>
            <tr>
              <th class="rr-table__th"></th>
              <th class="rr-table__th">Item</th>
              <th class="rr-table__th text-right">Price</th>
              <th class="rr-table__th">Condition</th>
            </tr>
          </thead>
          <tbody class="bg-white" data-test="upc-results-modal-items">
            <tr
              v-for="(item, index) in upcResults"
              class="rr-table__tr--hover"
              :key="item.id"
              :data-test="`upc-results-modal-item-${index}`"
              @click="selectItemFromResults(item)"
            >
              <td class="rr-table__td pr-0">
                <div
                  class="bg-no-repeat bg-center bg-contain mx-auto flex items-center"
                  style="width: 50px; height: 50px; mix-blend-mode: multiply;"
                  :style="
                    `background-image:url(${imageCdn(
                      item,
                      'w=150&h=150&t=fit'
                    )});`
                  "
                >
                  <span
                    v-if="!imageAvailable(item)"
                    class="flex text-xs text-center leading-tight text-gray-500"
                  >
                    No Image
                  </span>
                </div>
              </td>
              <td class="rr-table__td rr-table__td--item">
                <div class="flex flex-col">
                  <div class="text-sm leading-5 font-medium text-gray-900">
                    {{ item.title | truncate(120) }}
                  </div>
                  <div class="text-xs leading-5 text-gray-500">
                    {{ item.upc | upc }}
                  </div>
                </div>
              </td>
              <td
                class="rr-table__td text-sm leading-5 text-gray-900 text-right"
              >
                {{ formatCurrency(item.price) }}
              </td>
              <td class="rr-table__td">
                <span
                  class="rr-pill"
                  :class="getConditionClass(item.condition_id)"
                >
                  {{ getConditionName(item.condition_id) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </template>
    </modal-wall>
  </div>
</template>

<script>
import moment from "moment";
import { mapGetters } from "vuex";
import _ from "lodash";

import ModalWall from "./ModalWall.vue";

import {
  getConditionName,
  getConditionClass,
  imageCdn,
  imageAvailable,
  formatCurrency
} from "@/helpers";

export default {
  components: { ModalWall },

  computed: {
    ...mapGetters(["posStore"])
  },

  data() {
    return {
      query: "",
      ready: true,
      upcResults: []
    };
  },

  props: {
    fireQuery: {
      type: Boolean
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },

  watch: {
    query: _.debounce(function(query) {
      if (query.length == 10 || query.length == 12) {
        this.queryItems();
      }
    }, 500),

    fireQuery(bool) {
      if (bool == true) {
        this.queryItems();
        this.$emit("update:fireQuery", false);
      }
    }
  },

  methods: {
    queryItems() {
      if (this.query.length == 10 && this.ready) {
        // SKU Search
        this.ready = false;

        this.$store
          .dispatch("getItemBySku", this.query)
          .then(item => {
            this.$emit("item-found", item);
            this.query = "";
          })
          .finally(() => {
            this.ready = true;
          });
      } else if (this.query.length == 12 && this.ready) {
        // UPC Search
        this.ready = false;

        this.$store
          .dispatch("getItemsByUpc", {
            upc: this.query,
            options: {
              with_quantities: true,
              only_for_store_id: this.posStore.id
            }
          })
          .then(items => {
            if (items.length == 1) {
              this.$emit("item-found", items[0]);
            } else if (items.length > 1) {
              this.upcResults = items;
              this.$refs.upcResultsModal.openModal();
            } else {
              this.$toasted.show("No items with this UPC in this store.", {
                type: "error"
              });
            }

            this.query = "";
          })
          .finally(() => {
            this.ready = true;
          });
      } else if (this.ready) {
        this.$toasted.show("Invalid format.", { type: "error" });
      }
    },

    focusInput() {
      if (this.$refs.posSearchInput) {
        this.$refs.posSearchInput.focus();
      }
    },

    selectItemFromResults(item) {
      this.$emit("item-found", Object.assign({}, item));
      this.$refs.upcResultsModal.closeModal();
    },

    imageCdn,
    imageAvailable,
    getConditionName,
    getConditionClass,
    formatCurrency
  },

  mounted() {
    this.focusInput();
  },

  filters: {
    moment(date) {
      // return moment(date).format("MMMM Do, YYYY");
      return moment(date).fromNow();
    },

    upc(upc) {
      if ("NaN" == upc) {
        upc = "";
      }
      return upc;
    }
  }
};
</script>
