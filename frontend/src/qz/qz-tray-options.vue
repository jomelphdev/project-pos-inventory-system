<template>
  <div class="qz-tray--container qz-tray--options" v-if="hasParent && !hide">
    <div class="qz-tray--options--header--wrapper" v-if="!hideHeader">
      <div class="qz-tray--options--label--wrapper">
        <slot name="label">
          <h4 class="qz-tray--options--label">Options</h4>
        </slot>
      </div>

      <div class="qz-tray--options--reset--wrapper">
        <slot name="button-reset" :reset="reset">
          <button
            class="qz-tray--options--reset--button qz-tray--button"
            @click="reset"
          >
            Reset Options
          </button>
        </slot>
      </div>
    </div>

    <slot></slot>

    <div class="qz-tray--options--wrapper" v-if="!hideBody">
      <qz-tray-form-nested :items="qzOptions">
        <template
          v-for="(slot, slotIndex) in $scopedSlots"
          #[slotIndex]="{ item, index }"
        >
          <slot :name="slotIndex" :item="item" :index="index"></slot>
        </template>
      </qz-tray-form-nested>
    </div>
  </div>
</template>

<script>
import QzMixin from "@/qz/qz-mixin";
import QzTrayFormNested from "@/qz/qz-tray-form-nested.vue";

export default {
  name: "QzTrayOptions",

  label: "print-options",

  mixins: [QzMixin],

  components: {
    QzTrayFormNested
  },

  methods: {
    reset: function() {
      this.resetOptions();
    }
  },

  /* ************************************************************ *
   *                             Note:                            *
   * ************************************************************ *
   *                                                              *
   * qzOptions from QzMixin are used here for all options.        *
   *                                                              *
   * Format:                                                      *
   *                                                              *
   * let qzOptions = {                                            *
   *     ...: {...},                                              *
   *                                                              *
   *     copies: {                                                *
   *         label: 'Copies',                                     *
   *         value: 1,                                            *
   *         type: 'number',                                      *
   *         hidden: false,                                       *
   *     },                                                       *
   *                                                              *
   *     orientation: {                                           *
   *         label: 'orientation',                                *
   *         value: 'portrait',                                   *
   *         type: 'select',                                      *
   *         options: 'portrait|landscape|reverse-landscape'      *
   *         hidden: false,                                       *
   *     },                                                       *
   *                                                              *
   *     ...: {...},                                              *
   * };                                                           *
   *                                                              *
   * ************************************************************ */
};
</script>
