<template>
  <div data-test="pos-right">
    <div data-test="pos-right-editMode" v-if="editMode">
      <div class="mb-6">
        <div class="mb-4 font-bold text-2xl">Edit Order</div>
        <strong>Warning:</strong> Any changes made here will edit the entire
        order.
      </div>

      <div class="rr-field">
        <div class="rr-field__label">Tax</div>
        <div class="rr-field__radio">
          <input
            type="radio"
            v-model="taxed"
            :value="true"
            @click="$emit('apply-no-tax', false)"
            id="taxedTrue"
            class="rr-field__radio-input"
            data-test="pos-right-orderApplyTax-input"
          />
          <label for="taxedTrue" class="rr-field__radio-label">Apply Tax</label>
        </div>
        <div class="rr-field__radio">
          <input
            type="radio"
            v-model="taxed"
            :value="false"
            @click="$emit('apply-no-tax', true)"
            id="taxedFalse"
            class="rr-field__radio-input"
            data-test="pos-right-orderNoTax-input"
          />
          <label for="taxedFalse" class="rr-field__radio-label">No Tax</label>
        </div>
      </div>

      <custom-select
        :headerLabel="'Discount'"
        :options="discountsVisible"
        :selectedOptionId="selectedDiscount"
        :show="discountsVisible && showDiscounts"
        :disabled="!!orderDiscountAmount"
        @option-selected="(option) => selectDiscount(option)"
        @options-extended="(bool) => (showClassifications = !bool)"
      />
    </div>

    <div data-test="pos-right-main" v-else>
      <div class="rr-field">
        <template v-if="selectedItem.added_item">
          <label class="rr-field__label">
            Item Name

            <span
              class="rr-field__label-required"
              data-test="pos-right-title-indicator"
              v-if="!selectedItem.title"
            >
              Required
            </span>
          </label>
          <div class="rr-field">
            <input
              class="rr-field__input font-bold text-2xl"
              data-test="pos-right-itemTitle-input"
              v-model="itemTitle"
            />
          </div>
        </template>

        <template v-else>
          <div class="mb-4 font-bold text-2xl" data-test="pos-right-itemTitle">
            {{ selectedItem.title | truncate(50) }}
          </div>
        </template>
      </div>

      <div class="rr-field">
        <label class="rr-field__label">
          Item Price

          <span
            class="rr-field__label-required"
            data-test="pos-right-price-indicator"
            v-if="
              !price &&
              !selectedItem.discount_id &&
              !selectedItem.discount_amount
            "
          >
            Required
          </span>
        </label>
        <currency-input
          class="rr-field__input"
          :class="{
            'rr-field__input--flash': flashOriginalPrice,
            'text-gray-500': selectedItem.item_specific_discount_id,
          }"
          data-test="pos-right-itemPrice-input"
          v-model="price"
          :disabled="!!selectedItem.item_specific_discount_id"
        />

        <div
          class="text-sm text-red-700"
          v-if="!!selectedItem.item_specific_discount_id"
        >
          Changing the price manually is disabled when using a bundle discount.
        </div>
      </div>
      <button
        v-if="
          !selectedItem.added_item &&
          selectedItem.original_price != selectedItem.temp_price
        "
        class="rr-button rr-button--primary flex justify-center mb-4"
        data-test="pos-right-applyOriginalPrice-button"
        @click="price = selectedItem.original_price"
      >
        Apply Original Price
      </button>

      <div class="rr-field">
        <div class="flex items-baseline justify-between">
          <label class="rr-field__label"> Quantity Ordered </label>
        </div>
        <input
          class="rr-field__input"
          data-test="pos-right-itemQuantityOrdered-input"
          v-model="quantityOrdered"
        />
      </div>

      <custom-select
        :headerLabel="'Classification'"
        :options="classificationsVisible"
        :selectedOptionId.sync="selectedItem.classification_id"
        :required="!selectedItem.classification_id"
        :show="
          selectedItem.added_item == true &&
          showClassifications &&
          !classifications_disabled
        "
        @options-extended="(bool) => (showDiscounts = !bool)"
      />

      <custom-select
        :headerLabel="'Discount'"
        :options="discountsVisible"
        :selectedOptionId="selectedItem.discount_id"
        :show="discountsVisible && !orderWideDiscount && showDiscounts"
        :disabled="!!itemDiscountAmount"
        @option-selected="(option) => selectDiscount(option)"
        @options-extended="(bool) => (showClassifications = !bool)"
        v-if="
          !selectedItem.item_specific_discount_times_applied ||
          (selectedItem.item_specific_discount_id &&
            selectedItem.item_specific_discount_can_stack) ||
          editMode
        "
      />
    </div>

    <div
      class="rr-field"
      v-if="!selectedItem.item_specific_discount_times_applied || editMode"
    >
      <label class="rr-field__label"> Discount Dollar Amount </label>
      <currency-input
        v-if="!editMode"
        class="rr-field__input"
        data-test="pos-right-itemDiscountAmount-input"
        v-model="itemDiscountAmount"
      />
      <currency-input
        v-else
        class="rr-field__input"
        data-test="pos-right-orderDiscountAmount-input"
        v-model="orderDiscountAmount"
      />
      <div class="flex flex-row mt-2" v-if="!editMode">
        <button
          class="rr-button rounded-r-none"
          :class="[
            discountAmountType == 'price'
              ? 'rr-button--primary-solid'
              : 'rr-button--primary',
          ]"
          @click="discountAmountType = 'price'"
        >
          Price
        </button>
        <button
          class="rr-button rounded-l-none"
          :class="[
            discountAmountType == 'total'
              ? 'rr-button--primary-solid'
              : 'rr-button--primary',
          ]"
          @click="discountAmountType = 'total'"
        >
          Total
        </button>
      </div>
    </div>

    <div v-if="editMode">
      <button
        class="rr-button rr-button--lg rr-button--primary-solid flex justify-center w-full"
        data-test="pos-right-done-button"
        @click="$emit('disable-edit-mode')"
      >
        Done
      </button>
    </div>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import _ from "lodash";

