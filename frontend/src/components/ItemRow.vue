<template>
  <tr
    class="rr-table__tr--hover"
    @click="editItem(item)"
    @mouseover="hover = true"
    @mouseleave="hover = null"
  >
    <td class="rr-table__td pr-0">
      <div
        class="bg-no-repeat bg-center bg-contain mx-auto flex items-center"
        style="width: 50px; height: 50px; mix-blend-mode: multiply;"
        data-test="item-image"
        :style="`background-image:url(${imageCdn(item, 'w=150&h=150&t=fit')});`"
      >
        <span
          v-if="!imageAvailable(item)"
          class="flex text-xs text-center leading-tight text-gray-500"
          data-test="no-item-image"
        >
          No Image
        </span>
      </div>
    </td>
    <td class="rr-table__td rr-table__td--item">
      <div class="flex flex-col">
        <div class="text-sm leading-5 font-medium text-gray-900">
          {{ item.title | truncate(120) }}
          <!-- <span
              @click="printLabel(item._id)"
              class="rr-pill rr-pill--used ml-2"
              v-if="printerSelected && qzTrayConnected"
              >Print</span
            > -->
        </div>
        <div class="text-xs leading-5 text-gray-500">
          {{ item.upc | upc }}
        </div>
      </div>
    </td>
    <td class="rr-table__td text-sm leading-5 text-gray-900 text-right">
      {{ formatCurrency(item.price) }}
    </td>
    <td class="rr-table__td">
      <span class="rr-pill" :class="getConditionClass(item.condition_id)">
        {{ getConditionName(item.condition_id) }}
      </span>
    </td>
    <td class="rr-table__td rr-table__td--added">
      <div class="text-sm leading-5 text-gray-900">
        {{ item.created_at | moment }}
      </div>
      <div class="text-xs leading-5 text-gray-500">
        by {{ item.created_by_user.username }}
      </div>
    </td>
    <td v-if="$slots.hoverOptions" v-show="hover">
      <slot name="hoverOptions" />
    </td>
  </tr>
</template>

<script>
import moment from "moment";
import { mapGetters } from "vuex";
import {
  getConditionName,
  getConditionClass,
  imageCdn,
  imageAvailable,
  formatCurrency
} from "@/helpers";

export default {
  props: {
    item: {
      type: Object
    }
  },

  data() {
    return {
      hover: false
    };
  },

  methods: {
    editItem(item) {
      this.navigateTo({
        name: "items.edit",
        params: { id: item.id, item: item }
      });
    },

    navigateTo(routeData) {
      this.$router.push(routeData);
    },

    viewItem(item) {
      this.$root.$emit("view-item", item);
    },

    imageCdn,
    imageAvailable,
    getConditionName,
    getConditionClass,
    formatCurrency
  },

  computed: {
    ...mapGetters(["conditions"])
  },

  filters: {
    moment(date) {
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
