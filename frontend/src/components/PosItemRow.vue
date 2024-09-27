<template>
  <tr class="rr-table__tr--hover" :class="rowClass" data-test="pos-item-row">
    <td class="rr-table__td pl-8 pr-0">
      <div class="flex items-center">
        <div
          class="rr-table__delete-row"
          data-test="pos-item-row-removeItem-button"
          @click.stop="removeItem"
          v-if="showRemoveOption"
        >
          <i
            class="rr-table__icon svg svg-row-delete shadow rounded-md overflow-hidden"
          ></i>
        </div>
        <div
          v-if="item.images && item.images.length > 0"
          class="bg-no-repeat bg-center bg-contain mx-auto"
          style="width: 50px; height: 50px; mix-blend-mode: multiply"
          :style="`background-image:url(${imageCdn(
            item,
            'w=150&h=150&t=fit'
          )});`"
          data-test="pos-item-row-image"
        />
      </div>
    </td>
    <td class="rr-table__td rr-table__td--item">
      <div class="flex flex-col">
        <div
          class="text-sm leading-5 font-medium text-gray-900"
          data-test="pos-item-row-title"
        >
          {{ item.title | truncate(120) }}

          <div
            class="font-normal text-xs leading-5 text-red-700"
            data-test="pos-item-row-title-indicator"
            v-if="item.added_item && !item.title"
          >
            Requires a title
          </div>
          <div
            class="font-normal text-xs leading-5 text-red-700"
            data-test="pos-item-row-classification-indicator"
            v-if="
              item.added_item &&
              !item.classification_id &&
              !classifications_disabled
            "
          >
            Requires a classification
          </div>
        </div>
        <div
          class="text-xs leading-5 text-gray-500"
          data-test="pos-item-row-classification"
          v-if="item.classification_id"
        >
          {{ getClassificationName(item.classification_id) }}
        </div>
        <div v-if="item.consignor_id" data-test="pos-item-row-consignment">
          <span class="rr-pill rr-pill--blue"> Consignment Item </span>
        </div>
      </div>
    </td>
    <td class="rr-table__td text-right">
      <div
        class="text-sm leading-5 font-medium text-gray-900"
        data-test="pos-item-row-quantity"
        v-if="
          item.quantity_left_to_return == undefined ||
          item.quantity_left_to_return == item.quantity_ordered
        "
      >
        {{ item.quantity_ordered }}
      </div>
      <div
        class="text-sm leading-5 font-medium text-gray-900"
        data-test="pos-item-row-quantity-left"
        v-else
      >
        {{ item.quantity_left_to_return }} left of {{ item.quantity_ordered }}
      </div>
    </td>
    <td class="rr-table__td text-right">
      <div
        class="text-sm leading-5 font-medium text-gray-900"
        data-test="pos-item-row-price"
      >
        {{ formatCurrency(item.price) }}
      </div>
      <div
        class="text-xs leading-5 text-gray-700 line-through"
        data-test="pos-item-row-original-price"
        v-if="
          priceWasManuallyChanged ||
          (!this.item.temp_price &&
            (this.item.discount_id != null ||
              this.item.discount_amount != null))
        "
      >
        Orig. Price: {{ formatCurrency(originalPrice) }}
      </div>
      <div
        class="text-xs leading-5 text-gray-700 line-through"
        data-test="pos-item-row-temp-price"
        v-if="
          this.item.temp_price &&
          (this.item.discount_id != null || this.item.discount_amount != null)
        "
      >
        {{ formatCurrency(item.temp_price) }}
      </div>
      <div
        class="text-xs leading-5 text-gray-700"
        data-test="pos-item-row-price-discount-percentage"
        v-if="item.discount_id"
      >
        {{ discountAmount(item.discount_id) }}
      </div>
      <div
        class="text-xs leading-5 text-red-700"
        data-test="pos-item-row-price-indicator"
        v-if="item.price == 0 && !item.discount_id && !item.discount_amount"
      >
        Input a price
      </div>
    </td>
    <td class="rr-table__td text-right">
      <div
        class="text-sm leading-5 font-medium text-gray-900"
        data-test="pos-item-row-total"
      >
        {{ formatCurrency(item.total || 0) }}
      </div>
      <div
        class="text-xs leading-5 text-green-700"
        data-test="pos-item-row-original-price"
        v-if="item.discount_description"
      >
        Applied: <br />
        {{ item.discount_description }}
      </div>
    </td>
  </tr>
</template>

<script>
import {
  imageCdn,
  imageAvailable,
  formatCurrency,
  getDiscount,
  getClassificationName,
} from "@/helpers";
import { mapGetters } from "vuex";

export default {
  props: {
    item: Object,
    selectedId: [String, Number],
    index: Number,
    showRemoveOption: {
      type: Boolean,
      default: true,
    },
  },

  methods: {
    removeItem() {
      this.$root.$emit("remove-item", this.item);
    },

    discountAmount(discountId) {
      const discount = getDiscount(discountId);
      let output = "";

      if (discount) {
        const amount = discount.discount;

        if (!isNaN(amount)) {
          output = (amount * 100).toFixed(0) + "% Discount";
        }
      }

      return output;
    },

    imageCdn,
    imageAvailable,
    formatCurrency,
    getClassificationName,
  },

  computed: {
    ...mapGetters(["classifications_disabled"]),

    isSelected() {
      return this.selectedId == this.item.id;
    },
    priceWasManuallyChanged() {
      return (
        !this.item.added_item &&
        this.item.temp_price != this.item.original_price
      );
    },
    originalPrice() {
      if (this.item.added_item && this.priceWasManuallyChanged)
        return this.item.temp_price;
      else if (this.item.added_item) return this.item.added_item.original_price;
      else if (this.priceWasManuallyChanged) return this.item.original_price;
      else return this.item.item.price;
    },
    rowClass() {
      if (
        (this.isSelected && !("quantity_left_to_return" in this.item)) ||
        (this.item.quantity_left_to_return != 0 && this.isSelected)
      ) {
        return "rr-table__tr--selected";
      } else if (this.item.quantity_left_to_return == 0) {
        return "rr-table__tr--hidden";
      }

      return null;
    },
  },

  filters: {
    upc(upc) {
      if ("NaN" == upc) {
        upc = "";
      }
      return upc;
    },
  },
};
</script>