import CustomSelect from "./CustomSelect.vue";
import { CurrencyInput } from "vue-currency-input";

export default {
  props: {
    editMode: {
      type: Boolean,
    },
    currentlyTaxed: {
      type: Boolean,
    },
    selectedItem: Object,
    order: Object,
  },

  components: {
    CustomSelect,
    CurrencyInput,
  },

  computed: {
    ...mapGetters([
      "discountsVisible",
      "classificationsVisible",
      "classifications_disabled",
      "posStore",
    ]),

    quantityOrdered: {
      get: function () {
        return this.selectedItem.quantity_ordered;
      },
      set: async function (quantity) {
        if (quantity > 0)
          this.$emit("update-quantity-ordered", parseInt(quantity));
      },
    },
    price: {
      get: function () {
        return parseInt(this.selectedItem.price);
      },
      set: _.debounce(function (price) {
        this.$emit("update-price", price);
      }, 250),
    },

    itemTitle: {
      get: function () {
        return this.selectedItem.title;
      },
      set: function (title) {
        this.$emit("update-title", title);
      },
    },

    itemDiscountAmount: {
      get: function () {
        return this.selectedItem.discount_amount;
      },
      set: _.debounce(function (amount) {
        if (
          (!this.selectedItem.added_item && amount > this.selectedItem.price) ||
          (this.selectedItem.added_item && !this.selectedItem.temp_price) ||
          amount > this.selectedItem.temp_price
        ) {
          this.$emit("update-discount-amount", null);
          return this.$toasted.show(
            "Discount must be less than the price of the item.",
            { type: "error" }
          );
        }

        this.$emit("update-discount-amount", amount);
      }, 250),
    },

    orderDiscountAmount: {
      get: function () {
        return this.order.discount_amount;
      },
      set: _.debounce(function (amount) {
        if (amount > this.order.sub_total) {
          this.$emit("update-discount-amount", null);
          return this.$toasted.show(
            "Discount must be less than the total of the order.",
            { type: "error" }
          );
        }

        this.discountAmountType = "total";
        this.$emit("update-discount-amount", amount);
      }, 250),
    },
  },

  watch: {
    discountAmountType(type) {
      this.$emit("update-discount-amount-type", type);
    },
  },

  data() {
    return {
      flashOriginalPrice: false,
      taxed: this.currentlyTaxed,
      timeout: null,
      orderWideDiscount: false,
      showClassifications: true,
      showDiscounts: true,
      selectedDiscount: null,
      discountAmountType: "price",
    };
  },

  methods: {
    selectDiscount(discountId) {
      if (
        (this.editMode && this.selectedDiscount == discountId) ||
        (!this.editMode && this.selectedItem.discount_id == discountId)
      ) {
        discountId = null;
      }

      if (this.editMode) {
        this.selectedDiscount = discountId;
        return this.$root.$emit("order-wide-discount", discountId);
      }

      this.$emit("update-discount", discountId);
      this.flashOriginalPriceInput();
    },

    flashOriginalPriceInput() {
      this.flashOriginalPrice = true;
      setTimeout(() => {
        this.flashOriginalPrice = false;
      }, 500);
    },
  },
};
</script>

<style></style>
