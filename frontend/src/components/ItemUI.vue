<template>
  <div class="item-ui">
    <transition name="fade-in" appear>
      <AdjustQuantity :allowNegatives="isEditMode" />
    </transition>

    <div class="item-ui__main">
      <div>
        <h1 class="h1">
          {{
            itemForm.title
              ? itemForm.title
              : isEditMode
              ? "Edit Item"
              : "Create Item"
          }}
        </h1>

        <ItemImages
          :images="itemImages"
          :selectedImages="itemForm.images"
          @set-images="(images) => (itemForm.images = images)"
          @set-deleted-images="
            (images) => {
              if (this.isEditMode) itemForm.deleted_images = images;
            }
          "
        />

        <ItemNavigation @change="(tab) => (itemTab = tab)" />

        <div data-test="item-details" v-if="itemTab == 'details'">
          <div class="rr-field">
            <label class="rr-field__label">
              Title

              <span
                class="rr-field__label-required"
                data-test="title-indicator"
                v-if="!$v.itemForm.title.required"
              >
                Required
              </span>
            </label>
            <input
              class="rr-field__input"
              type="text"
              v-model="itemForm.title"
              data-test="itemFormTitle-input"
            />

            <div v-if="!isEditMode">
              <div
                class="text-sm mt-2 cursor-pointer"
                data-test="similar-titles"
                v-if="
                  searchViaTitle && itemsTitleQueryCount > 0 && itemForm.title
                "
                @click="queryForTitleItems"
              >
                <strong> Items with similar title: </strong>
                {{ itemsTitleQueryCount }}

                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5 inline"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="#3182ce"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
              </div>

              <div
                class="rr-field__radio w-1/2 mt-4"
                v-else-if="itemForm.title && !searchViaTitle"
              >
                <input
                  type="checkbox"
                  v-model="searchViaTitle"
                  :id="`searchViaTitle`"
                  :data-test="`searchViaTitle-input`"
                  class="rr-field__radio-input"
                  @click="
                    searchViaTitle = true;
                    getItemsCountForTitle();
                  "
                />
                <label
                  :for="`searchViaTitle`"
                  class="rr-field__radio-label flex-none text-sm"
                  >Search for similar items based on title?</label
                >
              </div>
            </div>
          </div>

          <div class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8">
            <div class="rr-field">
              <label class="rr-field__label">
                Original Price
                <span
                  class="rr-field__label-required"
                  data-test="originalPrice-indicator"
                  v-if="!$v.itemForm.original_price.required"
                >
                  Required
                </span>
              </label>
              <currency-input
                class="rr-field__input"
                :class="{ 'rr-field__input--flash': flashOriginalPrice }"
                v-model="itemForm.original_price"
                data-test="itemFormOriginalPrice-input"
                @change.once="originalPriceChanged"
              />
              <button
                class="rr-button rr-button--primary mt-2"
                v-if="
                  isEditMode &&
                  itemForm.isKeyDirty('original_price') &&
                  (!conditions_disabled || !classifications_disabled)
                "
                @click="calculatePrice(true)"
              >
                Apply Calculations
              </button>
            </div>

            <div
              class="rr-field"
              v-if="referenceOfferExists && isEditMode"
              data-test="itemOffer-div"
            >
              <label class="rr-field__label"> Reference Offer </label>
              <div class="rr-field__textarea">
                <strong class="text-black text-lg leading-tight font-semibold">
                  {{ formatCurrency(existingItem.merchant_price) }}
                </strong>
                <span class="text-xs text-gray-700">
                  @ {{ existingItem.merchant_name }}
                </span>
              </div>
            </div>

            <div
              class="rr-field"
              v-if="priceIsDiscounted || priceManuallyInput || isEditMode"
            >
              <label class="rr-field__label"> Sale Price </label>

              <currency-input
                class="rr-field__input"
                :class="{ 'rr-field__input--flash': flashFinalPrice }"
                v-model="itemForm.price"
                data-test="itemFormPrice-input"
                @change.native="manualPriceChange"
              />

              <button
                class="rr-button rr-button--primary mt-2"
                data-test="automatedPricing-button"
                v-if="priceManuallyInput"
                @click="
                  priceManuallyInput = false;
                  itemForm.original_price = lastOriginalPrice;
                  lastOriginalPrice = null;
                "
              >
                Automated Pricing
              </button>
            </div>

            <div class="rr-field">
              <label class="rr-field__label"> Cost </label>
              <currency-input
                class="rr-field__input"
                :class="{ 'rr-field__input--flash': flashCost }"
                v-model="itemForm.cost"
                data-test="itemFormCost-input"
              />
            </div>
          </div>

          <div class="rr-field" v-if="manifestItem">
            <label class="rr-field__label">
              Manifest Found
              <span class="rr-field__label-optional">
                Autofills original price &amp; cost
              </span>
            </label>

            <div>
              <div
                v-if="manifestItem"
                :key="manifestItem.id"
                class="rr-field__radio"
              >
                <input
                  type="radio"
                  v-model="offerSelected"
                  :id="manifestItem.id"
                  :value="manifestItem"
                  class="rr-field__radio-input"
                />
                <label
                  :for="manifestItem.id"
                  class="rr-field__radio-label items-baseline"
                >
                  {{ formatCurrency(manifestItem.price) }}
                  <span class="ml-4">
                    {{ manifestItem.title | truncate(45) }}
                  </span>
                  <span class="block text-xs text-gray-700 ml-2">
                    {{ manifestItem.created_at | dateFormat }}
                    <template v-if="manifestItem.cost">
                      ({{ formatCurrency(manifestItem.cost) }})
                    </template>
                  </span>
                </label>
              </div>
            </div>
          </div>

          <div class="rr-field" v-if="sortedOffers.length > 0">
            <label class="rr-field__label">
              Pricing Found

              <span class="rr-field__label-optional">
                Autofills original price
              </span>
            </label>
            <div data-test="offers-list">
              <div
                v-for="(offer, index) in sortedOffers"
                class="rr-field__radio"
                :key="offer.link"
              >
                <input
                  type="radio"
                  v-model="offerSelected"
                  :id="offer.link"
                  :value="offer"
                  class="rr-field__radio-input"
                  :data-test="`offer-option-${index}`"
                />
                <label :for="offer.link" class="rr-field__radio-label">
                  {{ formatCurrency(offer.price) }}
                  <span class="ml-4">
                    {{ offer.merchant }}
                  </span>
                </label>
              </div>
              <button
                class="rr-button rr-button--primary ml-4 mt-2"
                @click.prevent="$refs.allOffersModal.openModal()"
                v-if="!isEditMode && offers.length > 5"
              >
                View all offers
              </button>
            </div>
          </div>

          <div
            :class="{
              'grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8':
                isEditMode,
            }"
          >
            <form @submit.prevent="queryUpc">
              <div class="rr-field">
                <label class="rr-field__label">
                  UPC

                  <span
                    class="rr-field__label-required"
                    v-if="
                      !$v.itemForm.upc.decimal ||
                      !$v.itemForm.upc.minLength ||
                      !$v.itemForm.upc.maxLength
                    "
                  >
                    Enter a valid UPC code
                  </span>
                  <span
                    v-else-if="!isEditMode"
                    class="rr-field__label-optional"
                  >
                    Autofills &amp; replaces this item
                  </span>
                </label>
                <div class="flex items-center">
                  <div class="flex-1">
                    <input
                      type="text"
                      class="rr-field__input"
                      v-model="itemForm.upc"
                      :disabled="isEditMode"
                      data-test="itemFormUpcInput"
                    />
                  </div>
                  <div>
                    <button
                      class="rr-button rr-button--primary inline-flex ml-8"
                      @click.prevent="queryUpc"
                      :disabled="
                        !existingItem ||
                        !itemForm.upc ||
                        !$v.itemForm.upc.decimal ||
                        !$v.itemForm.upc.minLength ||
                        !$v.itemForm.upc.maxLength
                      "
                      v-text="upcButtonText"
                      v-if="!isEditMode"
                    ></button>
                  </div>
                </div>
              </div>
            </form>

            <div class="rr-field" v-if="itemForm.sku && isEditMode">
              <label class="rr-field__label"> SKU </label>
              <div class="rr-field__input" v-text="itemForm.sku"></div>
            </div>
          </div>

          <div class="rr-field">
            <label class="rr-field__label"> Description </label>
            <textarea
              rows="6"
              class="rr-field__textarea"
              v-model="itemForm.description"
              maxlength="2000"
              data-test="itemFormDescription-input"
            ></textarea>
          </div>
        </div>

        <div
          class="grid grid-cols-2 gap-8"
          data-test="item-shipping"
          v-if="itemTab == 'shipping'"
        >
          <div class="rr-field">
            <label class="rr-field__label"> Length </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="itemFormLength-input"
              v-model="itemForm.length.value"
            />
            <div class="absolute pt-8 inset-y-0 right-0 flex items-center">
              <select
                class="h-full rounded-md bg-transparent py-0 pl-2 pr-7 text-black outline-none"
                v-model="lengthUnit"
              >
                <option value="mm">mm</option>
                <option value="cm">cm</option>
                <option value="in">in</option>
                <option value="ft">ft</option>
              </select>
            </div>
          </div>
          <div class="rr-field">
            <label class="rr-field__label"> Width </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="itemFormWidth-input"
              v-model="itemForm.width.value"
            />
            <div class="absolute pt-8 inset-y-0 right-0 flex items-center">
              <select
                class="h-full rounded-md bg-transparent py-0 pl-2 pr-7 text-black outline-none"
                v-model="lengthUnit"
              >
                <option value="mm">mm</option>
                <option value="cm">cm</option>
                <option value="in">in</option>
                <option value="ft">ft</option>
              </select>
            </div>
          </div>
          <div class="rr-field">
            <label class="rr-field__label"> Depth </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="itemFormDepth-input"
              v-model="itemForm.depth.value"
            />
            <div class="absolute pt-8 inset-y-0 right-0 flex items-center">
              <select
                class="h-full rounded-md bg-transparent py-0 pl-2 pr-7 text-black outline-none"
                v-model="lengthUnit"
              >
                <option value="mm">mm</option>
                <option value="cm">cm</option>
                <option value="in">in</option>
                <option value="ft">ft</option>
              </select>
            </div>
          </div>
          <div class="rr-field">
            <label class="rr-field__label"> Weight </label>
            <input
              class="rr-field__input"
              type="text"
              data-test="itemFormWeight-input"
              v-model="itemForm.weight.value"
            />
            <div class="absolute pt-8 inset-y-0 right-0 flex items-center">
              <select
                class="h-full rounded-md bg-transparent py-0 pl-2 pr-7 text-black outline-none"
                v-model="itemForm.weight.unit"
              >
                <option value="g">g</option>
                <option value="oz">oz</option>
                <option value="lb">lb</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="item-ui__aside">
      <button
        class="rr-button rr-button--lg rr-button--primary flex justify-center flex-1 w-full mb-4"
        v-if="!qzReadyToPrint || !qzLabelPrinter"
        @click="showQzPanel"
        data-test="selectLabelPrinter-button"
      >
        Select Label Printer
      </button>
      <div class="grid grid-cols-2 gap-2 mb-8">
        <button
          class="rr-button rr-button--lg rr-button--primary-solid flex justify-center"
          @click="checkForLabelConfirmation()"
          :disabled="(ready && $v.itemForm.$invalid) || !itemForm.isDirty"
          data-test="saveItem-button"
        >
          Save Item
        </button>
        <button
          class="rr-button rr-button--lg rr-button--primary flex justify-center"
          @click="$router.push({ name: 'scan' })"
          data-test="goBack-button"
        >
          Go Back
        </button>
      </div>
      <div class="grid grid-cols-1 mb-8 -mt-4">
        <button
          class="rr-button flex justify-center py-3"
          @click="freshPage"
          :disabled="!itemForm.isDirty"
          data-test="itemReset-button"
        >
          Reset all changes
        </button>
      </div>
      <div class="grid grid-cols-1 mb-4" v-if="isEditMode">
        <button
          class="rr-button flex justify-center py-3"
          data-test="printExtraLabels-button"
          @click="printExtraLabelsModal"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5 mr-1"
            fill="none"
            viewBox="0 0 24 24"
            stroke="#3A72B9"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
            />
          </svg>
          Print Extra Labels
        </button>
      </div>

      <custom-select
        :headerLabel="'Classification'"
        :options="classificationOptions"
        :selectedOptionId.sync="itemForm.classification_id"
        :required="!$v.itemForm.classification_id.required"
        :show="showClassifications && !classifications_disabled"
        @options-extended="(bool) => (showConditions = !bool)"
      />

      <custom-select
        :headerLabel="'Condition'"
        :options="conditionOptions"
        :selectedOptionId.sync="itemForm.condition_id"
        :required="!$v.itemForm.condition_id.required"
        :show="showConditions && !conditions_disabled"
        @options-extended="(bool) => (showClassifications = !bool)"
      />

      <div class="my-6" v-if="consignorsVisible.length > 0">
        <div class="rr-field__radio">
          <input
            type="radio"
            class="rr-field__radio-input"
            id="consignmentRadio"
            :data-test="'isConsignment-radio'"
            :checked="isConsignment"
            @click="
              isConsignment = !isConsignment;
              itemForm.consignor_id = null;
              itemForm.consignment_fee = null;
            "
          />
          <label for="consignmentRadio" class="rr-field__radio-label">
            Item For Consignment
          </label>
        </div>

        <div class="grid grid-cols-2 gap-2 mt-2" v-if="isConsignment">
          <div class="rr-field rr-field--select">
            <label class="rr-field__label">
              Consignor

              <span
                class="rr-field__label-required"
                v-if="!$v.itemForm.consignor_id.required"
              >
                Required
              </span>
            </label>

            <select
              v-model="itemForm.consignor_id"
              class="rr-field__input"
              data-test="consignor-select"
            >
              <option
                v-for="consignor in consignorsVisible"
                :value="consignor.id"
                :key="consignor.id"
              >
                {{ consignor.name }}
              </option>
            </select>
          </div>

          <div class="rr-field">
            <label class="rr-field__label">
              Consignment Fee

              <span
                class="rr-field__label-required"
                v-if="!$v.itemForm.consignment_fee.required"
              >
                Required
              </span>
            </label>
            <currency-input
              class="rr-field__input"
              v-model="itemForm.consignment_fee"
              data-test="consignmentFee-input"
              @change.native="consignmentFeeManuallyInput = true"
            />
          </div>
        </div>
      </div>

      <StoreQuantities
        :existingQuantities="existingQuantities"
        :hide="!showClassifications || !showConditions"
        :invalid="$v.itemForm.quantities.$invalid"
        @set-quantity-changes="
          (quantities) => (itemForm.quantities = quantities)
        "
      />

      <div class="grid grid-rows-3 gap-4 mt-4" v-if="isEditMode">
        <button
          class="rr-button flex justify-center py-3"
          data-test="itemHistory-button"
          @click="$refs.itemSpecificDiscountsModal.openModal()"
        >
          <CurrencyDollarIcon
            size="20"
            class="mr-1 self-center"
            color="#3A72B9"
          />
          Item Specific Discounts
        </button>
        <button
          class="rr-button flex justify-center py-3"
          data-test="itemHistory-button"
          @click="$refs.quantityLogModal.openModal()"
        >
          <ViewGridAddIcon size="20" class="mr-1 self-center" color="#3A72B9" />
          View Item History
        </button>
        <button
          class="rr-button flex justify-center py-3"
          data-test="quantityTransfer-button"
          @click="$refs.quantityTransferModal.openModal()"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5 mr-1"
            viewBox="0 0 20 20"
            fill="#3A72B9"
          >
            <path
              d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"
            />
          </svg>
          Transfer Quantity
        </button>
        <button
          class="rr-button flex justify-center py-3"
          data-test="itemDelete-button"
          @click="confirmDelete"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5 mr-1"
            fill="none"
            viewBox="0 0 24 24"
            stroke="#c53030"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
            />
          </svg>
          Delete Item
        </button>
      </div>
    </div>

    <LabelsConfirmationModal
      :ref="'labelsConfirmationModal'"
      :showDontAskAgain="!isEditMode"
      @labels-wanted="
        (withLabels) => {
          this.withLabels = withLabels;
          saveItem();
        }
      "
    />

    <modal ref="titleItemsModal" data-test="title-items-modal">
      <template v-slot:header>
        <span class="text-center block"> Listed Items </span>
      </template>
      <template v-slot:body>
        <div class="max-w-lg text-lg text-center mb-8 mx-auto">
          Choose a saved listing or continue creating item.
        </div>

        <div v-if="listedTitleItems.length > 0">
          <div class="max-w-lg text-center mb-8 mx-auto">
            Items we found searching by title.
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
            <tbody class="bg-white" data-test="title-items-modal-items">
              <item-row
                :key="item.id"
                :item="item"
                :data-test="`title-items-modal-title-item-${index}`"
                v-for="(item, index) in listedTitleItems"
                class="relative"
              >
                <template v-slot:hoverOptions>
                  <td
                    class="absolute inset-0 bg-gray-100/75 flex justify-center items-center"
                  >
                    <button
                      class="rr-button font-semibold mr-2"
                      data-test="editItem-button"
                      @click.stop="
                        $router.push({
                          name: 'items.edit',
                          params: { id: item.id, item: item },
                        })
                      "
                    >
                      Go to Edit
                    </button>
                    <button
                      class="rr-button font-semibold"
                      data-test="createTitleVariant-button"
                      @click.stop="fillItem(item.id)"
                    >
                      Add Variant Condition
                    </button>
                  </td>
                </template>
              </item-row>
            </tbody>
          </table>
        </div>
      </template>
      <template v-slot:footer>
        <div class="flex flex-col items-center">
          <button
            class="rr-button rr-button--lg rr-button--primary-solid mb-4"
            @click="$refs.titleItemsModal.closeModal()"
          >
            Continue Creating Item
          </button>
        </div>
      </template>
    </modal>

    <div v-if="isEditMode">
      <modal ref="quantityLogModal" :size="`6xl`">
        <template v-slot:header>
          {{ existingItem.title }}
        </template>
        <template v-slot:body>
          <ItemHistory
            :storeQuantities="existingItem.quantity_log"
            :itemId="existingItem.id"
          />
        </template>
      </modal>

      <QuantityTransferModal
        ref="quantityTransferModal"
        :itemId="existingItem.id"
        :storesWithQuantity="storesWithQuantity"
      />
    </div>

    <modal
      ref="confirmDeleteModal"
      class="centered"
      data-test="confirm-delete-modal"
    >
      <template v-slot:header> Confirm Item Deletion </template>
      <template v-slot:body>
        <div class="max-w-lg">
          <strong>Warning:</strong>
          Deleted items cannot be recovered. Are you sure you want to delete "{{
            itemForm.title
          }}"?
        </div>
      </template>
      <template v-slot:footer>
        <button
          class="rr-button rr-button--lg rr-button--danger"
          data-test="confirmDelete-button"
          @click="deleteItem()"
        >
          Delete Item
        </button>
        <button
          class="rr-button rr-button--lg ml-4"
          data-test="cancelDelete-button"
          @click="$refs.confirmDeleteModal.closeModal()"
        >
          Cancel
        </button>
      </template>
    </modal>

    <modal ref="reasonForChangeModal" class="">
      <template v-slot:header> Reason for change </template>
      <template v-slot:body>
        <div class="max-w-lg">
          <div class="rr-field">
            <label class="rr-field__label">
              Enter the reason
              <span
                class="rr-field__label-required"
                v-if="!$v.reasonInput.reason.required"
              >
                Required
              </span>
            </label>
            <textarea
              rows="6"
              class="rr-field__textarea"
              v-model="reasonInput.reason"
              maxlength="2000"
            ></textarea>
          </div>
        </div>
      </template>
      <template v-slot:footer>
        <div class="flex">
          <button
            class="rr-button rr-button--lg rr-button--primary"
            @click="updateItem()"
            :disabled="$v.reasonInput.$invalid"
          >
            Save Item
          </button>
          <button
            class="rr-button rr-button--lg ml-4"
            data-test="cancelDelete-button"
            @click="$refs.reasonForChangeModal.closeModal()"
          >
            Cancel
          </button>
        </div>
      </template>
    </modal>

    <modal
      ref="extraLabelsModal"
      class="centered"
      data-test="print-exta-labels-modal"
    >
      <template v-slot:header> Print Extra Labels </template>
      <template v-slot:body>
        <div class="max-w-lg">
          <strong>Note:</strong>
          Printing extra labels will not adjust the store’s quantity.

          <div
            class="grid md:grid-cols-2 grid-cols-1 md:gap-x-8 md:gap-y-8 mt-8 text-left"
          >
            <div class="rr-field">
              <label class="rr-field__label">
                Labels

                <span
                  class="rr-field__label-required"
                  v-if="!$v.amountToPrint.required"
                >
                  Required
                </span>
              </label>
              <input
                type="number"
                class="rr-field__input"
                step="1"
                min="1"
                max="25"
                v-model="amountToPrint"
              />
            </div>
            <div class="rr-field rr-field--select">
              <label class="rr-field__label">
                Store

                <span
                  class="rr-field__label-required"
                  v-if="!$v.storeToPrint.required"
                >
                  Required
                </span>
              </label>
              <select
                v-model="storeToPrint"
                name="store"
                class="rr-field__input"
                data-test="print-exta-labels-modal-store-select"
              >
                <option value="" selected disabled hidden>Choose...</option>
                <option v-for="store in stores" :key="store.id" :value="store">
                  {{ store.name }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </template>
      <template v-slot:footer>
        <button
          class="rr-button rr-button--lg rr-button--primary flex justify-center mr-4"
          data-test="selectLabelPrinter-button"
          v-if="!qzLabelPrinter"
          @click="showQzPanel"
        >
          Select Label Printer
        </button>
        <button
          class="rr-button rr-button--lg rr-button--primary-solid"
          @click="printExtraLabels()"
          data-test="printLabels-button"
          :disabled="$v.printExtraLabels.$invalid || !qzLabelPrinter"
        >
          Print Labels
        </button>
      </template>
    </modal>

    <modal ref="allOffersModal" class="centered" data-test="all-offers-modal">
      <template v-slot:header> Offers </template>
      <template v-slot:body>
        <div data-test="offers-list">
          <div
            v-for="(offer, index) in offers"
            class="rr-field__radio"
            :key="offer.link"
          >
            <input
              type="radio"
              v-model="offerSelected"
              :id="offer.link"
              :value="offer"
              class="rr-field__radio-input"
              :data-test="`offer-option-modal-${index}`"
            />
            <label :for="offer.link" class="rr-field__radio-label">
              {{ formatCurrency(offer.price) }}
              <span class="ml-4">
                {{ offer.merchant }}
              </span>
            </label>
          </div>
        </div>
      </template>
      <template v-slot:footer>
        <button
          class="rr-button rr-button--lg ml-4"
          data-test="cancelDelete-button"
          @click="$refs.allOffersModal.closeModal()"
        >
          Go Back
        </button>
      </template>
    </modal>

    <modal ref="itemSpecificDiscountsModal">
      <template v-slot:header>Item Specific Discounts</template>
      <template v-slot:body>
        <div v-if="isCreatingDiscount">
          <div class="rr-field">
            <label class="rr-field__label">
              Bundle Amount
              <span
                class="rr-field__label-required"
                v-if="!$v.specificDiscount.quantity.required"
              >
                Required
              </span>
            </label>
            <input
              v-model="specificDiscount.quantity"
              class="rr-field__input"
              type="number"
              data-test="bundleAmount-input"
            />
          </div>
          <div class="rr-field">
            <label class="rr-field__label">
              {{
                specificDiscount.discount_type == "percent"
                  ? "Percent Off"
                  : "Bundle Price"
              }}

              <span
                class="rr-field__label-required"
                v-if="!$v.specificDiscount.discount_amount.required"
              >
                Required
              </span>
            </label>
            <div class="relative mt-2 rounded-md shadow-sm">
              <input
                v-if="specificDiscount.discount_type == 'percent'"
                v-model="specificDiscount.discount_amount"
                type="text"
                class="rr-field__input"
                data-test="itemDiscountAmount-input"
              />
              <currency-input
                v-else
                v-model="specificDiscount.discount_amount"
                class="rr-field__input"
                data-test="itemDiscountAmount-input"
              />
              <div class="absolute inset-y-0 right-0 flex items-center">
                <select
                  v-model="specificDiscount.discount_type"
                  class="h-full rounded-md border-0 bg-transparent py-0 pl-2 pr-7 font-semibold"
                >
                  <option value="amount">Dollar Amount</option>
                  <option value="percent">Percent Off</option>
                </select>
              </div>
            </div>
          </div>
          <div class="rr-field">
            <label class="rr-field__label">Times Applicable Per Order</label>
            <input
              v-model="specificDiscount.times_applicable"
              class="rr-field__input"
              type="number"
              data-test="timesApplicable-input"
            />
          </div>
          <div class="mb-4">
            <div class="flex flex-row">
              <DatePicker
                class="w-full mr-2"
                label="Start Date"
                :maxDate="
                  specificDiscount.expires_at
                    ? new Date(specificDiscount.expires_at)
                    : null
                "
                :mode="discountsDateMode"
                :default="
                  specificDiscount.active_at
                    ? new Date(specificDiscount.active_at)
                    : null
                "
                pickerContainerClasses="relative"
                @date-selected="
                  (date) =>
                    (specificDiscount.active_at = date
                      ? dbDateString(date)
                      : null)
                "
              />
              <DatePicker
                class="w-full ml-2"
                label="End Date"
                :required="!$v.specificDiscount.expires_at.required"
                :maxDate="null"
                :minDate="
                  specificDiscount.active_at
                    ? new Date(specificDiscount.active_at)
                    : null
                "
                :mode="discountsDateMode"
                :default="
                  specificDiscount.expires_at
                    ? new Date(specificDiscount.expires_at)
                    : null
                "
                pickerContainerClasses="relative"
                @date-selected="
                  (date) =>
                    (specificDiscount.expires_at = date
                      ? dbDateString(date)
                      : null)
                "
              />
            </div>
          </div>
          <div class="mb-4">
            <div class="rr-field__radio inline-block">
              <input
                type="checkbox"
                v-model="specificDiscount.can_stack"
                :value="!specificDiscount.can_stack"
                id="canStack"
                class="rr-field__radio-input"
              />
              <label
                for="canStack"
                class="rr-field__radio-label items-baseline"
              >
                Can stack with other discounts
              </label>
            </div>
          </div>
          <span
            v-if="specificDiscount.quantity && specificDiscount.discount_amount"
            class="text-base font-semibold"
          >
            Discount description: <br />
            Buy {{ specificDiscount.quantity }} for
            {{
              specificDiscount.discount_type == "percent"
                ? `${specificDiscount.discount_amount}% off.`
                : formatCurrency(specificDiscount.discount_amount)
            }}
          </span>
        </div>
        <div v-else>
          <span class="flex mb-4">You can only have 1 active discount.</span>
          <data-table
            class="mt-4"
            v-if="itemForm.specific_discounts.length > 0"
          >
            <data-table-header>
              <data-table-header-cell>
                Existing Discounts
              </data-table-header-cell>
              <data-table-header-cell> </data-table-header-cell>
            </data-table-header>
            <data-table-body>
              <data-table-row
                v-for="(discount, index) of itemForm.specific_discounts"
                :key="index"
                @click="setItemDiscount(discount)"
              >
                <data-table-cell :class="{ 'opacity-50': discount.deleted_at }">
                  Buy {{ discount.quantity }} for
                  {{
                    discount.discount_type == "percent"
                      ? `${parseFloat(discount.discount_amount * 100).toFixed(
                          2
                        )}% off.`
                      : formatCurrency(discount.discount_amount)
                  }}
                </data-table-cell>
                <data-table-cell>
                  <TrashIcon
                    v-if="!discount.deleted_at"
                    @click.stop="deactivateDiscount(discount)"
                    class="text-red-500 float-right"
                  />
                  <CheckIcon
                    v-else
                    @click.stop="activateDiscount(discount)"
                    class="text-green-500 float-right"
                  />
                </data-table-cell>
              </data-table-row>
            </data-table-body>
          </data-table>
          <b v-else>No existing item specific discounts for this item.</b>
        </div>
      </template>
      <template v-slot:footer>
        <div class="flex flex-row gap-x-4">
          <button
            class="rr-button rr-button--lg rr-button--primary-solid"
            @click="
              () => {
                if (isCreatingDiscount) return createSpecificDiscount();
                isCreatingDiscount = true;
              }
            "
            :disabled="
              isCreatingDiscount &&
              ($v.specificDiscount.$invalid || !specificDiscount.isDirty)
            "
            data-test="createDiscount-button"
          >
            Create a Discount
          </button>
          <button
            v-if="isCreatingDiscount"
            class="rr-button rr-button--lg"
            @click="isCreatingDiscount = false"
            data-test="discountModalBack-button"
          >
            Back
          </button>
        </div>
      </template>
    </modal>

    <ConfirmationModal
      ref="confirmationModal"
      header="Discount already exists for this item and quantity, would you like to overwrite it?"
      @response="
        (res) => {
          if (res) createSpecificDiscount(res);
          else isCreatingDiscount = false;
        }
      "
    />
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import { formatCurrency, dbDateStringNow, dbDateString } from "@/helpers";
import {
  required,
  requiredIf,
  minLength,
  maxLength,
  decimal,
} from "vuelidate/lib/validators";
import { debounce } from "lodash";
import moment from "moment";
import {
  ViewGridAddIcon,
  CurrencyDollarIcon,
  CheckIcon,
  TrashIcon,
} from "@vue-hero-icons/outline";

import ItemNavigation from "@/components/ItemNavigation";
import ItemImages from "@/components/ItemImages";
import ItemRow from "@/components/ItemRow";
import AdjustQuantity from "@/components/AdjustQuantity";
import CustomSelect from "@/components/CustomSelect";
import StoreQuantities from "@/components/StoreQuantities";
import ItemHistory from "@/components/ItemHistory";
import QuantityTransferModal from "@/components/QuantityTransferModal";
import LabelsConfirmationModal from "@/components/LabelsConfirmationModal.vue";
import ConfirmationModal from "@/components/ConfirmationModal.vue";
import DatePicker from "@/components/DatePicker.vue";

import ItemsMixin from "../mixins/ItemsMixin";
import dataTableMixin from "@/components/table/dataTableMixin";
import Form from "../classes/Form";

export default {
  name: "ItemUI",

  props: {
    existingItem: {
      type: Object,
    },
    hiddenConditions: {
      type: Array,
      default: () => [],
    },
    isEditMode: {
      type: Boolean,
      default: false,
    },
  },

  mixins: [ItemsMixin, dataTableMixin],

  components: {
    ItemNavigation,
    ItemImages,
    ItemRow,
    AdjustQuantity,
    CustomSelect,
    StoreQuantities,
    ViewGridAddIcon,
    CurrencyDollarIcon,
    ItemHistory,
    QuantityTransferModal,
    LabelsConfirmationModal,
    ConfirmationModal,
    CheckIcon,
    TrashIcon,
    DatePicker,
  },

  data() {
    return {
      reasonInput: {
        reason: null,
      },
      itemForm: new Form({
        condition_id: null,
        classification_id: null,
        consignor_id: null,
        title: null,
        price: null,
        original_price: null,
        cost: null,
        description: null,
        upc: null,
        sku: null,
        quantities: [],
        images: [],
        deleted_images: [],
        specific_discounts: [],
        asin: null,
        brand: null,
        color: null,
        ean: null,
        elid: null,
        merchant_name: null,
        merchant_price: null,
        consignment_fee: null,
        length: {
          value: null,
          unit: "in",
        },
        width: {
          value: null,
          unit: "in",
        },
        depth: {
          value: null,
          unit: "in",
        },
        weight: {
          value: null,
          unit: "oz",
        },
      }),
      // ItemMain data
      lengthUnit: "in",
      offers: [],
      offerSelected: {},
      specificDiscount: new Form({
        id: null,
        item_id: null,
        quantity: null,
        discount_amount: null,
        discount_type: "amount",
        times_applicable: null,
        can_stack: false,
        active_at: null,
        expires_at: null,
        deleted_at: null,
      }),
      discountsDateMode: "date",
      listedTitleItems: [],
      itemsTitleQueryCount: null,
      manifestItem: null,
      lastOriginalPrice: null,
      priceManuallyInput: false,
      searchViaTitle: false,
      flashFinalPrice: false,
      flashOriginalPrice: false,
      flashCost: false,
      loadingUpc: true,
      itemTab: "details",
      isCreatingDiscount: false,
      // ItemAside data
      amountToPrint: 1,
      storeToPrint: {},
      conditionsNotHidden: [],
      showClassifications: true,
      showConditions: true,
      isConsignment: false,
      hideConsignment: false,
      consignmentFeeManuallyInput: false,
      ready: true,
      withLabels: true,
    };
  },

  computed: {
    ...mapGetters([
      "stores",
      "classifications",
      "conditions",
      "classificationsVisible",
      "conditionsVisible",
      "qzReadyToPrint",
      "qzLabelPrinter",
      "consignorsVisible",
      "classifications_disabled",
      "conditions_disabled",
      "wantsLabels",
      "wantsLabelsConfirmation",
    ]),
    referenceOfferExists() {
      return this.itemForm.merchant_name && this.itemForm.merchant_price;
    },
    existingQuantities() {
      return this.existingItem && this.existingItem.store_quantities
        ? this.existingItem.store_quantities
        : [];
    },
    itemReadyToSave() {
      return this.isEditMode
        ? this.itemForm.isDirty && !this.$v.itemForm.$invalid
        : this.ready && !this.$v.itemForm.$invalid;
    },
    classificationOptions() {
      return !this.isEditMode
        ? this.classificationsVisible
        : this.classifications;
    },
    conditionOptions() {
      return !this.isEditMode ? this.conditionsNotHidden : this.conditions;
    },
    upcButtonText() {
      return !this.existingItem ? "Please Wait..." : "Lookup UPC and Pricing";
    },
    sortedOffers() {
      if (!this.offers) return [];

      const offerLimit = 5;
      const offers = JSON.parse(JSON.stringify(this.offers));

      return offers
        .filter((offer) => {
          return offer.price > 0;
        })
        .sort((a, b) => {
          return a.updated_t < b.updated_t ? 1 : -1;
        })
        .slice(0, offerLimit)
        .sort((a, b) => {
          return a.price > b.price ? 1 : -1;
        });
    },
    priceIsDiscounted() {
      const isDiscounted =
        this.itemForm.price > 0 &&
        this.itemForm.price < this.itemForm.original_price;

      if (isDiscounted) {
        this.flashFinalPriceInput();
      }

      return isDiscounted;
    },
    itemImages() {
      return this.existingItem && this.existingItem.images
        ? [...this.existingItem.images]
        : [];
    },
    storesWithQuantity() {
      return this.existingItem.store_quantities
        ? [...this.existingItem.store_quantities]
        : [];
    },
  },

  watch: {
    "itemForm.title": debounce(function () {
      if (!this.searchViaTitle) return;

      this.getItemsCountForTitle();
    }, 500),
    "itemForm.original_price": function () {
      this.calculatePrice();
    },
    "itemForm.classification_id": function () {
      this.calculatePrice();
    },
    "itemForm.condition_id": function () {
      this.calculatePrice();
    },
    "itemForm.consignor_id": function () {
      this.calculateConsignmentFee();
    },
    "itemForm.price": function () {
      if (this.itemForm.consignor_id) {
        this.calculateConsignmentFee();
      }
    },
    "specificDiscount.discount_type": function () {
      if (!this.specificDiscount.isDirty) return;
      this.specificDiscount.discount_amount = null;
    },
    isCreatingDiscount(bool) {
      if (!bool) this.specificDiscount.initialState();
    },
    hiddenConditions(conditions) {
      this.hideConditions(conditions);
    },
    offerSelected: function (offer) {
      if (this.isEditMode) return;

      let merchantName = null;

      // Handle merchant name if manifest.
      if (offer.merchant) {
        merchantName = offer.merchant;
      } else if (offer.title) {
        let manifestDate = moment(offer.createdAt).format("M/D/YYYY");
        merchantName = `Manifest (${manifestDate})`;
      }

      if (offer.cost) {
        this.itemForm.cost = offer.cost;
        this.flashCostInput();
      }

      this.itemForm.merchant_name = merchantName;
      this.itemForm.original_price = offer.price ? offer.price : null;
      this.itemForm.merchant_price = offer.price ? offer.price : null;
      this.$refs.allOffersModal.closeModal();
      this.flashOriginalPriceInput();
    },
    lengthUnit(unit) {
      this.itemForm.length.unit = unit;
      this.itemForm.width.unit = unit;
      this.itemForm.depth.unit = unit;
    },
    existingItem: {
      handler(item) {
        if (!item) return;

        item = { ...item };

        if (!this.isEditMode) {
          delete item.images;
          this.offers = item.offers;
        }

        this.itemForm.fill(item);
      },
      immediate: true,
    },
  },

  mounted() {
    this.conditionsNotHidden = [...this.conditionsVisible];

    if (this.$route.params.item & !this.isEditMode) {
      this.manifestItem = this.$route.params.item;
      this.offerSelected = this.manifestItem;
      this.itemForm.update(this.manifestItem);
    } else if (this.isEditMode && this.existingItem) {
      this.itemForm.update(this.existingItem);
    }

    if (this.$route.query["hide-consignment"]) {
      this.hideConsignment = true;
    }

    this.$root.$on("refresh-item", () => {
      this.offerSelected = {};
      this.itemForm.reset();
      this.conditionsNotHidden = [...this.conditionsVisible];
    });
  },

  methods: {
    saveItem() {
      if (this.isEditMode) {
        if (
          this.itemForm.isKeyDirty("original_price") ||
          this.itemForm.isKeyDirty("price") ||
          this.itemForm.isKeyDirty("cost")
        )
          this.reasonForChange();
        else this.updateItem();
      } else {
        this.createItem();
      }
    },

    createItem() {
      if (this.withLabels && !this.readyToPrintLabel()) {
        this.$refs.labelsConfirmationModal.closeModal();
        return;
      }

      this.ready = false;
      this.$store
        .dispatch("createItem", this.itemForm.data)
        .then((item) => {
          this.itemForm.update(item);
          this.$toasted.show(`Item Created: "${item.title}".`, {
            type: "success",
          });
          this.handleSavedItem(this.withLabels, "scan");
        })
        .catch(() => {
          this.ready = true;
        });
    },

    updateItem() {
      if (this.withLabels && !this.readyToPrintLabel()) {
        return this.$refs.labelsConfirmationModal.closeModal();
      }

      this.$store
        .dispatch("updateItem", {
          itemId: this.existingItem.id,
          update: {
            ...this.itemForm.dirtyData,
            reason: this.reasonInput.reason,
          },
        })
        .then((item) => {
          this.itemForm.update(item);
          this.$toasted.show(`Item Updated: "${item.title}".`, {
            type: "success",
          });
          this.handleSavedItem(this.withLabels, "items.index");
        });
    },

    deleteItem() {
      this.$store.dispatch("deleteItem", this.existingItem.id).then(() => {
        this.$toasted.show("Item Successfully Deleted.", { type: "success" });
        this.$router.push({ name: "items.index" });
      });
    },

    checkForLabelConfirmation() {
      if (this.isEditMode && this.positiveQuantities.length > 0) {
        this.$refs.labelsConfirmationModal.openModal();
        return;
      } else if (!this.isEditMode && this.wantsLabelsConfirmation) {
        this.$refs.labelsConfirmationModal.openModal();
        return;
      } else {
        this.saveItem();
      }
    },

    calculatePrice(manualTrigger = false) {
      if (this.isEditMode && !manualTrigger) return;

      const classificationRequirements =
        (!this.classifications_disabled && this.itemForm.classification_id) ||
        this.classifications_disabled;
      const conditionRequirements =
        (!this.conditions_disabled && this.itemForm.condition_id) ||
        this.conditions_disabled;

      if (
        this.itemForm.original_price &&
        classificationRequirements &&
        conditionRequirements &&
        !this.priceManuallyInput
      ) {
        const data = {
          price: this.itemForm.original_price,
          classification_id: this.itemForm.classification_id,
          condition_id: this.itemForm.condition_id,
        };

        this.$store.dispatch("calculatePrice", data).then((price) => {
          this.itemForm.price = price;
        });
      }
    },

    calculateConsignmentFee() {
      if (
        this.itemForm.consignor_id &&
        this.itemForm.price &&
        !this.consignmentFeeManuallyInput
      ) {
        this.$store
          .dispatch("calculateConsignmentFee", {
            consignor_id: this.itemForm.consignor_id,
            price: this.itemForm.price,
          })
          .then(({ consignment_fee }) => {
            if (consignment_fee) {
              this.itemForm.consignment_fee = consignment_fee;
            }
          });
      }
    },

    queryForTitleItems() {
      this.$store
        .dispatch("queryItems", { query: this.itemForm.title })
        .then((items) => {
          this.listedTitleItems = items;
          this.$refs.titleItemsModal.openModal();
        });
    },

    getItemsCountForTitle() {
      if (this.isEditMode) return;

      this.$store
        .dispatch("getItemsCountForQuery", this.itemForm.title)
        .then((count) => {
          this.itemsTitleQueryCount = count;

          if (count > 0) {
            this.removeDupeConditions = false;
            this.$store
              .dispatch("getUsedConditionsFromTitle", this.itemForm.title)
              .then((conditions) => {
                this.hideConditions(conditions);
              });
          }
        });
    },

    setItemDiscount(discount) {
      discount = { ...discount };
      if (discount.deleted_at) return;
      else if (discount.discount_type == "percent")
        discount.discount_amount = parseFloat(
          discount.discount_amount * 100
        ).toFixed(2);

      this.isCreatingDiscount = true;
      this.specificDiscount.update(discount);
    },

    fillItem(itemId) {
      this.$store.dispatch("getItem", itemId).then((item) => {
        this.removeDupeConditions = true;
        this.listedItems = [item];
        this.images = item.images;
        var merchantName = item.merchant_name
          ? item.merchant_name
          : "Item from inventory";
        this.offers = [
          {
            merchant: merchantName,
            price: item.original_price / 100,
            link: item.id,
          },
        ];
        this.itemForm.fill({
          title: item.title,
          cost: item.cost == 0 ? null : item.cost,
          upc: item.upc ? item.upc : this.upc,
          description: item.description,
          classification_id: item.classification_id,
        });

        this.$refs.titleItemsModal.closeModal();
      });
    },

    confirmDelete() {
      this.$refs.confirmDeleteModal.openModal();
    },

    reasonForChange() {
      this.$refs.reasonForChangeModal.openModal();
    },

    manualPriceChange() {
      if (!this.priceManuallyInput) {
        this.lastOriginalPrice = this.itemForm.original_price;
      }

      this.priceManuallyInput = true;
      this.itemForm.original_price = this.itemForm.price;
    },

    originalPriceChanged() {
      if (this.itemForm.original_price < this.itemForm.price) {
        this.$toasted.show(
          "Cannot have a lower original price than sale price.",
          { type: "info" }
        );
        return (this.itemForm.original_price = this.itemForm.price);
      }
    },

    hideConditions(conditions) {
      this.conditionsNotHidden = [
        ...this.conditionsNotHidden.filter((c) => {
          return !conditions.includes(c.id);
        }),
      ];
    },

    createSpecificDiscount(overwrite = false) {
      const existingIndex = this.itemForm.specific_discounts.findIndex(
        (d) =>
          this.specificDiscount.id == d.id ||
          d.quantity == this.specificDiscount.quantity
      );

      if (
        this.specificDiscount.discount_type == "percent" &&
        (existingIndex == -1 || (existingIndex > -1 && overwrite))
      ) {
        this.specificDiscount.discount_amount /= 100;
      }

      const data = Object.assign({}, this.specificDiscount.data);

      if (
        this.itemForm.specific_discounts.length > 0 &&
        existingIndex > -1 &&
        !overwrite
      ) {
        return this.$refs.confirmationModal.openModal();
      } else if (existingIndex > -1 && overwrite) {
        this.itemForm.specific_discounts[existingIndex] = data;
      } else {
        if (this.itemForm.specific_discounts.length > 0) {
          this.itemForm.specific_discounts.forEach(
            (d) => (d.deleted_at = dbDateStringNow())
          );
        }

        this.itemForm.specific_discounts.push(data);
      }

      this.isCreatingDiscount = false;
    },

    activateDiscount(discount) {
      this.itemForm.specific_discounts
        .filter((d) => JSON.stringify(d) != JSON.stringify(discount))
        .forEach((d) => (d.deleted_at = dbDateStringNow()));
      discount.deleted_at = null;
    },

    deactivateDiscount(discount) {
      if (discount.id != null) return (discount.deleted_at = dbDateStringNow());

      const index = this.itemForm.specific_discounts.findIndex(
        (d) => JSON.stringify(d) == JSON.stringify(discount)
      );
      this.itemForm.specific_discounts.splice(index, 1);
    },

    printExtraLabels() {
      this.printLabel(this.storeToPrint.id, this.amountToPrint);
      this.$refs.extraLabelsModal.closeModal();
    },

    printExtraLabelsModal() {
      if (this.readyToPrintLabel()) this.$refs.extraLabelsModal.openModal();
    },

    showQzPanel() {
      this.$store.dispatch("updateQzPanel", true);
    },

    freshPage() {
      this.$root.$emit("refresh-item");
      this.priceManuallyInput = false;
    },

    flashFinalPriceInput() {
      this.flashFinalPrice = true;
      setTimeout(() => {
        this.flashFinalPrice = false;
      }, 500);
    },

    flashOriginalPriceInput() {
      this.flashOriginalPrice = true;
      setTimeout(() => {
        this.flashOriginalPrice = false;
      }, 500);
    },

    flashCostInput() {
      this.flashCost = true;
      setTimeout(() => {
        this.flashCost = false;
      }, 500);
    },

    formatCurrency,
    dbDateStringNow,
    dbDateString,
  },

  validations() {
    return {
      amountToPrint: { required, decimal },
      storeToPrint: { required },
      printExtraLabels: ["amountToPrint", "storeToPrint"],
      reasonInput: {
        reason: {
          required: requiredIf(function () {
            return (
              this.itemForm.isKeyDirty("original_price") ||
              this.itemForm.isKeyDirty("price") ||
              this.itemForm.isKeyDirty("cost")
            );
          }),
        },
      },
      itemForm: {
        condition_id: {
          required: requiredIf(function () {
            return !this.conditions_disabled;
          }),
        },
        classification_id: {
          required: requiredIf(function () {
            return !this.classifications_disabled;
          }),
        },
        consignor_id: {
          required: requiredIf(function () {
            return this.isConsignment;
          }),
        },
        consignment_fee: {
          required: requiredIf(function () {
            return this.isConsignment;
          }),
          decimal,
        },
        title: { required },
        original_price: { required, decimal },
        price: { required, decimal },
        upc: { decimal, minLength: minLength(12), maxLength: maxLength(13) },
        images: {
          minLength: minLength(0),
          maxLength: maxLength(5),
        },
        quantities: {
          required: requiredIf(function () {
            return !this.isEditMode;
          }),
          $each: {
            quantityValidation(qty) {
              if (
                qty.quantity_received > 0 ||
                (qty.quantity_received < 0 && qty.message)
              ) {
                return true;
              }

              return false;
            },
          },
        },
        specific_discounts: {
          $each: {
            quantity: { required, decimal },
            discount_amount: { required, decimal },
            discount_type: { required },
          },
        },
      },
      specificDiscount: {
        quantity: { required, decimal },
        discount_amount: { required, decimal },
        discount_type: { required },
        expires_at: {
          required: requiredIf(function () {
            return this.specificDiscount.active_at != null;
          }),
        },
      },
    };
  },

  filters: {
    dateFormat(date) {
      return moment(date).format("M/D/YYYY");
    },
  },
};
</script>

<style></style>
